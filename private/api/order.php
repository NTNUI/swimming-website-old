<?php
include_once("library/util/store_helper.php");
function handle_error($id, $error) {
	global $t;
	if (array_key_exists("message", $error)) $error = $error["message"];
	$url = $t->get_url("store?item_id=$id&error=" . urlencode($error));
	header("Location: $url");
	exit();
}
$store = new StoreHelper("en");

$source = $_POST["source"];
$api_id = $_POST["id"];
$kommentar = $_POST["kommentar"];

$t->load_translation("store");

try {
	$src = $store->create_order($api_id, $source, $kommentar);
	if ($src["status"] == "chargeable") {
		$charge = $store->charge($src);
		header("Location: " . $t->get_url("checkout?source=" . $src["id"] . "&charge=" . $charge["id"]));
		exit();
	} else {
		header("Location: " . $src["redirect"]["url"]);
		exit();
	}

} catch (\Stripe\Error\Card $e) {
	$body = $e->getJsonBody();
	$e = $body["error"];
	handle_error($api_id, $e);
	exit();
} catch (\Stripe\Error\Source $e) {
	$body = $e->getJsonBody();
	$e = $body["error"];
	handle_error($api_id, $e);
	exit();
} catch (\Stripe\Error\InvalidRequest $e) {
	$body = $e->getJsonBody();
	$e = $body["error"];
	handle_error($api_id, $e);
	exit();
} catch (Exception $e) {
	handle_error($api_id, $t->get_translation($e->getMessage(), "store"));
	exit();
}

