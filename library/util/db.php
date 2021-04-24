<?php 
function connect($database) {
	global $settings;

	$database = $settings["SQL_server"]["databases"][$database];
	$servername = $settings["SQL_server"]["servername"];
	$username = $settings["SQL_server"]["username"];
	$password = $settings["SQL_server"]["password"];

	if(!$database){
		print("Failed to retrieve database name");
		die();
	}
	$mysqli = mysqli_connect($servername, $username, $password, $database);
	if(!$mysqli){
		print("Failed to connect to database");
		die();

	}
	if (!$mysqli->set_charset("utf8")){
		print("Failed to set utf8 charset");
		die();
	}

	return $mysqli;
}
