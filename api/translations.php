<?php
if (!$access_control->can_access("api", "translations")) {
	header("HTTP/1.0 403 Forbidden");
	die("You need to log in first");
}

$page = $_REQUEST['page'];
file_put_contents("$translations_dir/$page.json", file_get_contents("php://input"));

header("Content-Type", "application/json");
print json_encode(json_decode(file_get_contents("$translations_dir/$page.json")));

$access_control->log("api/translations", "edit", $page);
