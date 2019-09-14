<?php

	include_once('functions.php');

	header('Content-type: application/json');
	header('X-Content-Type-Options: nosniff');
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: GET,PUT,POST,DELETE');
	header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');


	// ---------------------------------------------------------------
	// Check whether the competitor id and radio id has been passed in
	// ---------------------------------------------------------------

	$competitorId = '';
	$radioId = '';

	// Update the variables
	if (isset($_GET['competitorId']) && isset($_GET['radioId'])) {

		$competitorId = $_GET['competitorId'];
		$radioId = $_GET['radioId'];

	}

	// Return nothing if not provided
	else {

		return;

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


	// ----------------------------
	// Get the competitor's details
	// ----------------------------

	// Query the database for the competitor's information
	$sql = "SELECT competitor.name AS competitorName, competitor.id, competitor.cls AS clsId, class.name AS clsName, competitor.st, org.name AS clubName FROM mopCompetitor as competitor
	LEFT JOIN mopOrganization AS org ON competitor.org = org.id AND competitor.cid = org.cid
	LEFT JOIN mopClass AS class ON competitor.cls = class.id AND competitor.cid = class.cid
	WHERE competitor.id = ". $competitorId . " AND competitor.cid = " . $meosMopId;

	// Execute the query
	$res = $linkMop->query($sql);

	// Get the result
	$r = $res->fetch_assoc();

	// Create an array to hold the information
	$competitorInfo = array();

	// Populate the array
	$competitorInfo['name'] = $r['competitorName'];
	$competitorInfo['competitorId'] = $r['id'];
	$competitorInfo['clsId'] = $r['clsId'];
	$competitorInfo['clsName'] = $r['clsName'];
	$competitorInfo['startTime'] = $r['st'];
	$competitorInfo['club'] = $r['clubName'];


	// -----------------------------------------------------
	// Get the details for the radio controls on this course
	// -----------------------------------------------------


	// ** Get all radios for this class **

	// Query the database, order the radios
	$sql = "SELECT ctrl FROM mopClassControl WHERE id = " . $competitorInfo['clsId'] . " AND cid = " . $cmp . " ORDER BY ord";

	// Execute the query
	$res = $linkMop->query($sql);
	
	// Add the radio control codes to an array
	$radioControlCodes = [];
	while ($r = $res->fetch_assoc()) {
		$code = (int)$r['ctrl'];
		array_push($radioControlCodes, $code);
	}


	// ** Determine the course for this class **

	// Connect to the actual MeOS event database for this event
	$linkEventDB = @new mysqli(MYSQL_HOSTNAME, MYSQL_USERNAME, MYSQL_PASSWORD, $meosEventNameId);

	// Get the course for this class
	$sql = "SELECT Course FROM oClass WHERE Id = " . $competitorInfo['clsId'];
	$res = $linkEventDB->query($sql);
	$r = $res->fetch_assoc();
	$courseId = $r['Course'];


	// ** Determine the leg distances for this course and all its controls **

	// Query the database for the courses and distances
	$sql = "SELECT Controls, Length, Legs FROM oCourse WHERE Id = " . $courseId;
	$res = $linkEventDB->query($sql);
	$r = $res->fetch_assoc();
	$courseTotalLength = $r['Length'];

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

	// Close the connection to the database
	mysqli_close($linkEventDB);

	// Array to eventually hold the radio information
	$radioInfo = array();


	// -------------------------------------------------------
	// Get times of all competitors who have visited the radio
	// -------------------------------------------------------

	// Check whether we want the finish
	if ($radioId == "FINISH") {

		$sql = "WITH bestTimeCte AS (
			SELECT MIN(rt) minRt FROM mopCompetitor WHERE cid = ". $meosMopId ." AND cls = ". $competitorInfo['clsId'] ." AND rt > 0 AND stat = 1
		)

		SELECT competitor.id, competitor.rt, competitor.name AS competitorName, org.name AS clubName,
			competitor.rt - (SELECT minRt FROM bestTimeCte) as diff,
			CASE 
			    WHEN competitor.rt IS NOT NULL THEN RANK() OVER ( PARTITION 
			BY
			    (CASE 
			        WHEN competitor.rt IS NOT NULL THEN 1 
			        ELSE 0 
			    END)
			ORDER BY
			    competitor.rt ) 
			END radioRank

		FROM mopCompetitor AS competitor 

		LEFT JOIN mopOrganization AS org ON competitor.org = org.id AND competitor.cid = org.cid

		WHERE competitor.cid = ". $meosMopId ." AND competitor.cls = ". $competitorInfo['clsId'] ." AND competitor.rt > 0 AND competitor.stat = 1

		ORDER BY competitor.rt";

		// print_r($sql);

		// Run the query
		$res = $linkMop->query($sql);
			
		// Store the result objects in an array
		$rows = array();

		// Loop through the results
		while ($r = $res->fetch_assoc()) {

			// Create the object
			$resultObj = array();

			// Populate with the required data
			$resultObj['competitorId'] = $r['id'];
			$resultObj['name'] = $r['competitorName'];
			$resultObj['club'] = $r['clubName'];
			$resultObj['radioTime'] = $r['rt'];
			$resultObj['diff'] = $r['diff'];
			$resultObj['rank'] = $r['radioRank'];

			array_push($rows, $resultObj);

		}
	
		// Radio name (the number of the split control)
		$radioInfo["radioName"] = "Finish";
		$radioInfo["distance"] = $courseTotalLength;
		$radioInfo["percentage"] = "100";

	}

	// ...or a standard radio control
	else {

		// Query the database
		$sql = "WITH bestTimeCte AS (
			SELECT MIN(rt) minRt FROM mopRadio WHERE ctrl = ". $radioId . " AND cid = ". $meosMopId ." AND id IN (
				SELECT id FROM mopCompetitor WHERE cid = ". $meosMopId ." AND cls = ". $competitorInfo['clsId'] ."
			)
		)

		SELECT radio.id, radio.rt, competitor.name AS competitorName, org.name AS clubName, 
			radio.rt - (SELECT minRt FROM bestTimeCte) as diff,
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

		LEFT JOIN mopCompetitor AS competitor ON radio.id = competitor.id AND radio.cid = competitor.cid

		LEFT JOIN mopOrganization AS org ON competitor.org = org.id AND competitor.cid = org.cid

		WHERE radio.cid = ". $meosMopId ." AND radio.ctrl = ". $radioId. " AND radio.id IN (
			SELECT id FROM mopCompetitor WHERE cid = ". $meosMopId ." AND cls = ". $competitorInfo['clsId'] ."
		)

		ORDER BY radio.rt";

		// print_r($sql);

		// Run the query
		$res = $linkMop->query($sql);
			
		// Store the result objects in an array
		$rows = array();

		// Loop through the results
		while ($r = $res->fetch_assoc()) {

			// Create the object
			$resultObj = array();

			// Populate with the required data
			$resultObj['competitorId'] = $r['id'];
			$resultObj['name'] = $r['competitorName'];
			$resultObj['club'] = $r['clubName'];
			$resultObj['radioTime'] = $r['rt'];
			$resultObj['diff'] = $r['diff'];
			$resultObj['rank'] = $r['radioRank'];

			array_push($rows, $resultObj);

		}
	
		// Radio name (the number of the split control)
		$radioInfo["radioName"] = "Split " . (array_search($radioId, $radioControlCodes) + 1);

		// Calculate the distance as at the radio control

		// Leg distance
		$totalLegDistance = null;

		// Leg percentage
		$totalLegPercentage = null;

		// Check if the control is in the control array distances (i.e. we have a distance for that leg)
		if (array_key_exists($radioId, $legDistanceArray)) {

			// Leg distance
			$totalLegDistance = 0;

			// Loop through the leg distances, adding them up until we reach that control
			foreach ($legDistanceArray as $key => $value) {

				// Update totalLegDistance
				$totalLegDistance = $totalLegDistance + $value;

				// Check if we have reached the control
				if ($key == $radioId) {
					break;
				}

			}

			// Calculate the percentage
			$totalLegPercentage = round($totalLegDistance / ($courseTotalLength) * 100);

		}

		$radioInfo["distance"] = $totalLegDistance;
		$radioInfo["percentage"] = $totalLegPercentage;

	}

	// -----------------------
	// Build the JSON response
	// -----------------------

	// Competition information
	$responseArray['cmpName'] = $cmpName;
	$responseArray['cmpDate'] = $cmpDate;

	// Radio information
	$responseArray['radioInfo'] = $radioInfo;

	// Competitor information
	$responseArray['competitor'] = $competitorInfo;

	// Radio results
	$responseArray['radioResults'] = $rows;

	// Return the JSON
	echo json_encode($responseArray);

?>