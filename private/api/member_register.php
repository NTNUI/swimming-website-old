<?php
require_once("library/util/member.php");
require_once("library/util/api.php");

$response = new Response();

if (!$access_control->can_access("api", "member_register")) {
	if (Authenticator::get_username()) {
		log::message("Info: Access denied for " . Authenticator::get_username(), __FILE__, __LINE__);
	} else {
		log::message("Info: Access denied", __FILE__, __LINE__);
	}
	$response->code = HTTP_FORBIDDEN;
	$response->data = ["error" => true, "message" => "Access denied"];
	$response->send();
	return;
}

$member_id = argsURL("REQUEST", "id");

// check missing argument
if ($member_id === NULL) {
	$response->code = HTTP_INVALID_REQUEST;
	$response->data = [
		"error" => true,
		"message" => "missing required argument id"
	];
	$response->send();
	return;
}

try {
	$phone = Member::get_phone($member_id);
	Member::approve($phone);

	$response->code = HTTP_OK;
	$response->data = ["success" => true, "message" => "user has been approved"];
} catch (\Throwable $th) {

	$response->code = HTTP_INTERNAL_SERVER_ERROR;
	$response->data = ["error" => true, "message" => "Internal server error"];
	log::message($th->getMessage(), __FILE__, __LINE__);
} finally {
	$response->send();
}
