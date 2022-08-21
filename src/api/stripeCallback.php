<?php

/**
 * deprecated
 */

declare(strict_types=1);

// Documentation: https://stripe.com/docs/webhooks
require_once("Library/Util/Store.php");
require_once("Library/Util/Order.php");

use Stripe\PaymentIntent;
use libphonenumber\PhoneNumberUtil;

$response = new Response();
global $args;
try {
	if (argsURL("SERVER", "REQUEST_METHOD") !== "POST") {
		throw new \Exception("accepting only post methods");
	}
	$secret = $_ENV["STRIPE_SIGNING_KEY"];
	$data = file_get_contents("php://input");
	if ($data === false) {
		throw new \Exception();
	}
	$sigHeader = argsURL("SERVER", "HTTP_STRIPE_SIGNATURE");
	if (empty($sigHeader)) {
		throw new \InvalidArgumentException("signature invalid");
	}
	$event = \Stripe\Webhook::constructEvent(
		$data,
		$sigHeader,
		$secret
	);

	switch ($event["type"]) {
		case "source.canceled":
		case "charge.failed":
			Order::fromPaymentIntent(paymentIntent::retrieve($event["data"]["object"]["payment_intent"]))->setOrderStatus(OrderStatus::FAILED);
			$response->code = Response::HTTP_OK;
			break;
		case "charge.succeeded":
			Order::fromPaymentIntent(paymentIntent::retrieve($event["data"]["object"]["payment_intent"]))->setOrderStatus(OrderStatus::FINALIZED);
			if ($event["data"]["object"]["productHash"] === Settings::getInstance()->getLicenseProductHash()) {

				$phone = PhoneNumberUtil::getInstance()->parse($event["data"]["object"]["phone"]);
				Member::fromPhone($phone)->approveEnrollment();
			}
			$response->code = Response::HTTP_OK;
			break;
		default:
			$response->code = Response::HTTP_BAD_REQUEST;
			break;
	}
} catch (\Throwable $ex) {
	$response->code = Response::HTTP_INTERNAL_SERVER_ERROR;
	if (boolval(filter_var($_ENV["DEBUG"], FILTER_VALIDATE_BOOLEAN))) {
		$response->data["message"] = $ex->getMessage();
		$response->data["code"] = $ex->getCode();
		$response->data["file"] = $ex->getFile();
		$response->data["line"] = $ex->getLine();
		$response->data["args"] = $args;
		$response->data["backtrace"] = $ex->getTrace();
	}
}

$response->sendJson();
