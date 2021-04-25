<?php 
// returns some sort of connection object on success and false on failure
function connect($database) {
	global $settings;

	$database = $settings["SQL_server"]["databases"][$database];
	$servername = $settings["SQL_server"]["servername"];
	$username = $settings["SQL_server"]["username"];
	$password = $settings["SQL_server"]["password"];

	if(!$database){
		print("Failed to retrieve database name");
		return false;
	}
	$mysqli = mysqli_connect($servername, $username, $password, $database);
	if(!$mysqli){
		print("Failed to connect to database");
		return false;

	}
	if (!$mysqli->set_charset("utf8")){
		print("Failed to set utf8 charset");
		return false;
	}

	return $mysqli;
}
