<?php
declare(strict_types=1);

require_once("Library/Util/Member.php");
require_once("Library/Util/Api.php");

$response = new Response();

if (!$access_control->can_access("api", "member_register")) {
	if (Authenticator::get_username()) {
		log::message("Info: Access denied for " . Authenticator::get_username(), __FILE__, __LINE__);
	} else {
		log::message("Info: Access denied", __FILE__, __LINE__);
	}
	$response->code = HTTP_FORBIDDEN;
	$response->data = [
		"error" => true,
		"success" => false,
		"message" => "Access denied"
	];
	$response->send();
	return;
}

// check missing or failed argument
$filter_options = [
	"options" => [
		"min_range" => 1
	],
	"flags" => FILTER_NULL_ON_FAILURE
];
$member_id = filter_var(argsURL("REQUEST", "id"), FILTER_VALIDATE_INT, $filter_options);

if ($member_id === NULL) {
	$response->code = HTTP_INVALID_REQUEST;
	$response->data = [
		"error" => true,
		"success" => false,
		"message" => "required argument id is missing or malformed"
	];
	$response->send();
	return;
}

try {
	$phone = Member::get_phone($member_id);
	Member::approve($phone);

	$response->code = HTTP_OK;
	$response->data = [
		"success" => true,
		"success" => false,
		"message" => "user has been approved"
	];
} catch (\Throwable $th) {

	$response->code = HTTP_INTERNAL_SERVER_ERROR;
	$response->data = [
		"error" => true,
		"success" => false,
		"message" => "Internal server error"
	];
	log::message($th->getMessage(), __FILE__, __LINE__);
} finally {
	$response->send();
}
