<?php
// TODO: Refactor this file

$result = array("error" => "Not implemented");
if (isset($_GET["register"])) {
	$username = $_GET["register"];
	$conn = connect("web");

	$query = $conn->prepare("SELECT id FROM users WHERE username=?");
	if (!$query) {
		log::die("could not prepare statement", __FILE__, __LINE__);
	}
	$query->bind_param("s", $username);
	if (!$query) {
		log::die("could not bind params", __FILE__, __LINE__);
	}
	$query->execute();
	if (!$query) {
		log::die("could not execute query", __FILE__, __LINE__);
	}
	$query->bind_result($id);
	if (!$query) {
		log::die("could not bind results", __FILE__, __LINE__);
	}
	if ($query->fetch()) {
		$query->close();
		// Generate friday
		$last_friday = date("N") == 5 ? "today" : "last friday";
		$friday = date("Y-m-d", strtotime($last_friday));
		$beer_query = $conn->prepare("SELECT 1 FROM friday_beer WHERE user_id=? AND date=?");
		if (!$beer_query) {
			log::die("could not prepare query", __FILE__, __LINE__);
		}
		$beer_query->bind_param("is", $id, $friday);
		if (!$beer_query) {
			log::die("could not bind params", __FILE__, __LINE__);
		}
		$beer_query->execute();
		if (!$beer_query) {
			log::die("could not execute query", __FILE__, __LINE__);
		}
		if (!$beer_query->fetch()) {
			$beer_query->close();

			$insert_query = $conn->prepare("INSERT INTO friday_beer (user_id, date) VALUES (?, ?)");
			if (!$beer_query) {
				log::die("could not prepare query", __FILE__, __LINE__);
			}
			$insert_query->bind_param("is", $id, $friday);
			if (!$beer_query) {
				log::die("could not bind params", __FILE__, __LINE__);
			}
			$insert_query->execute();
			if (!$beer_query) {
				log::die("could not execute query", __FILE__, __LINE__);
			}

			$insert_query->close();
			$access_control->log("api/friday_beer", "beered", $username);
			$result = array("success" => "ok");
		} else {
			$result = array("error" => "Already drank beer");
			$beer_query->close();
		}
	} else {
		$result = array("error" => "Username not found");
		$query->close();
	}
} else {
	$conn = connect("web");
	if (!$beer_query) {
		log::die("could not connect to database", __FILE__, __LINE__);
	}
	$query = $conn->prepare("SELECT users.username, beers.date FROM friday_beer as beers JOIN users ON users.id = beers.user_id WHERE role IN (2, 5, 6)");
	if (!$beer_query) {
		log::die("could not prepare query", __FILE__, __LINE__);
	}
	$query->execute();
	if (!$beer_query) {
		log::die("could not execute query", __FILE__, __LINE__);
	}
	$query->bind_result($username, $date);
	if (!$beer_query) {
		log::die("could not bind results", __FILE__, __LINE__);
	}
	$result = array();
	while ($query->fetch()) {
		$result[$username][] = $date;
	}
}
header("Content-Type: application/json");
print json_encode($result);
