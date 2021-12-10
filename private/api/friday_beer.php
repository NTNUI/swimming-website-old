<?php
// TODO: Refactor this file

$result = array("error" => "Not implemented");
if (isset($_GET["register"])) {
	$username = $_GET["register"];
	$db = new DB("web");

	$db->prepare("SELECT id FROM users WHERE username=?");
	$db->bind_param("s", $username);
	if (!$query) {
		log::die("could not bind params", __FILE__, __LINE__);
	}
	$db->execute();
	if (!$query) {
		log::die("could not execute query", __FILE__, __LINE__);
	}
	$db->stmt->bind_result($id);
	if ($db->fetch()) {
		$db->stmt->close();
		// Generate friday
		$last_friday = date("N") == 5 ? "today" : "last friday";
		$friday = date("Y-m-d", strtotime($last_friday));

		$db->prepare("SELECT 1 FROM friday_beer WHERE user_id=? AND date=?");
		$db->bind_param("is", $id, $friday);
		$db->execute();
		if (!$db->fetch()) {
			$db->stmt->close();
			$db->prepare("INSERT INTO friday_beer (user_id, date) VALUES (?, ?)");
			$db->bind_param("is", $id, $friday);
			$db->execute();
			$db->stmt->close();
			$access_control->log("api/friday_beer", "beered", $username);
			$result = array("success" => "ok");
		} else {
			$result = array("error" => "Already drank beer");
		}
	} else {
		$result = array("error" => "Username not found");
	}
} else {
	$db = new DB("web");
	$db->prepare("SELECT users.username, beers.date FROM friday_beer as beers JOIN users ON users.id = beers.user_id WHERE role IN (2, 5, 6)");
	$db->execute();
	$db->stmt->bind_result($username, $date);
	$result = array();
	while ($db->fetch()) {
		$result[$username][] = $date;
	}
}
header("Content-Type: application/json");
print json_encode($result);
