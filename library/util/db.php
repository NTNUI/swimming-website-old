<?php
// returns some sort of connection object on success and false on failure
// TODO: look into https://github.com/ThingEngineer/PHP-MySQLi-Database-Class
use function PHPSTORM_META\type;

function connect($database)
{
	global $settings;
	mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_INDEX); // TODO: remove MYSQLI_REPORT_INDEX. right now it is crashing some parts in access_control. They need to be fixed first.

	$database = $settings["SQL_server"]["databases"][$database];
	$servername = $settings["SQL_server"]["servername"];
	$username = $settings["SQL_server"]["username"];
	$password = $settings["SQL_server"]["password"];

	if (!$database) {
		log::message("Failed to retrieve database name", __FILE__, __LINE__);
		return false;
	}
	$mysqli = mysqli_connect($servername, $username, $password, $database);
	if (!$mysqli) {
		log::message("Failed to connect to database", __FILE__, __LINE__);
		log::message(mysqli_connect_error(), __FILE__, __LINE__);
		return false;
	}
	if (!$mysqli->set_charset("utf8")) {
		log::message("Failed to set utf8 charset", __FILE__, __LINE__);
		log::message(mysqli_error($mysqli), __FILE__, __LINE__);
		return false;
	}
	return $mysqli;
}

// TODO: test this function. If it works, refactor like crazy
// Execute a command on a database. Value are stored in &$vars
function exec_sqli($server, $sql, $bind_params, &...$result)
{
	$conn = connect($server);
	if (!$conn) {
		log::die("Failed to connect to database " . mysqli_connect_error(), __FILE__, __LINE__);
	}
	$query = $conn->prepare($sql);
	if (!$query) {
		log::die("Failed to prepare query " . mysqli_error($conn), __FILE__, __LINE__);
	}
	$type = (gettype($bind_params))[0];
	$query->bind_param($type, $bind_params); // might need unwrapping
	if (!$query) {
		log::die("Failed to bind_params query " . mysqli_error($conn), __FILE__, __LINE__);
	}
	$query->execute();
	if (!$query) {
		log::die("Failed to execute query " . mysqli_error($conn), __FILE__, __LINE__);
	}
	$query->bind_result($result);
	// TODO: add bind result and shit
	$query->close();
	$conn->close();
}
