<?php

if (!$access_control->can_access("api/storeadmin")) {
    log::message("Access denied for " . Authenticator::get_username(), __FILE__, __LINE__);
    log::forbidden("Access denied", __FILE__, __LINE__);
}

if (!isset($_GET["type"])) {
	header("HTTP/1.0 404 Not found");
	die("No such request type");
}

include_once("library/util/db.php");
include_once("library/util/store_helper.php");

$store = new StoreHelper($language);
header("Content-Type: application/json");
switch ($_GET["type"]) {
case "mark_delivered":
	$item = $store->get_item($_REQUEST["item_id"]);
	if ($item == false) {
		print json_encode([
			"error" => "Item id not found",
		]);
		return;
	}
	$id = $_REQUEST["id"];

	$store->update_status($id, "DELIVERED");
	print json_encode([
		"success" => true,
	]);
	break;
case "set_visibility":
	$item = $store->get_item($_REQUEST["item_id"]);
	if ($item == false) {
		print json_encode([
			"error" => "Item id not found"
		]);
		return;
	}
	$visibility = filter_var($_REQUEST["visibility"], FILTER_VALIDATE_BOOLEAN);
	$store->set_visibility($item["id"], $visibility);
	print json_encode([
		"success" => true,
	]);
	break;
default:
	print json_encode([
		"error" => "type '" . $_GET["type"] . "' is not a type",
	]);
}
