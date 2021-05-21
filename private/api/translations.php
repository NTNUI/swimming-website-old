<?php
if (!$access_control->can_access("api", "translations")) {
	header("HTTP/1.0 403 Forbidden");
	die("You need to log in first");
}

$page = $_REQUEST['page'];
global $settings;
$dir = $settings["translations_dir"];

if (file_put_contents("$dir/$page.json", file_get_contents("php://input")) === false){
	log_message("Error: Could not save content to $page. Maybe you need to run 'chmod 774 translations/*.json'?", __FILE__, __LINE__);
}

header("Content-Type", "application/json");
print json_encode(json_decode(file_get_contents("$dir/$page.json")));

$access_control->log("api/translations", "edit", $page);
