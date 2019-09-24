<?php

	include_once('functions.php');

	header('Content-type: application/json');
	header('X-Content-Type-Options: nosniff');
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: GET,PUT,POST,DELETE');
	header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');


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


	// --------------------------------------
	// Get all the radios for the competition
	// --------------------------------------

	// Query the database for the competition information
	$sql = "SELECT id FROM mopControl WHERE cid = " . $cmp;

	// Execute the query
	$res = $linkMop->query($sql);

	// Create an array to store the radio ids
	$radioIdArray = array();

	// Loop through each control and add to the array
	while ($r = $res->fetch_assoc()) {
		array_push($radioIdArray, $r['id']);
	}


	// ---------------------------------------
	// Get all the classes for the competition
	// ---------------------------------------

	// Query the database for the competition information
	$sql = "SELECT id, name FROM mopClass WHERE cid = " . $cmp . " ORDER BY id ASC";

	// Execute the query
	$res = $linkMop->query($sql);

	// Create an array to store the classes
	$classArray = array();

	// Loop through each class and add to the array
	while ($r = $res->fetch_assoc()) {

		// Create an object to store the class details
		$classObject = array();
		$classObject['id'] = $r['id'];
		$classObject['name'] = $r['name'];

		// Add to the classArray
		array_push($classArray, $classObject);

	}


	// -------------------------------
	// Get all competitors ids to bibs
	// -------------------------------

	// Connect to the MeOS main database
	$linkMeosMain = @new mysqli(MYSQL_HOSTNAME, MYSQL_USERNAME, MYSQL_PASSWORD, $meosEventNameId);

	// Query the MeOS main database for a list of competitors in this competition
	$sql = "SELECT Id, Bib FROM oRunner ORDER BY Id";

	// Execute the query
	$res = $linkMeosMain->query($sql);

	// Create an array to store the details
	$competitorIdToBibArray = array();

	// Loop through each competitor and add to the array
	while ($r = $res->fetch_assoc()) {
		$competitorIdToBibArray[$r['Id']] = $r['Bib'];
	}

	// Close the connection to the database
	mysqli_close($linkMeosMain);


	// -------------------
	// Get all competitors
	// -------------------

	// Query the database for all competitors
	$sql = "SELECT competitor.id, competitor.name, org.name as clubName, cls.name AS className, competitor.st, competitor.rt, REVERSE(SUBSTRING_INDEX(REVERSE(competitor.name), ' ', 1)) AS lastName, REPLACE(competitor.name, REVERSE(SUBSTRING_INDEX(REVERSE(competitor.name), ' ', 1)), '') AS firstName FROM mopCompetitor AS competitor

			LEFT JOIN mopOrganization AS org ON competitor.org = org.id AND org.cid = ". $cmp ."

			LEFT JOIN mopClass AS cls ON competitor.cls = cls.id AND cls.cid = ". $cmp ."

			WHERE competitor.cid = ". $cmp ."

			ORDER BY competitor.cls ASC, lastname ASC";

	// Create an array to store the competitors
	$competitorsArray = array();

	// Execute the query
	$res = $linkMop->query($sql);

	// Loop through each competitor and add to the array
	while ($r = $res->fetch_assoc()) {

		// Create an object to store the competitor details
		$competitorObject = array();
		$competitorObject['id'] = $r['id'];
		$competitorObject['bib'] = $competitorIdToBibArray[$r['id']];
		$competitorObject['name'] = $r['name'];
		$competitorObject['firstName'] = $r['firstName'];
		$competitorObject['lastName'] = $r['lastName'];
		$competitorObject['club'] = $r['clubName'];
		$competitorObject['className'] = $r['className'];
		$competitorObject['startTime'] = $r['st'];
		$competitorObject['finishTime'] = $r['rt'];

		array_push($competitorsArray, $competitorObject);

	}


	// -----------------------
	// Build the JSON response
	// -----------------------

	// Competition information
	$responseArray['cmpName'] = $cmpName;
	$responseArray['cmpDate'] = $cmpDate;

	// Radio details
	$responseArray['radios'] = $radioIdArray;

	// Class details
	$responseArray['classes'] = $classArray;

	// Competitors
	$responseArray['competitors'] = $competitorsArray;

	// Return the JSON
	echo json_encode($responseArray);

?>