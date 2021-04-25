<?php

class AccessControl {
	private $user, $rolerules;
	
	function __construct($username, &$mysqli = NULL) {
		$this->user = $username;
		if ($mysqli == NULL) {
			include_once("library/util/db.php");
			$mysqli = connect("web");
		}
		
		$this->get_rolerules($mysqli);

	}

	public function get_rolerules(&$mysqli) {
		$query = NULL;
		if ($this->user != "") {
			$sql = "SELECT role.type, role.page FROM role_access AS role JOIN users ON role.role = users.role WHERE users.username=?";
			$query = $mysqli->prepare($sql);
			$query->bind_param("s", $this->user);
		} else {
			//not logged in
			$sql = "SELECT role.type, role.page FROM role_access AS role JOIN roles ON role.role = roles.id WHERE roles.name=?";
			$query = $mysqli->prepare($sql);
			
			//Role for users without account
			$unregistered = "unregistered";
			$query->bind_param("s", $unregistered);
		}
		$query->execute();
		if(!$query){
			// querry failed to execure.
			print("Failed to execute querry in access control");
			die();
		}

		$type =  "";
		$pattern = "";
		$query->bind_result($type, $pattern);
		if(!$query){
			print("Failed to bind result in access control");
			die();
		}
		
		while ($query->fetch()) {
			$this->rolerules[] = array("type" => $type, "pattern" => $pattern);
		}
		$query->close();
	}

	public function can_access($page, $action = "") {
		$result = false;
		$matchlevel = 0;
		foreach ($this->rolerules as $rule) {
			$type = $rule["type"] == "ALLOW";
			$pattern = $rule["pattern"];
			//full path
			if ($pattern == "$page/$action" && $matchlevel < 4) {
				$result = $type;
				$matchlevel = 4;
			}
			if (strpos($pattern, "$page/") === 0  && fnmatch($pattern, "$page/$action") && $matchlevel < 3) {
				$result = $type;
				$matchlevel = 3;
			}
			//Top level match
			else if ($action == $pattern && $matchlevel < 2) {
				$result = $type;
				$matchlevel = 2;
			}
			//Wildcard match
			else if (fnmatch($pattern, "$page/$action") && $matchlevel < 1) {
				$result = $type;
				$matchlevel = 1;
			}
		}

		return $result;
	}

	public function log($page, $action, $value = NULL) {
		$username = $this->user;
		if ($this->user == "") $username = "~Unregistered User~";
		$sql = "INSERT INTO access_log (page, user, action, value) VALUES(?, ?, ?, ?)";

		include_once("library/util/db.php");
		$mysqli = connect("web");
		$query = $mysqli->prepare($sql);
		if(!$query){
			print("Failed to prepare query in access control");
			die();
		}

		$query->bind_param("ssss", $page, $username, $action, $value);
		if(!$query){
			print("Failed to bind parameters in access control");
			die();
		}

		$query->execute();
		if(!$query){
			print("Failed to execute query in access control");
			die();
		}

		$query->close();
		$mysqli->close();
	}

}

