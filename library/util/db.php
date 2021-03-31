<?php 
function connect($database) {

	global $settings;
	if($database == "web" || $database == "medlem")
	{
		$database = "svommer_$database";
	}

	$servername = $settings["SQL_server"]["servername"];
	$username = $settings["SQL_server"]["username"];
	$password = $settings["SQL_server"]["password"];

	$STUPIDCONSTANT = 3; // NEEDS TO BE FIXED SOME WAY
	for ($i=0; $i < $STUPIDCONSTANT; $i++) {
		if($settings["SQL_server"]["databases"][$i] == $database){
			$database = $settings["SQL_server"]["databases"][$i];
		}
	}

	$dbname = $database;
	if(!$dbname){
		print("error on connect() inside bd.php ");
		return;
	}

	$mysqli = mysqli_connect($servername, $username, $password, $dbname);
	$mysqli->set_charset("utf8");

	return $mysqli;
}
