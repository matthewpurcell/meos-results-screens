<html>

	<head>

		<style>

			body {
				font-family: Arial;
			}

			table {
				border-collapse: collapse;
				font-size: 14px;
				margin-bottom: 50px;
			}

			tr#active {
				background-color: green;
				color: white;
				font-weight: bold;
			}

			td {
				border: 1px solid black;
				padding: 5px;
			}

			td:first-child {
				width: 50px;
				text-align: center;
			}

			td:first-child + td {
				width: 500px;
			}

			td:first-child + td + td {
				width: 150px;
			}

		</style>

	</head>

	<body>

<?php

	include_once('functions.php');

	// Connect to the MeOS defaults database
	$linkMeosDefaults = @new mysqli(MYSQL_HOSTNAME, MYSQL_USERNAME, MYSQL_PASSWORD, "meos-defaults");

	// Update the event defaults
	if (isset($_GET['setEvent']) || isset($_GET['setMop'])) {

		// Set the MeOS Event Database
		if (isset($_GET['setEvent'])) {

			$eventId = intval($_GET['setEvent']);
			$eventNameId = $_GET['setEventNameId'];

			$sql = "UPDATE defaultEvents SET value = ". $eventId .", data = '". $eventNameId ."' WHERE property = 'meosEventId'";
			$res = $linkMeosDefaults->query($sql);

		}

		// Set the MeOS MOP (Result Screens) Database
		if (isset($_GET['setMop'])) {

			$eventId = intval($_GET['setMop']);

			$sql = "UPDATE defaultEvents SET value = ". $eventId ." WHERE property = 'meosMopId'";
			$res = $linkMeosDefaults->query($sql);

		}

	}

	// ------------------------------
	// Get the current default events
	// ------------------------------

	// Query the MeOS main database for a list of competitions
	$sql = "SELECT property, value FROM defaultEvents";

	// Execute the query
	$res = $linkMeosDefaults->query($sql);

	// Variables to store the current defaults
	$meosEventId = -1;
	$meosMopId = -1;

	// Loop through each property
	while ($r = $res->fetch_assoc()) {

		if ($r['property'] == "meosEventId") {
			$meosEventId = $r['value'];
		}

		else if ($r['property'] == "meosMopId") {
			$meosMopId = $r['value'];
		}

	}

	// Close the connection to the database
	mysqli_close($linkMeosDefaults);


	// ----------------
	// List MeOS events
	// ----------------

	// Connect to the MeOS main database
	$linkMeosMain = @new mysqli(MYSQL_HOSTNAME, MYSQL_USERNAME, MYSQL_PASSWORD, "MeOSMain");

	// Query the MeOS main database for a list of competitions
	$sql = "SELECT Id, Name, Date, NameId FROM oEvent";

	// Execute the query
	$res = $linkMeosMain->query($sql);

?>

	<h1>MeOS Event Database</h1>

	<table>
		<tr>
			<th>Id</th><th>Name</th><th>Date</th>
		</tr>

<?php

	// Loop through each event
	while ($r = $res->fetch_assoc()) {

		echo ($r['Id'] == $meosEventId ? "<tr id='active'>" : "<tr>");
		echo "<td>" . $r['Id'] . "</td>";
		if ($r['Id'] == $meosEventId)
			echo "<td>" . $r['Name'] . "</td>";
		else
			echo "<td><a href='?setEvent=" . $r['Id'] . "&setEventNameId=" . $r['NameId'] . "'>" . $r['Name'] . "</a></td>";
		echo "<td>" . $r['Date'] . "</td>";
		echo "</tr>";

	}

	// Close the connection to the database
	mysqli_close($linkMeosMain);

?>

	</table>

<?php

	// ---------------
	// List MOP events
	// ---------------

	// Connect to the MOP database
	$linkMop = @new mysqli(MYSQL_HOSTNAME, MYSQL_USERNAME, MYSQL_PASSWORD, "meos-mop");

	// Query the MOP database for a list of competitions
	$sql = "SELECT cid, name, date FROM mopCompetition";

	// Execute the query
	$res = $linkMop->query($sql);
	
?>

	<h1>MeOS MOP (Results Screens) Database</h1>

	<table>
		<tr>
			<th>Id</th><th>Name</th><th>Date</th>
		</tr>

<?php

	// Loop through each event
	while ($r = $res->fetch_assoc()) {

		echo ($r['cid'] == $meosMopId ? "<tr id='active'>" : "<tr>");
		echo "<td>" . $r['cid'] . "</td>";
		if ($r['cid'] == $meosMopId)
			echo "<td>" . $r['name'] . "</a></td>";
		else
			echo "<td><a href='?setMop=" . $r['cid'] . "'>" . $r['name'] . "</a></td>";
		echo "<td>" . $r['date'] . "</td>";
		echo "</tr>";

	}

	// Close the connection to the database
	mysqli_close($linkMop);

?>

	</table>

	</body>

</html>