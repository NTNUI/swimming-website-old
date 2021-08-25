<?php
include_once("library/util/store_helper.php");
function handle_error($id, $error) {
	http_response_code(500);
	print(json_encode(["error"=> true, "message" => $error]));
	die();
}
$store = new StoreHelper("en");

$data = json_decode(file_get_contents("php://input"));

try {
	$src = "error";

	if( isset($data->{"payment_intent_id"})){

		$intentId = $data->payment_intent_id;
		$intent = $store->get_intent_by_id($intentId);
		$intent->confirm();
		$src = $store->update_order($intent);
	} elseif (isset($data->{"payment_method_id"})) {
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
	handle_error($api_id, $e->getMessage());
	exit();
}

