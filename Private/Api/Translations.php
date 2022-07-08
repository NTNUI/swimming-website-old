<?php
declare(strict_types=1);

if (!$access_control->can_access("api", "translations")) {
    log::message("Info: Access denied for " . Authenticator::get_username(), __FILE__, __LINE__);
    log::forbidden("Access denied", __FILE__, __LINE__);
}

$page = $_REQUEST['page'];
$dir = Settings::get_instance()->get_translations_dir();

if (file_put_contents("$dir/$page.json", file_get_contents("php://input")) === false){
	log::message("Error: Could not save content to $page. Maybe you need to run 'chmod 774 translations/*.json'?", __FILE__, __LINE__);
}

header("Content-Type: application/json");
print json_encode(json_decode(file_get_contents("$dir/$page.json")));

log::message(Authenticator::get_username() . " has updated translations for $page", __FILE__, __LINE__);
