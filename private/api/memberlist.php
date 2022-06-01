<?php
if (!Authenticator::is_logged_in()) {
	log::forbidden("Access denied", __FILE__, __LINE__);
}
global $access_control;
if (!$access_control->can_access("api", "memberlist")) {
	log::forbidden("Access denied", __FILE__, __LINE__);
}

// Connect to server
$db = new DB("member");

$sql = "SELECT id, registration_date, surname, first_name, email FROM member WHERE approved_date IS NULL OR YEAR(approved_date) <> YEAR(NOW()) ORDER BY registration_date";
$db->prepare($sql);
$db->execute();

$db->stmt->bind_result($id, $registration_date, $surname, $name, $email);

$result = [];
while ($db->fetch()) {
	$surname = htmlspecialchars($surname);
	$first_name = htmlspecialchars($first_name);
	$email = htmlspecialchars($email);

	$interval = date_diff(date_create(), date_create($registration_date));
	$registration_diff = "";
	if ($interval->y != 0) {
		$registration_diff .= $interval->y . " year, ";
	}
	if ($interval->m != 0) {
		$registration_diff .= $interval->m . " month, ";
	}
	$registration_diff .= $interval->d . " days";
	$result[] = [
		"id" => $id,
		"first_name" => $first_name,
		"surname" => $surname,
		"email" => $epost,
		"registration_date" => $registration_date,
		"registration_diff" => $registration_diff,
	];
}
header("Content-Type: application/json");
print(json_encode($result));
