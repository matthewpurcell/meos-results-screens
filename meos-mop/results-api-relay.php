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

		// ---------------------------
		// Build the results SQL query
		// ---------------------------

		// Build the final query
		$sql = "WITH
			finishCte AS (SELECT MIN(rt) minRt FROM mopTeam WHERE cls = ". $clsid ." AND stat = 1 AND cid = 10),
			leg1Cte AS (SELECT MIN(rt) minRT FROM mopCompetitor WHERE cid = 10 AND stat = 1 AND id IN (SELECT rid FROM mopTeamMember WHERE cid = 10 AND leg = 1)),
			leg2Cte AS (SELECT MIN(rt) minRT FROM mopCompetitor WHERE cid = 10 AND stat = 1 AND id IN (SELECT rid FROM mopTeamMember WHERE cid = 10 AND leg = 2)),
			leg3Cte AS (SELECT MIN(rt) minRT FROM mopCompetitor WHERE cid = 10 AND stat = 1 AND id IN (SELECT rid FROM mopTeamMember WHERE cid = 10 AND leg = 3))

		SELECT team.id AS teamId, team.name AS teamName, team.cls AS classId, team.stat AS status, team.st AS startTime,

		competitor1.rt AS leg1Time, competitor1.name AS leg1Runner, competitor1.id AS leg1RunnerId, competitor1.stat AS leg1Status,
		CASE WHEN competitor1.rt IS NOT NULL AND competitor1.stat = 1 THEN RANK() OVER ( PARTITION BY (CASE WHEN competitor1.rt IS NOT NULL AND competitor1.stat = 1 THEN 1 ELSE 0 END) ORDER BY competitor1.rt ) END leg1Rank, 
		CASE WHEN competitor1.rt <> 0 THEN competitor1.rt - (SELECT minRt FROM leg1Cte) ELSE NULL END leg1Diff, 

		competitor2.rt AS leg2Time, competitor2.name AS leg2Runner, competitor2.id AS leg2RunnerId, competitor2.stat AS leg2Status,
		CASE WHEN competitor2.rt IS NOT NULL AND competitor2.stat = 1 THEN RANK() OVER ( PARTITION BY (CASE WHEN competitor2.rt IS NOT NULL AND competitor2.stat = 1 THEN 1 ELSE 0 END) ORDER BY competitor2.rt ) END leg2Rank, 
		CASE WHEN competitor2.rt <> 0 THEN competitor2.rt - (SELECT minRt FROM leg2Cte) ELSE NULL END leg2Diff, 

		competitor3.rt AS leg3Time, competitor3.name AS leg3Runner, competitor3.id AS leg3RunnerId, competitor3.stat AS leg3Status,
		CASE WHEN competitor3.rt IS NOT NULL AND competitor3.stat = 1 THEN RANK() OVER ( PARTITION BY (CASE WHEN competitor3.rt IS NOT NULL AND competitor3.stat = 1 THEN 1 ELSE 0 END) ORDER BY competitor3.rt ) END leg3Rank, 
		CASE WHEN competitor3.rt <> 0 THEN competitor3.rt - (SELECT minRt FROM leg3Cte) ELSE NULL END leg3Diff,

		team.rt AS finishTime,
		CASE WHEN team.rt IS NOT NULL AND team.stat = 1 THEN RANK() OVER ( PARTITION BY (CASE WHEN team.rt IS NOT NULL AND team.stat = 1 THEN 1 ELSE 0 END) ORDER BY team.rt ) END finishRank,
		CASE WHEN team.rt <> 0 AND team.stat = 1 THEN team.rt - (SELECT minRt FROM finishCte) ELSE NULL END finishDiff

		FROM mopTeam AS team

		LEFT JOIN mopTeamMember AS teamMember1 ON team.id = teamMember1.id AND team.cid = teamMember1.cid AND teamMember1.leg = 1
		LEFT JOIN mopCompetitor AS competitor1 ON teamMember1.rid = competitor1.id AND teamMember1.cid = competitor1.cid

		LEFT JOIN mopTeamMember AS teamMember2 ON team.id = teamMember2.id AND team.cid = teamMember2.cid AND teamMember2.leg = 2
		LEFT JOIN mopCompetitor AS competitor2 ON teamMember2.rid = competitor2.id AND teamMember2.cid = competitor2.cid

		LEFT JOIN mopTeamMember AS teamMember3 ON team.id = teamMember3.id AND team.cid = teamMember3.cid AND teamMember3.leg = 3
		LEFT JOIN mopCompetitor AS competitor3 ON teamMember3.rid = competitor3.id AND teamMember3.cid = competitor3.cid

		WHERE team.cls = ". $clsid ."

		ORDER BY FIELD (team.stat, 1, 0, 3, 4, 5, 6, 20, 21, 99), team.rt";

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
			$resultObj['id'] = $r['teamId'];
			$resultObj['teamName'] = $r['teamName'];
			$resultObj['status'] = $r['status'];
			$resultObj['startTime'] = $r['startTime'];
			$resultObj['finishTime'] = formatAbsoluteTime($r['finishTime']);
			$resultObj['finishRank'] = $r['finishRank'];
			$resultObj['finishDiff'] = formatDiffTime($r['finishDiff']);

			// Create an array for the legs
			$resultObj['legs'] = array();

			// Loop through each leg control
			for ($i = 1; $i <= 3; $i++) {

				$legObject = array();
				$legObject['runnerId'] = $r['leg' . $i . 'RunnerId'];
				$legObject['time'] = formatAbsoluteTime($r['leg' . $i . 'Time']);
				$legObject['rank'] = $r['leg' . $i . 'Rank'];
				$legObject['diff'] = formatDiffTime($r['leg' . $i . 'Diff']);
				$legObject['status'] = $r['leg' . $i . 'Status'];
				array_push($resultObj['legs'], $legObject);

			}

			array_push($rows, $resultObj);

		}

		$classResults['clsId'] = $clsid;
		$classResults['clsName'] = $clsname;
		$classResults['legCount'] = 3;

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