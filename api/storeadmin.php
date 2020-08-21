<?php

if (!$access_control->can_access("api/storeadmin")) {
	header("HTTP/1.0 403 Permission denied");
	die("You do not have permission to access this function");
}

if (!isset($_GET["type"])) {
	header("HTTP/1.0 404 Not found");
	die("No such request type");
}

include_once("library/util/db.php");
include_once("library/util/store_helper_v2.php");

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
	$visibility = $_REQUEST["visibility"];
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
	/*
case "get_item":
	$item = $store->get_item($_POST["item_id"], true);
	$res = array();
	if ($item) {
		$res = $item;
	        $res["orders"] = array();	
		$sql = "SELECT name, email, phone FROM store_orders WHERE item_id=? AND order_status='FINALIZED'";
		$conn = connect("web");
		$query = $conn->prepare($sql);
		$query->bind_param("i", $item["id"]);
		$query->execute();
		$query->bind_result($name, $email, $phone);
		while ($query->fetch()) {
			$res["orders"][] = array(
				"name" => $name,
				"email" => $email,
				"phone" => $phone
			);
		}
	}
	print json_encode($res);
	break;
case "list_items":
	$items = $store->get_items();
	print json_encode($items);
	break;
}
