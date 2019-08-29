<?php

	include_once('functions.php');

	header('Content-type: application/json');
	header('X-Content-Type-Options: nosniff');
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: GET,PUT,POST,DELETE');
	header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

	function formatAbsoluteTime($t) {
		
		// This code does hh:mm:ss for > 1 hour and mm:ss for < 1 hour

		/*
		if ($t) {		
			$t = $t/10; // convert from 10ths of seconds into seconds
			if ($t > 3600)
				return sprintf("%d:%02d:%02d", $t/3600, ($t/60)%60, $t%60);
			else
				return sprintf("%02d:%02d", ($t/60)%60, $t%60);
		}
		*/

		// This code does mmm:ss for everyone
		if ($t) {		
			$t = $t/10; // convert from 10ths of seconds into seconds
			return sprintf("%01d:%02d", ($t/60), $t%60);
		}

		return null;
	}

	function formatDiffTime($t) {

		// This code does hh:mm:ss for > 1 hour and mm:ss for < 1 hour

		/*
		if ($t) {
			$t = $t/10; // convert from 10ths of seconds into seconds
			if ($t > 3600) 		
				return sprintf("+%d:%02d:%02d", $t/3600, ($t/60)%60, $t%60);
			else
				return sprintf("+%d:%02d", ($t/60)%60, $t%60);
		}
		*/

		// This code does mmm:ss for everyone
		if ($t) {
			$t = $t/10; // convert from 10ths of seconds into seconds
			return sprintf("+%d:%02d", ($t/60), $t%60);
		}

		return null;
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
	$marqueeShow = 0;
	$marqueeDuration = 20;
	$marqueeText = '';

	// Loop through each property
	while ($r = $res->fetch_assoc()) {

		if ($r['property'] == "meosEventId") {
			$meosEventId = $r['value'];
			$meosEventNameId = $r['data'];
		}

		else if ($r['property'] == "meosMopId") {
			$meosMopId = $r['value'];
		}

		else if ($r['property'] == "marqueeShow") {
			$marqueeShow = $r['value'];
		}

		else if ($r['property'] == "marqueeText") {
			$marqueeDuration = $r['value'];
			$marqueeText = $r['data'];
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

	// TODO: Figure out which database to use based on the competition

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

	// print_r($classToCourseArray);


	// ---------------------------------
	// Get each runner's official status
	// ---------------------------------

	// We need to do this so we can differentiate between runners who have finished (punched the radio finish)
	// unit but no download yet vs. runners who have downloaded and have a confirmed, official time and correct punches

	// Query the database for the courses and distances
	$sql = "SELECT Id, Status FROM oRunner";

	// Create an associative array to hold the runner id -> status information
	$runnerToOfficialStatusArray = array();

	// Execute the query
	$res = $linkEventDB->query($sql);

	// Loop through each runner
	while ($r = $res->fetch_assoc()) {

		// Map each runner's id to a status
		$runnerToOfficialStatusArray[$r['Id']] = $r['Status'];

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


	// ---------------------------------------------
	// Loop through each class and build the results
	// ---------------------------------------------

	// Create a variable to store the full results array
	$fullResults = array();

	foreach ($classArray as $clsid => $clsname) {

		// -----------------------------
		// Get the radios for this class
		// -----------------------------

		// Query the database for the radios relevant for this class
		$sql = "SELECT ctrl FROM mopClassControl WHERE id = " . $clsid . " AND cid = " . $cmp . " ORDER BY ord";

		// Execute the query
		$res = $linkMop->query($sql);
		
		// Add the radio control codes to an array
		$radioControlCodes = [];
		while ($r = $res->fetch_assoc()) {
			$code = (int)$r['ctrl'];
			array_push($radioControlCodes, $code);
		}

		// ---------------------------
		// Build the results SQL query
		// ---------------------------

		// CTE query
		$cteQuery = "WITH finishCte AS (SELECT MIN(rt) minRt FROM mopCompetitor WHERE cls = " . $clsid . " AND cid = " . $cmp . " AND stat = 1)";

		// SELECT query
		$selectQuery = "SELECT competitor.id AS id, competitor.name AS competitor, competitor.st AS startTime, org.name AS club, competitor.stat AS status, REVERSE(SUBSTRING_INDEX(REVERSE(competitor.name), ' ', 1)) AS lastName";

		// INNER JOIN query
		$innerJoinQuery = "";
		
		// Loop through each radio control code and build the needed parts of the query
		for ($i = 0; $i < count($radioControlCodes); $i++) {

			// Store the control code for ease of access
			$controlCode = $radioControlCodes[$i];

			// CTE query
			$cteQuery .= ", radio" . $i . "Cte AS (SELECT MIN(rt) minRt FROM mopRadio WHERE ctrl = " . $controlCode . " AND cid = " . $cmp . " AND id in (SELECT id FROM mopCompetitor WHERE cls = " . $clsid . " AND cid = " . $cmp . "))";

			// SELECT query
			$selectQuery .= ", radio" . $i . ".rt AS radio" . $i . "Time, 
			CASE WHEN radio" . $i . ".rt IS NOT NULL THEN RANK() OVER ( PARTITION BY (CASE WHEN radio" . $i . ".rt IS NOT NULL THEN 1 ELSE 0 END) ORDER BY radio" . $i . ".rt ) END radio" . $i . "Rank,
			radio" . $i . ".rt - (SELECT minRt FROM radio" . $i . "Cte) radio". $i . "Diff";

			$innerJoinQuery .= " LEFT JOIN mopRadio AS radio" . $i . " ON competitor.id = radio" . $i . ".id AND radio" . $i . ".ctrl = " . $radioControlCodes[$i];

		}

		// Put the finish time onto the selectQuery
		$selectQuery .= ", competitor.rt AS finishTime,
		CASE WHEN competitor.rt IS NOT NULL AND competitor.stat = 1 THEN RANK() OVER ( PARTITION BY (CASE WHEN competitor.rt IS NOT NULL AND competitor.stat = 1 THEN 1 ELSE 0 END) ORDER BY competitor.rt ) END finishRank,
		CASE WHEN competitor.rt <> 0 THEN competitor.rt - (SELECT minRt FROM finishCte) ELSE NULL END finishDiff";

		// Build the final query
		$sql = $cteQuery . " " . $selectQuery . " FROM mopCompetitor AS competitor LEFT JOIN mopOrganization AS org ON competitor.org = org.id AND competitor.cid = org.cid " . $innerJoinQuery . " WHERE competitor.cls = " . $clsid . " AND competitor.cid = " . $cmp . " ORDER BY FIELD (competitor.stat, 1, 0, 3, 4, 5, 6, 20, 21, 99), 
			CASE WHEN competitor.stat = 1 THEN competitor.rt END,
			CASE WHEN competitor.stat = 0 THEN competitor.st END,
			lastName";

		// print_r($sql);

		// -----------------
		// Execute the query
		// -----------------
		
		// Run the query
		$res = $linkMop->query($sql);
		
		// Store the result objects in an array
		$rows = array();

		// Loop through the results
		while ($r = $res->fetch_assoc()) {

			// Create the object
			$resultObj = array();

			// Populate with mandatory fields
			$resultObj['id'] = $r['id'];
			$resultObj['competitor'] = $r['competitor'];
			$resultObj['club'] = $r['club'];

			// Set the status to be whatever MOP gives us...
			$resultObj['status'] = $r['status'];

			// ...but we need to do some checking if the status is "1" OK
			if ($r['status'] == "1") {

				// Check the runner's official status from the event database
				if (array_key_exists($r['id'], $runnerToOfficialStatusArray)) {

					// Check if the status is 0
					if ($runnerToOfficialStatusArray[$r['id']] == "0") {

						// This means that the runner has finished (as MOP gives us a status of 1) but they have
						// not yet downloaded (as the event database status is 0) so give the runner our custom
						// status of "100" which indicates a finish, but no download
						$resultObj['status'] = "100";

					}

				}

			}			

			$resultObj['startTime'] = $r['startTime'];
			$resultObj['finishTime'] = formatAbsoluteTime($r['finishTime']);
			$resultObj['finishRank'] = $r['finishRank'];
			$resultObj['finishDiff'] = formatDiffTime($r['finishDiff']);

			// Create an array for radios
			$resultObj['radios'] = array();

			// Loop through each radio control
			for ($i = 0; $i < count($radioControlCodes); $i++) {

				$radioObject = array();
				$radioObject['code'] = $radioControlCodes[$i];
				$radioObject['time'] = formatAbsoluteTime($r['radio' . $i . 'Time']);
				$radioObject['rank'] = $r['radio' . $i . 'Rank'];
				$radioObject['diff'] = formatDiffTime($r['radio' . $i . 'Diff']);
				array_push($resultObj['radios'], $radioObject);

			}

			array_push($rows, $resultObj);

		}

		// Get the course id for this class
		$courseId = $classToCourseArray[$clsid];

		$classResults['clsId'] = $clsid;
		$classResults['clsName'] = $clsname;
		$classResults['course'] = $courseArray[$courseId]['name'];
		$classResults['length'] = $courseArray[$courseId]['length'];
		$classResults['radioCount'] = count($radioControlCodes);

		// Create an array with the radio information for the class
		$classResults['radioInfo'] = array();

		// Loop through each radio control
		for ($i = 0; $i < count($radioControlCodes); $i++) {

			// Get the control code
			$controlCode = $radioControlCodes[$i];

			// Calculate the distance as at the radio control

			// Leg distance
			$totalLegDistance = null;

			// Leg percentage
			$totalLegPercentage = null;

			// Check that the course exists
			if (array_key_exists($clsid, $classToCourseArray)) {

				// Get the leg distances array for this classes course
				$legDistanceArray = $courseArray[$courseId]['legDistances'];

				// Check if the control is in the control array distances (i.e. we have a distance for that leg)
				if (array_key_exists($controlCode, $legDistanceArray)) {

					// Leg distance
					$totalLegDistance = 0;

					// Loop through the leg distances, adding them up until we reach that control
					foreach ($legDistanceArray as $key => $value) {

						// Update totalLegDistance
						$totalLegDistance = $totalLegDistance + $value;

						// Check if we have reached the control
						if ($key == $controlCode) {
							break;
						}

					}

					// Calculate the percentage
					$totalLegPercentage = round($totalLegDistance / ($courseArray[$courseId]['length']) * 100);

				}

			}

			// Create an object with the radio information
			$radioObject = array();
			$radioObject['code'] = $controlCode;
			$radioObject['distance'] = $totalLegDistance;
			$radioObject['percentage'] = $totalLegPercentage;
			array_push($classResults['radioInfo'], $radioObject);

		}

		$classResults['clsResults'] = $rows;

		array_push($fullResults, $classResults);

	}		


	// Competition information
	$competitionResults['cmpName'] = $cmpName;
	$competitionResults['cmpDate'] = $cmpDate;

	// Marquee stuff
	$competitionResults['marquee'] = array();
	$competitionResults['marquee']['show'] = intval($marqueeShow);
	$competitionResults['marquee']['text'] = $marqueeText;
	$competitionResults['marquee']['duration'] = $marqueeDuration;

	// Competition results
	$competitionResults['cmpResults'] = $fullResults;

	// Calculate the hash of the competitionResults, allow the front end to track adn determine if there have been changes
	$hash = md5(serialize($competitionResults));

	// Add the hash to the results object
	$competitionResults['hash'] = $hash;

	// Return the JSON
	echo json_encode($competitionResults);

?>