<?php
include_once("library/util/db.php");
if (!$access_control->can_access("api", "dugnad")) {
	log::forbidden("Access denied", __FILE__, __LINE__);
}
function setDugnad($id, $value)
{
	global $settings;
	$conn = connect("medlem");

	$sql = "UPDATE ${settings['memberTable']} SET `harUtførtFrivilligArbeid`=? WHERE ID=?";
	$query = $conn->prepare($sql);
	if (!$query) {
		log::die("Could not prepare statement", __FILE__, __LINE__);
	}
	$query->bind_param("ii", $value, $id);
	if (!$query) {
		log::die("Could not bind params", __FILE__, __LINE__);
	}
	$result = $query->execute();
	if (!$result) {
		log::message("Query failed to execute", __FILE__, __LINE__);
	}
	$query->close();
	$conn->close();
	return $result;
}

function getVolunteers($number)
{
	global $settings;
	$conn = connect("medlem");

	$sql = "SELECT id, fornavn, etternavn, phoneNumber, epost, `harUtførtFrivilligArbeid` FROM ${settings['memberTable']} WHERE (`harUtførtFrivilligArbeid` IS NULL OR `harUtførtFrivilligArbeid`=0) AND kontrolldato IS NOT NULL ORDER BY IFNULL(`harUtførtFrivilligArbeid`, 1) ASC, RAND() LIMIT ?";

	$query = $conn->prepare($sql);
	if (!$query) {
		log::die("Failed to prepare query", __FILE__, __LINE__);
	}

	$query->bind_param("i", $number);
	if (!$query) {
		log::die("Failed to bind params", __FILE__, __LINE__);
	}
	if (!$query->execute()) {
		log::die("Failed to execute query", __FILE__, __LINE__);
	}

	if (!$query->bind_result($id, $first, $last, $phone, $email, $dugnad)) {
		log::die("failed to bind result", __FILE__, __LINE__);
	}
	$result = [];
	while ($query->fetch()) {
		$result[] = array(
			"id" => $id,
			"name" => "$first $last",
			"email" => $email,
			"phone" => $phone,
			"dugnad" => $dugnad
		);
	}

	$query->close();
	$conn->close();
	return $result;
}

function search($name)
{
	// Search(string name)
	global $settings;
	$conn = connect("medlem");
	$sql = "SELECT id, fornavn, etternavn, phoneNumber, epost, `harUtførtFrivilligArbeid` FROM ${settings['memberTable']} WHERE CONCAT(fornavn, ' ', etternavn) LIKE CONCAT('%', ?, '%')";

	$query = $conn->prepare($sql);
	if (!$query) {
		log::die("Could not prepare statement", __FILE__, __LINE__);
	}
	$query->bind_param("s", $name);
	if (!$query) {
		log::die("Could not bind params", __FILE__, __LINE__);
	}
	$query->execute();
	if (!$query) {
		log::die("Could not execute query", __FILE__, __LINE__);
	}

	$query->bind_result($id, $first, $last, $phone, $email, $dugnad);
	if (!$query) {
		log::die("Could not bind results", __FILE__, __LINE__);
	}
	$result = [];
	while ($query->fetch()) {
		$result[] = array(
			"id" => $id,
			"name" => "$first $last",
			"email" => $email,
			"phone" => $phone,
			"dugnad" => $dugnad
		);
	}
	$query->close();
	$conn->close();
	return $result;
}

$getRandom = argsURL("GET", "getRandom");
$approve_id = argsURL("GET", "approve");
$reject_id = argsURL("GET", "reject");
$search = argsURL("GET", "search");

// @return string|null
function getAction($getRandom = 0, $approve_id = 0, $reject_id = 0, $search = "")
{
	if ($getRandom) {
		return "getRandom";
	}
	if ($approve_id) {
		return "approve";
	}
	if ($reject_id) {
		return "reject";
	}
	if ($search) {
		return "search";
	}
	return null;
}

$action = getAction($getRandom, $approve_id, $reject_id, $search);
switch ($action) {
	case 'getRandom':
		// don't log search queries
		$result = getVolunteers($getRandom);
		break;
	case 'approve':
		$access_control->log("api/dugnad", $action, $approve_id);
		$result = setDugnad($approve_id, true);
		break;
	case 'reject':
		$access_control->log("api/dugnad", $action, $reject_id);
		$result = setDugnad($reject_id, false);
		break;
	case 'search':
		// don't log search queries
		$result = search($search);
		break;

	default:
		return;
}

header("Content-type: application/json");
print(json_encode($result));
