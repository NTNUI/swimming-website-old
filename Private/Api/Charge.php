<?php

declare(strict_types=1);

require_once("Library/Util/Store.php");
require_once("Library/Util/Api.php");

use Stripe\Exception\ApiErrorException as StripeApiErrorException;


try {
	$response = new Response();
	$store = new Store("en");
	$data = json_decode(file_get_contents("php://input"), true, flags: JSON_THROW_ON_ERROR);

	// 3D secure charge
	if (isset($data["payment_intent_id"])) {
		$intent = $store->get_intent_by_id($data["payment_intent_id"]);
		$intent->confirm();
		
		log::message(json_encode($intent));

		if ($intent->status === "requires_action" && $intent->next_action->type === "use_stripe_sdk") {
			// 3d secure step 1: give client secret	
			$response->data = [
				"requires_action" => true,
				"payment_intent_client_secret" => $intent->client_secret,
			];
		} else if ($intent->status == "succeeded") {
			// 3d secure step 2: save successful order
			$store->finalize_order($intent["id"]);
			$response->data = [
				"success" => true,
				"error" => false,
				"message" => "Purchase succeeded.\nYou've been charged " . $intent["amount"] / 100 . " NOK",
			];
		} else {
			log::message("Error: payment intent with id: " . $intent["id"] . "returned unexpected value.", __FILE__, __LINE__);
			throw new \Exception("Unexpected payment intent status");
		}

		// non 3d secure charge
	} elseif (isset($data["payment_method_id"])) {
		// TODO: refactor create_order to accept order object
		$response->data = $store->create_order($data["product_hash"], $data["payment_method_id"], $data["customer"], $data["comment"] ?? NULL);
	} else {
		// todo: elseif customer and product attached: create a new paymentIntent, return client secret
		throw new \InvalidArgumentException("Missing parameter, Expected payment_intent_id or payment_method_id");
	}
	$response->code = HTTP_OK;
	$response->send();
	return;

} catch (StripeApiErrorException | \JsonException | \InvalidArgumentException | StoreException $e) {
	// Expected stripe errors that should be shown to users
	$response = new Response();
	$response->code = HTTP_INVALID_REQUEST;
	$response->data = [
		"error" => true,
		"success" => false,
		"message" => $e->getMessage(),
	];
	$response->send();
	return;

} catch (Exception $e) {
	// Unexpected errors on server
	log::message(json_encode($e), __FILE__, __LINE__);

	$response = new Response();
	$response->code = HTTP_INTERNAL_SERVER_ERROR;
	$response->data = [
		"error" => true,
		"success" => false,
		"message" => "Internal server error",
	];
	$response->send();
	return;
}
