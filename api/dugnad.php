<?php 
session_start();
include_once("library/util/db.php");
if (!$access_control->can_access("api", "dugnad")) {
	header("HTTP/1.0 403 Forbidden");
	die("You do not have access to this page");
}
function setDugnad($id, $value) {
	global $settings;
	$conn = connect("member");
	$sql = "UPDATE ${settings['memberTable']} SET `harUtførtFrivilligArbeid`=? WHERE ID=?";
	$query = $conn->prepare($sql);
	$query->bind_param("ii", $value, $id);
	$result = $query->execute();
	$query->close();
	$conn->close();
	return $result;
}
if (isset($_GET["getRandom"]) && intval($_GET["getRandom"])) {
	include_once("library/util/db.php");

	$conn = connect("member");

	$sql = "SELECT id, fornavn, etternavn, phoneNumber, epost, `harUtførtFrivilligArbeid` FROM ${settings['memberTable']} WHERE (`harUtførtFrivilligArbeid` IS NULL OR `harUtførtFrivilligArbeid`=0) AND kontrolldato IS NOT NULL ORDER BY IFNULL(`harUtførtFrivilligArbeid`, 1) ASC, RAND() LIMIT ?";

	$query = $conn->prepare($sql);
	$query->bind_param("i", $_GET["getRandom"]);
	if (!$query->execute()) {
		print "error"; 
	}

	$query->bind_result($id, $first, $last, $phone, $email, $dugnad);
	$result = [];
	while($query->fetch()) {
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
	header("Content-type: application/json");
	print json_encode($result);
	return;
} else if (isset($_GET["approve"]) && intval($_GET["approve"]) != 0) {
	$access_control->log("api/dugnad", "approve", $_GET["approve"]);
	$result = setDugnad($_GET["approve"], 1);
	header("Content-type: application/json");
	print json_encode(array("success" => $result));
	return;

} else if (isset($_GET["reject"]) && intval($_GET["reject"]) != 0) {
	$access_control->log("api/dugnad", "reject", $_GET["reject"]);
	$result = setDugnad($_GET["reject"], 0);
	header("Content-type: application/json");
	print json_encode(array("success" => $result));
	return;

} else if (isset($_GET["search"]) && strlen($_GET["search"]) > 0) {
	$conn = connect("member");
	$sql = "SELECT id, fornavn, etternavn, phoneNumber, epost, `harUtførtFrivilligArbeid` FROM ${settings['memberTable']} WHERE CONCAT(fornavn, ' ', etternavn) LIKE CONCAT('%', ?, '%')";

	$query = $conn->prepare($sql);
	$query->bind_param("s", $_GET["search"]);
	$query->execute();

	$query->bind_result($id, $first, $last, $phone, $email, $dugnad);
	$result = [];
	while($query->fetch()) {
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
	header("Content-type: application/json");
	print json_encode($result);
	return;
}
