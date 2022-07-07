<?php
declare(strict_types=1);

require_once("Library/Util/Store.php");
require_once("Library/Util/Api.php");

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

header("Content-Type: application/json");
try {
	$response = "error";

	// 3D secure charge
	if (isset($data->{"payment_intent_id"})) {	
		$intent = $store->get_intent_by_id($data->payment_intent_id);
		$intent->confirm();

		if ($intent->status == "requires_action" && $intent->next_action->type == "use_stripe_sdk") {
			// 3d secure step 1: give client secret	
			$response = [
				"requires_action" => true,
				"payment_intent_client_secret" => $intent->client_secret
			];
		} else if ($intent->status == "succeeded") {
			// 3d secure step 2: save successful order
			$store->finalize_order($intent["id"]);
			$response = [
				"success" => true,
				"error" => false,
				"message" => "Purchase succeeded.\nYou've been charged " . $intent["amount"] / 100 . " NOK"
			];
		} else {
			log::message("Error: payment intent with id: ". $intent["id"] . "returned unexpected value.", __FILE__, __LINE__);
			throw new Exception("Unexpected payment intent status");
		}

	// non 3d secure charge
	} elseif (isset($data->{"payment_method_id"})) {
		// TODO: refactor create_order to accept order object
		$response = $store->create_order($data->product_hash, $data->payment_method_id, $data->customer, $data->comment);

	} else {
		// todo: elseif customer and product attached: create a new paymentIntent, return client secret

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
	log::message($e->getTraceAsString(), $e->getFile(), $e->getLine());
	handle_error($e, 400);
	
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
