<?php
declare(strict_types=1);

// TODO: Refactor this file

$result = ["error" => "Not implemented"];
if (isset($_GET["register"])) {
	$username = $_GET["register"];
	$db = new DB("web");

	$db->prepare("SELECT id FROM users WHERE username=?");
	$db->bind_param("s", $username);
	$db->execute();
	$db->bind_result($id);
	if ($db->fetch()) {
		$db->close();
		// Generate friday
		$last_friday = date("N") == 5 ? "today" : "last friday";
		$friday = date("Y-m-d", strtotime($last_friday));

		$db->prepare("SELECT 1 FROM friday_beer WHERE user_id=? AND date=?");
		$db->bind_param("is", $id, $friday);
		$db->execute();
		if (!$db->fetch()) {
			$db->close();
			$db->prepare("INSERT INTO friday_beer (user_id, date) VALUES (?, ?)");
			$db->bind_param("is", $id, $friday);
			$db->execute();
			log::message($username . " was attending friday beer", __FILE__, __LINE__);
			$result = ["success" => "ok"];
		} else {
			$result = ["error" => "Already drank beer"];
		}
	} else {
		$result = ["error" => "Username not found"];
	}
} else {
	$db = new DB("web");
	$db->prepare("SELECT users.username, beers.date FROM friday_beer as beers JOIN users ON users.id = beers.user_id WHERE role IN (2, 5, 6)");
	$db->execute();
	$db->bind_result($username, $date);
	$result = [];
	while ($db->fetch()) {
		$result[$username][] = $date;
	}
}
header("Content-Type: application/json");
print json_encode($result);
