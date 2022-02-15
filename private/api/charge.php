<?php
require_once("library/util/store.php");
define("DEBUG", false);
function handle_error(Throwable $th, int $code = HTTP_INTERNAL_SERVER_ERROR) 
{
	http_response_code($code);
	if (DEBUG) {
		print(json_encode(
			[
				"error" => true,
				"file" => $th->getFile(),
				"line" => $th->getLine(),
				"trace" => $th->getTraceAsString(),
				"message" => $th->getMessage()
			]
		));
	} else {
		print(json_encode(
			[
				"error" => true,
				"message" => $th->getMessage()
			]
		));
	}

	return;
}
$store = new Store("en");
$data = json_decode(file_get_contents("php://input"));

use Stripe\Exception\CardException as CardException;
use Stripe\Exception\RateLimitException as RateLimitException;
use Stripe\Exception\InvalidRequestException as InvalidRequestException;
use Stripe\Exception\AuthenticationException as AuthenticationException;
use Stripe\Exception\ApiConnectionException as ApiConnectionException;
use Stripe\Exception\ApiErrorException as ApiErrorException;

header("Content-Type", "application/json");
try {
	$response = "error";

	if (isset($data->{"payment_intent_id"})) {
		// Payment intent API

		$intentId = $data->payment_intent_id;
		$intent = $store->get_intent_by_id($intentId);
		$intent->confirm();
		$response = $store->update_order($intent);
	} elseif (isset($data->{"payment_method_id"})) {
		// deprecated. Using old "source" and "charge" API
		$source = $data->payment_method_id;
		$product_hash = $data->product_hash;
		$owner = $data->owner;
		// $comment = isset($data->comment) ? $data->comment : "";
		$t->load_translation("store");
		// $response = $store->create_order($product_hash, $source, $owner, $comment);
		$response = $store->create_order($product_hash, $source, $owner);
	} else {
		http_response_code(400);
		print(json_encode([
			"success" => false,
			"error" => true,
			"message" => "Missing parameter. Expected payment_intent_id or payment_method_id"
		]));
		return;
	}

	http_response_code(200);
	print(json_encode($response));
	return;
} catch (CardException | ApiErrorException | AuthenticationException | ApiConnectionException | RateLimitException | InvalidRequestException $e) {
	// Expected stripe errors that should be shown to users
	log::message($e->getError()->message . " Payment intent: " . $e->getError()->payment_intent->id, $e->getFile(), $e->getLine());
	handle_error($e, $e->getHttpStatus());
	
} catch (\StoreException $e) {
	// Expected client errors like product not found and stuff like that
	log::message($e->getMessage(), __FILE__, __LINE__);
	log::message($e->getTraceAsString(), __FILE__, __LINE__);
	handle_error($e, 400);

} catch (Exception $e) {
	// Unexpected errors on server
	log::message($e->getMessage(), __FILE__, __LINE__);
	log::message($e->getTraceAsString(), __FILE__, __LINE__);
	handle_error($e, 500);
}
