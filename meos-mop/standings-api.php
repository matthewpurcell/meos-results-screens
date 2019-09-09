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

	// Update the variables
	if (isset($_GET['radioId'])) {
		$radioId = $_GET['radioId'];
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


	// ------------------------------------------
	// Get the course information for all classes
	// ------------------------------------------

	// Connect to the actual MeOS event database for this event
	$linkEventDB = @new mysqli(MYSQL_HOSTNAME, MYSQL_USERNAME, MYSQL_PASSWORD, $meosEventNameId);

	// Query the database for the courses and distances
	$sql = "SELECT Id, Name, Length FROM oCourse";

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


	// ---------------------------------------------
	// Loop through each class and build the results
	// ---------------------------------------------

	// Create a variable to store the full results array
	$fullResults = array();

	foreach ($classArray as $clsid => $clsname) {

		// If no radio id has been passed in, then we do the standings at the finish
		if ($radioId == null) {

			$sql = "WITH bestTimeCte AS (
				SELECT MIN(rt) minRt FROM mopCompetitor WHERE cid = ". $meosMopId ." AND cls = ". $clsid ." AND rt > 0
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

			WHERE competitor.cid = ". $meosMopId ." AND competitor.cls = ". $clsid ." AND competitor.rt > 0

			ORDER BY competitor.rt";

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
				$resultObj['time'] = $r['rt'];
				$resultObj['diff'] = $r['diff'];
				$resultObj['rank'] = $r['radioRank'];

				array_push($rows, $resultObj);

			}

		}

		// Get the course id for this class
		$courseId = $classToCourseArray[$clsid];

		// Create an object for this class
		$clsObj = array();
		$clsObj["clsId"] = $clsid;
		$clsObj["clsName"] = $clsname;
		$clsObj["course"] =  $courseArray[$courseId]['name'];
		$clsObj["length"] = $courseArray[$courseId]['length'];
		$clsObj["clsResults"] = $rows;

		// Add to the fullResults object
		array_push($fullResults, $clsObj);

	}

	// -----------------------
	// Build the JSON response
	// -----------------------

	$responseArray = array();

	// Competition information
	$responseArray['cmpName'] = $cmpName;
	$responseArray['cmpDate'] = $cmpDate;

	// Results
	$responseArray['cmpResults'] = $fullResults;

	// Return the JSON
	echo json_encode($responseArray);

?>