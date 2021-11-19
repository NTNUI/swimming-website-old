<?php
include_once("library/util/store_helper.php");
function handle_error(string $id, string $error, &...$_)
{
	http_response_code(500);
	print(json_encode(["error" => true, "message" => $error, "id"=> $id, $_]));
	die();
}
$store = new StoreHelper("en");

$data = json_decode(file_get_contents("php://input"));

try {
	$src = "error";

	if (isset($data->{"payment_intent_id"})) {

		$intentId = $data->payment_intent_id;
		$intent = $store->get_intent_by_id($intentId);
		$intent->confirm();
		$src = $store->update_order($intent);
	} elseif (isset($data->{"payment_method_id"})) {
		$source = $data->payment_method_id;
		$store_item_hash = $data->store_item_hash;
		$owner = $data->owner;
		$comment = $data->comment;

		$t->load_translation("store");
		header("Content-Type", "application/json");

		$src = $store->create_order($store_item_hash, $source, $owner, $comment);
	}
	echo (json_encode($src));
	exit();
 } catch (\Stripe\Error\Card $e) {
	$body = $e->getJsonBody();
	$e = $body["error"];
	log::message($e->getJsonBody(), $e->getFile(), $e->getLine());
	handle_error($store_item_hash, $e);
	exit();
} catch (\Stripe\Error\Source $e) {
	$body = $e->getJsonBody();
	$e = $body["error"];
	log::message($e->getJsonBody(), $e->getFile(), $e->getLine());
	handle_error($store_item_hash, $e);
	exit();
} catch (\Stripe\Error\InvalidRequest $e) {
	$body = $e->getJsonBody();
	$e = $body["error"];
	log::message($e->getJsonBody(), $e->getFile(), $e->getLine());
	handle_error($store_item_hash, $e);
	exit();
} catch (Exception $e) {
	log::message($e->getMessage(), $e->getFile(), $e->getLine());
	handle_error($store_item_hash, $e->getMessage());
	exit();
}
