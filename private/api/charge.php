<?php
include_once("library/util/store_helper.php");
function handle_error($id, $error) {
	global $t;
	if (array_key_exists("message", $error)) $error = $error["message"];
	print(json_encode(["error" => $error]));
	exit();
}
$store = new StoreHelper("en");

$data = json_decode(file_get_contents("php://input"));

try {
	$src = "error";

	if (array_key_exists("payment_intent_id", $data)) {

		$intentId = $data->payment_intent_id;
		$intent = $store->get_intent_by_id($intentId);
		$intent->confirm();
		$src = $store->update_order($intent);
	} elseif (array_key_exists("payment_method_id", $data)) {
		$source = $data->payment_method_id;
		$api_id = $data->item_id;
		$owner = $data->owner;
		$kommentar = $data->kommentar;

		$t->load_translation("store");
		header("Content-Type", "applciation/json");

		$src = $store->create_order($api_id, $source, $owner, $kommentar);
	}
	echo(json_encode($src));
	exit();
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

