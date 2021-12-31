<?php
require_once("library/util/db.php");
class AccessControl
{
	private $user, $group_rules;

	function __construct($username)
	{
		$this->user = $username;
		$this->get_group_rules();
	}

	public function get_group_rules()
	{
		$db = new DB("web");
		if ($this->user != "") {
			$sql = "SELECT role.type, role.page FROM role_access AS role JOIN users ON role.role = users.role WHERE users.username=?";
			$db->prepare($sql);
			$db->bind_param("s", $this->user);
		} else {
			// not logged in
			$sql = "SELECT role.type, role.page FROM role_access AS role JOIN roles ON role.role = roles.id WHERE roles.name=?";
			$db->prepare($sql);
			// Role for users without account
			$unregistered = "unregistered";
			$db->bind_param("s", $unregistered);
		}
		$db->execute();

		$type =  "";
		$pattern = "";
		$db->stmt->bind_result($type, $pattern);
		while ($db->fetch()) {
			$this->group_rules[] = array("type" => $type, "pattern" => $pattern);
		}
	}

	public function can_access(string $page, $action): bool
	{
		// php 7.4 does not support assigning that input variable to "" yet.
		if (!isset($action)) {
			$action  = "";
		}
		$result = false;
		$match_level = 0;
		if ($this->group_rules == NULL) {
			log::die("role rules are null", __FILE__, __LINE__);
		}
		if ($page === "admin" && $action === "") {
			return true; // accept users to dashboard
		}
		foreach ($this->group_rules as $rule) {
			$type = $rule["type"] == "ALLOW";
			$pattern = $rule["pattern"];
			// full path
			if ($pattern === "$page/$action" && $match_level < 4) {
				$result = $type;
				$match_level = 4;
			}
			if (strpos($pattern, "$page/") === 0  && fnmatch($pattern, "$page/$action") && $match_level < 3) {
				$result = $type;
				$match_level = 3;
			}
			// Top level match
			else if ($action === $pattern && $match_level < 2) {
				$result = $type;
				$match_level = 2;
			}
			// Wildcard match
			else if (fnmatch($pattern, "$page/$action") && $match_level < 1) {
				$result = $type;
				$match_level = 1;
			}
		}

		return $result;
	}

	public function log(string $page, string $action, $value = NULL)
	{
		$username = $this->user;
		if ($this->user == "") $username = "~Unregistered User~";
		$sql = "INSERT INTO access_log (page, user, action, value) VALUES(?, ?, ?, ?)";
		$db = new DB("web");
		$db->prepare($sql);
		$db->bind_param("ssss", $page, $username, $action, $value);
		$db->execute();
	}
}
