<?php

declare(strict_types=1);

require_once(__DIR__ . "/../Library/Util/Api.php");
require_once(__DIR__ . "/../Library/Util/Order.php");
require_once(__DIR__ . "/../Library/Util/Product.php");
require_once(__DIR__ . "/../Library/Util/Member.php");

use libphonenumber\PhoneNumberUtil;
use Stripe\Exception\ApiErrorException as StripeApiErrorException;
use Stripe\PaymentIntent;


$response = new Response();
try {
	\Stripe\Stripe::setApiKey($_ENV["STRIPE_SECRET_KEY"]);

	$file_content = file_get_contents("php://input");
	if ($file_content === false) {
		throw new \Exception("could not read php://input");
	}

	$request = json_decode($file_content, true, flags: JSON_THROW_ON_ERROR);

	$intent = new \Stripe\PaymentIntent();

	if (isset($request["payment_method_id"])) {


		if (!array_key_exists("order", $request)) {
			throw new \InvalidArgumentException("order is not set");
		}

		if (!array_key_exists("customer", $request["order"])) {
			throw new \InvalidArgumentException("customer is not set");
		}

		// stripe expects clients to send full name in name property. Everywhere else fullName is used.
		foreach (["name", "email"] as $key) {
			if (!array_key_exists($key, $request["order"]["customer"])) {
				throw new \InvalidArgumentException("missing $key inside order");
			}
		}

		if (!array_key_exists("productHash", $request["order"]["product"])) {
			throw new \InvalidArgumentException("productHash is not set");
		}
		$product = Product::fromProductHash($request["order"]["product"]["productHash"]);

		$phone = NULL;
		if ($request["order"]["customer"]["phone"]) {
			$phone = PhoneNumberUtil::getInstance()->parse($request["order"]["customer"]["phone"]);
		}
		$customer = new Customer($request["order"]["customer"]["name"], $request["order"]["customer"]["email"], $phone);

		$order = Order::new($customer, $product, $request["payment_method_id"], $request["order"]["comment"] ?? "", OrderStatus::PLACED);
		$intent = PaymentIntent::retrieve($order->intent_id);
	} elseif (isset($request["payment_intent_id"])) {

		$intent = PaymentIntent::retrieve($request["payment_intent_id"]);
		$intent->confirm();
	} else {
		throw new \InvalidArgumentException("payment_intent_id or payment_method_id needs to be set");
	}

	if ($intent["status"] === "requires_action" && $intent["next_action"]["type"] === "use_stripe_sdk") {

		$response->data = [
			"requires_action" => true,
			"payment_intent_client_secret" => $intent["client_secret"],
		];
	} else if ($intent["status"] === "succeeded") {

		Order::fromPaymentIntent(PaymentIntent::retrieve($intent))->setOrderStatus(OrderStatus::FINALIZED);
		$response->data = [
			"success" => true,
			"error" => false,
			"message" => "Purchase succeeded.\nYou've been charged " . $intent["amount"] / 100 . " NOK",
		];

		// membership approval hook
		if ($intent["metadata"]["productHash"] === Settings::getInstance()->getLicenseProductHash()) {
			Member::fromPhone(PhoneNumberUtil::getInstance()->parse($request["order"]["customer"]["phone"]))->approveEnrollment();
			$response->data["message"] .= "\nYour membership has been automatically approved.";
			$response->data["redirect_url"] = "https://ntnui.slab.com/posts/welcome-to-ntnui-swimming-%F0%9F%92%A6-44w4p9pv";
		}
	}

	$response->code = Response::HTTP_OK;
} catch (StripeApiErrorException | \JsonException |  InvalidArgumentException $ex) {
	$response->code = Response::HTTP_BAD_REQUEST;
	$response->data = [
		"error" => true,
		"success" => false,
		"message" => $ex->getMessage(),
		"trace" => $ex->getTrace(),
	];
} catch (\LogicException | \Throwable $ex) {
	$response->code = Response::HTTP_INTERNAL_SERVER_ERROR;
	$response->data = [
		"error" => true,
		"success" => false,
		"message" => "internal server error",
	];
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
