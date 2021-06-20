<?php
// returns some sort of connection object on success and false on failure
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
function exec_sqli($server, $sql, $bind_params, &...$vars)
{
	$mysqli = connect($server);
	if (!$mysqli) {
		log::message("Failed to connect to database", __FILE__, __LINE__);
		log::message(mysqli_connect_error(), __FILE__, __LINE__);
		die();
	}
	$query = $mysqli->prepare($sql);
	if (!$query) {
		log::message("Failed to prepare querry", __FILE__, __LINE__);
		log::message(mysqli_connect_error(), __FILE__, __LINE__);
		die();
	}
	$query->bind_param($bind_params, $vars); // might need unwrapping
	if (!$query) {
		log::message("Failed to prepare querry", __FILE__, __LINE__);
		log::message(mysqli_connect_error(), __FILE__, __LINE__);
		die();
	}
	$query->execute();
	if (!$query) {
		log::message("Failed to prepare querry", __FILE__, __LINE__);
		log::message(mysqli_connect_error(), __FILE__, __LINE__);
		die();
	}
	// TODO: add bind result and shit
	$query->close();
	$mysqli->close();
}
