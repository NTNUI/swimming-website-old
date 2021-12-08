<?php

// require log in
if (!Authenticator::is_logged_in()) {
	log::forbidden("Access denied", __FILE__, __LINE__);
}

// check permissions
if (!$access_control->can_access("api", "isMember")) {
	log::message("Info: Access denied for " . Authenticator::get_username(), __FILE__, __LINE__);
	log::forbidden("Access denied", __FILE__, __LINE__);
}

header("Content-Type: application/json; charset=UTF-8");

// exit on missing params
$surname = $_GET["surname"];
if ($surname == NULL) {
	http_response_code(400);
	return;
}

// get data
$db = new DB("member");
$sql = "SELECT fornavn, etternavn FROM ${settings['memberTable']} WHERE YEAR(kontrolldato)=YEAR(now()) AND etternavn=? ORDER BY fornavn, etternavn";
$db->prepare($sql);
$db->bind_param("s", $surname);
$db->execute();
$db->store_result();

if ($db->num_rows() === 0) {
	http_response_code(404);
	return;
}

$first_name;
$surname;
$result = [];
$db->bind_result($first_name, $surname);

// fetch result from database
while ($db->fetch()) {
	$result[] = array("first_name" => $first_name, "surname" => $surname);
}

// encode the result
$encoded_json = json_encode($result);
if ($encoded_json === false) {
	log::die("Failed to encode json", __FILE__, __LINE__, true);
}

// return valid response
http_response_code(200);
print($encoded_json);
