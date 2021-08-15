<?php
if (!Authenticator::is_logged_in()) {
	log::forbidden("Access denied", __FILE__, __LINE__);
}

// Connect to server
$conn = connect("medlem");

$sql = "SELECT id, regdato, etternavn, fornavn, kommentar, epost FROM " . $settings["memberTable"] . " WHERE kontrolldato IS NULL OR YEAR(kontrolldato) <> YEAR(NOW()) ORDER BY regdato";
$query = $conn->prepare($sql);

if (!$query->execute()) {
	die("Connection failed: " . $query->error);
}
$query->bind_result($id, $registration_date, $surname, $name, $comment, $email);

$result = [];
while ($query->fetch()) {
	$surname = htmlspecialchars($surname);
	$name = htmlspecialchars($name);
	$comment = htmlspecialchars($comment);
	$email = htmlspecialchars($email);

	$interval = date_diff(date_create(), date_create($registration_date));
	$tid = "";
	if ($interval->y != 0) {
		$tid .= $interval->y . " år, ";
	}
	if ($interval->m != 0) {
		$tid .= $interval->m . " måneder, ";
	}
	$tid .= $interval->d . " dager";
	$result[] = array(
		"id" => $id,
		"fornavn" => $fornavn,
		"etternavn" => $etternavn,
		"epost" => $epost,
		"regdato" => $registration_date,
		"regdiff" => $tid,
		"kommentar" => $kommentar
	);
}
header("Content-Type: application/json");
print(json_encode($result));
