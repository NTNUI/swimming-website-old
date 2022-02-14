<?php
require_once("library/util/db.php");
if (!$access_control->can_access("api", "volunteer")) {
	log::forbidden("Access denied", __FILE__, __LINE__);
}
function setVolunteer($id, $value)
{
	$db = new DB("member");
	$sql = "UPDATE member SET `have_volunteered`=? WHERE ID=?";
	$db->prepare($sql);
	$db->bind_param("ii", $value, $id);
	$db->execute();
}

function getVolunteers($number)
{
	$db = new DB("member");
	$sql = "SELECT id, first_name, surname, phone, email, `have_volunteered` FROM member WHERE (`have_volunteered` IS NULL OR `have_volunteered`=0) AND approved_date IS NOT NULL ORDER BY IFNULL(`have_volunteered`, 1) ASC, RAND() LIMIT ?";
	$db->prepare($sql);
	$db->bind_param("i", $number);
	$db->execute();
	$db->stmt->bind_result($id, $first, $last, $phone, $email, $volunteer_status);
	$result = [];
	while ($db->fetch()) {
		$result[] = array(
			"id" => $id,
			"name" => "$first $last",
			"email" => $email,
			"phone" => $phone,
			"volunteer_status" => $volunteer_status
		);
	}

	return $result;
}

function search($name)
{
	// Search(string name)
	$db = new DB("member");
	$sql = "SELECT id, first_name, surname, phone, email, `have_volunteered` FROM member WHERE CONCAT(first_name, ' ', surname) LIKE CONCAT('%', ?, '%') AND approved_date > 0";

	$db->prepare($sql);
	$db->bind_param("s", $name);
	$db->execute();
	$db->stmt->bind_result($id, $first, $last, $phone, $email, $volunteer_status);
	$result = [];
	while ($db->fetch()) {
		$result[] = array(
			"id" => $id,
			"name" => "$first $last",
			"email" => $email,
			"phone" => $phone,
			"volunteer_status" => $volunteer_status
		);
	}
	return $result;
}

$getRandom = argsURL("GET", "getRandom");
$approve_id = argsURL("GET", "approve");
$reject_id = argsURL("GET", "reject");
$search = argsURL("GET", "search");

// @return string|NULL
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
	return NULL;
}

$action = getAction($getRandom, $approve_id, $reject_id, $search);
$result = "";
switch ($action) {
	case 'getRandom':
		// don't log search queries
		$result = getVolunteers($getRandom);
		break;
	case 'approve':
		$access_control->log("api/volunteer", $action, $approve_id);
		setVolunteer($approve_id, true);
		break;
	case 'reject':
		$access_control->log("api/volunteer", $action, $reject_id);
		setVolunteer($reject_id, false);
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
