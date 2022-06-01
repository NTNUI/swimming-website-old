<?php
require_once("library/util/api.php");

// require log in
if (!Authenticator::is_logged_in()) {
	log::forbidden("Access denied", __FILE__, __LINE__);
}

// check permissions
if (!$access_control->can_access("api", "isMember")) {
	log::message("Info: Access denied for " . Authenticator::get_username(), __FILE__, __LINE__);
	log::forbidden("Access denied", __FILE__, __LINE__);
}
$response = new Response();


// exit on missing params
$surname = $_GET["surname"];
if ($surname == NULL) {
	$response->error("surname parameter is not set");
	$response->send();
	return;
}

// get data
$db = new DB("member");
$sql = "SELECT first_name, surname FROM member WHERE YEAR(approved_date)=YEAR(now()) AND surname=? ORDER BY first_name, surname";
$db->prepare($sql);
$db->bind_param("s", $surname);
$db->execute();
$db->store_result();

if ($db->num_rows() === 0) {
	$response->error("User not found", HTTP_NOT_FOUND);
	$response->send();
	return;
}

$first_name;
$surname;
$result = [];
$db->stmt->bind_result($first_name, $surname);

// fetch result from database
while ($db->fetch()) {
	$result[] = ["first_name" => $first_name, "surname" => $surname];
}
$response->code = HTTP_OK;
$response->data = $result;
$response->send();