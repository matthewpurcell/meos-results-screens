<?php

	include_once('functions.php');

	header('Content-type: application/json');
	header('X-Content-Type-Options: nosniff');
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: GET,PUT,POST,DELETE');
	header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');


	// -------------------------------------------
	// Check whether a radio id has been passed in
	// -------------------------------------------

	$radioId = null;
	$timestamp = null;

	// Update the variables
	if (isset($_GET['radioId'])) {
		$radioId = $_GET['radioId'];
	}

	if (isset($_GET['timestamp'])) {
		$timestamp = $_GET['timestamp'];
	}

	// ------------------------------------------------------
	// Get the current competition from the database defaults
	// ------------------------------------------------------

	// Connect to the MeOS defaults database
	$linkMeosDefaults = @new mysqli(MYSQL_HOSTNAME, MYSQL_USERNAME, MYSQL_PASSWORD, "meos-defaults");

	// Query the MeOS main database for a list of competitions
	$sql = "SELECT property, value, data FROM defaultEvents";

	// Execute the query
	$res = $linkMeosDefaults->query($sql);

	// Variables to store the current defaults
	$meosEventId = -1;
	$meosEventNameId = null;
	$meosMopId = -1;

	// Loop through each property
	while ($r = $res->fetch_assoc()) {

		if ($r['property'] == "meosEventId") {
			$meosEventId = $r['value'];
			$meosEventNameId = $r['data'];
		}

		else if ($r['property'] == "meosMopId") {
			$meosMopId = $r['value'];
		}

	}

	// Close the connection to the database
	mysqli_close($linkMeosDefaults);

	// Get the competition id
	$cmp = $meosMopId;

	// Connect to the MeOS MOP database
	$linkMop = ConnectToDB();


	// ---------------------------
	// Get the competition details
	// ---------------------------

	// Query the database for the competition information
	$sql = "SELECT name, date FROM mopCompetition WHERE cid = " . $cmp . " LIMIT 1";

	// Execute the query
	$res = $linkMop->query($sql);

	// Get the result
	$r = $res->fetch_assoc();
	$cmpName = $r['name'];
	$cmpDate = $r['date'];


	// ----------------------------------------------------
	// Get the leg distances (if available) for all courses
	// ----------------------------------------------------

	// Connect to the actual MeOS event database for this event
	$linkEventDB = @new mysqli(MYSQL_HOSTNAME, MYSQL_USERNAME, MYSQL_PASSWORD, $meosEventNameId);

	// Query the database for the courses and distances
	$sql = "SELECT Id, Name, Controls, Length, Legs FROM oCourse";

	// Create an associative array to hold the course information
	$courseArray = array();

	// Execute the query
	$res = $linkEventDB->query($sql);

	// Loop through each course
	while ($r = $res->fetch_assoc()) {

		// Create a course object
		$courseObject = array();
		$courseObject["id"] = $r['Id'];
		$courseObject["name"] = $r['Name'];
		$courseObject["length"] = $r['Length'];

		// Split the controls into an array
		$controlsArray = explode(";", $r["Controls"]);

		// Split the leg distances into an array
		$legsArray = explode(";", $r["Legs"]);

		// Create an associative array to store the control -> distance for the course
		$legDistanceArray = array();

		// Loop through the controlCodeArray and get the associated distance (if available)
		for ($i = 0; $i < count($controlsArray); $i = $i + 1) {

			// Control code
			$controlCode = $controlsArray[$i];

			// Check it's not empty
			if (empty($controlCode)) {

				// Deal with the final control to finish leg
				if ($i == count($controlsArray) - 1) {
					$controlCode = "FINISH";
				}

				else {
					continue;
				}

			}

			// Check whether the control has a corresponding leg distance
			if ( (array_key_exists($i, $legsArray)) && (!empty($legsArray[$i])) ) {

				// Control length
				$controlLength = $legsArray[$i];

				// Store the length of that control
				$legDistanceArray[$controlCode] = $controlLength;

			}

		}

		// Add to the course object
		$courseObject["legDistances"] = $legDistanceArray;

		// Add the course to the courseArray
		$courseArray[$r['Id']] = $courseObject;

	}

	// print_r($courseArray);

	// Get the classes so they can be mapped to courses
	$sql = "SELECT Id, Name, Course FROM oClass";

	// Create an associative array to hold the class information
	$classToCourseArray = array();

	// Execute the query
	$res = $linkEventDB->query($sql);

	// Loop through each class
	while ($r = $res->fetch_assoc()) {

		// Map each class to a course
		$classToCourseArray[$r['Id']] = $r['Course'];

	}

	// Close the connection to the event database - note to self, we don't use the event database again from here
	mysqli_close($linkEventDB);


	// ---------------------
	// Get the class details
	// ---------------------

	// Query the database for the class information
	$sql = "SELECT name, id FROM mopClass WHERE cid = " . $cmp . " ORDER BY ord";

	// Execute the query
	$res = $linkMop->query($sql);

	// Create an associative array to hold the class information
	$classArray = array();

	// Loop through each class
	while ($r = $res->fetch_assoc()) {
		$classArray[$r['id']] = $r['name'];
	}


	// ----------------------
	// Get the latest punches
	// ----------------------

	// Create an array to store the latest punches
	$latestPunches = array();

	// Check if we only want to get punches after a certain time
	$timestampWhereClause = '';

	if ($timestamp) {
		$timestampWhereClause = ' AND UNIX_TIMESTAMP(rt_timestamp) >= ' . $timestamp . ' ';
	}

	// If no radio id has been passed in, or the radio id is FINISH, then we do the latest punches at the finish
	if (($radioId == null) || ($radioId == "FINISH")) {

		// Build the query
		$sql = 'SELECT competitor.id, competitor.name as competitorName, competitor.cls, org.name AS clubName, competitor.rt, competitor.rt_timestamp

			FROM mopCompetitor AS competitor

			LEFT JOIN mopOrganization AS org ON competitor.org = org.id AND competitor.cid = org.cid

			WHERE competitor.cid = '. $meosMopId . $timestampWhereClause . ' ORDER BY rt_timestamp DESC LIMIT 0, 11';

		// Run the query
		$res = $linkMop->query($sql);
			
		// Loop through the results
		while ($r = $res->fetch_assoc()) {

			// Create an object for each result
			$resultObj = array();

			// Populate with the required data
			$resultObj['competitorId'] = $r['id'];
			$resultObj['name'] = $r['competitorName'];
			$resultObj['clsId'] = $r['cls'];
			$resultObj['clsName'] = $classArray[$r['cls']];
			$resultObj['club'] = $r['clubName'];
			$resultObj['time'] = $r['rt'];

			// Add to the rows array
			array_push($latestPunches, $resultObj);

		}

		// Loop through each result
		for ($i = 0; $i < count($latestPunches); $i = $i + 1) {

			// Get the result object
			$resultObj = $latestPunches[$i];

			// Get the diff and ranks for all finishers in this class
			$sql = 'WITH bestTimeCte AS (
				SELECT MIN(rt) minRt FROM mopCompetitor WHERE cid = '. $meosMopId .' AND cls = '. $resultObj['clsId'] .' AND rt > 0
			)

			SELECT competitor.id, competitor.rt - (SELECT minRt FROM bestTimeCte) as diff,
				CASE 
				    WHEN competitor.rt IS NOT NULL THEN RANK() OVER ( PARTITION 
				BY
				    (CASE 
				        WHEN competitor.rt IS NOT NULL THEN 1 
				        ELSE 0 
				    END)
				ORDER BY
				    competitor.rt ) 
				END finishRank

			FROM mopCompetitor AS competitor 

			WHERE competitor.cid = '. $meosMopId .' AND competitor.cls = '. $resultObj['clsId'] .' AND competitor.rt > 0';

			// Run the query
			$res = $linkMop->query($sql);

			// Loop through the results
			while ($r = $res->fetch_assoc()) {

				// Find the entry for this competitor
				if ($r['id'] == $resultObj['competitorId']) {

					// Set the diff and rank
					$latestPunches[$i]['diff'] = $r['diff'];
					$latestPunches[$i]['rank'] = $r['finishRank'];

					// Break out of the loop
					break;

				}

			}

		}

	}

	// Otherwise, get the latest punches for a particular radio
	else {

		// Build the query
		$sql = 'SELECT radio.id, radio.rt, competitor.name AS competitorName, competitor.cls, org.name AS clubName, radio.rt_timestamp

			FROM mopRadio As radio

			LEFT JOIN mopCompetitor AS competitor ON competitor.id = radio.id AND radio.cid = competitor.cid

			LEFT JOIN mopOrganization AS org ON competitor.org = org.id AND radio.cid = org.cid

			WHERE radio.cid = '. $meosMopId . $timestampWhereClause . ' AND radio.ctrl = '. $radioId .' ORDER BY rt_timestamp DESC LIMIT 0, 11';

		// Run the query
		$res = $linkMop->query($sql);
			
		// Loop through the results
		while ($r = $res->fetch_assoc()) {

			// Create an object for each result
			$resultObj = array();

			// Populate with the required data
			$resultObj['competitorId'] = $r['id'];
			$resultObj['name'] = $r['competitorName'];
			$resultObj['clsId'] = $r['cls'];
			$resultObj['clsName'] = $classArray[$r['cls']];
			$resultObj['club'] = $r['clubName'];
			$resultObj['time'] = $r['rt'];

			// Add to the rows array
			array_push($latestPunches, $resultObj);

		}

		// Loop through each result
		for ($i = 0; $i < count($latestPunches); $i = $i + 1) {

			// Get the result object
			$resultObj = $latestPunches[$i];

			// Get the diff and ranks for all runners in this class at this radio
			$sql = 'WITH idsOfCompetitorsInClassCte AS (
				SELECT id FROM mopCompetitor WHERE cid = '. $meosMopId .' AND cls = '. $resultObj['clsId'] .'
			),
			bestTimeCte AS (
				SELECT MIN(rt) minRt FROM mopRadio WHERE cid = '. $meosMopId .' AND ctrl = '. $radioId .' AND rt > 0 AND id IN (SELECT id FROM idsOfCompetitorsInClassCte)
			)

			SELECT radio.id, radio.rt - (SELECT minRt FROM bestTimeCte) as diff,
				CASE 
				    WHEN radio.rt IS NOT NULL THEN RANK() OVER ( PARTITION 
				BY
				    (CASE 
				        WHEN radio.rt IS NOT NULL THEN 1 
				        ELSE 0 
				    END)
				ORDER BY
				    radio.rt ) 
				END radioRank

			FROM mopRadio AS radio 

			WHERE radio.cid = '. $meosMopId .' AND radio.ctrl = '. $radioId .' AND radio.rt > 0 AND radio.id IN (SELECT id FROM idsOfCompetitorsInClassCte)';

			// Run the query
			$res = $linkMop->query($sql);

			// Loop through the results
			while ($r = $res->fetch_assoc()) {

				// Find the entry for this competitor
				if ($r['id'] == $resultObj['competitorId']) {

					// Set the diff and rank
					$latestPunches[$i]['diff'] = $r['diff'];
					$latestPunches[$i]['rank'] = $r['radioRank'];

					// Break out of the loop
					break;

				}

			}

		}

	}

	// -----------------------
	// Build the JSON response
	// -----------------------

	// Competition information
	$responseArray['cmpName'] = $cmpName;
	$responseArray['cmpDate'] = $cmpDate;

	// Radio information
	$radioInfo = array();
	$radioInfo['radioId'] = $radioId;
	$responseArray['radioInfo'] = $radioInfo;

	// Competitor information
	$responseArray['latestPunches'] = $latestPunches;

	// Return the JSON
	echo json_encode($responseArray);

?>