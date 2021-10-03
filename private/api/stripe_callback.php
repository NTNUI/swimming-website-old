<?php
// Documentation: https://stripe.com/docs/webhooks
include_once("library/util/store_helper.php");

$store = new StoreHelper("en");

$secret = $settings["stripe"]["signing_key"];

$data = @file_get_contents("php://input");
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$event = null;

try {

	$event = \Stripe\Webhook::constructEvent(
		$data,
		$sig_header,
		$secret
	);
} catch (\UnexpectedValueException $e) {
	log::client_error("Bad Request", __FILE__, __LINE__);
	exit();
} catch (\Stripe\Error\SignatureVerification $e) {
	log::client_error("Wrong signature", __FILE__, __LINE__);
	exit();
}
try {
	switch ($event["type"]) {
		case "source.canceled":
		case "charge.failed":
			log::message("[INFO]: Charge failed", __FILE__, __LINE__);
			$store->fail_order($event["data"]["object"]);
			break;
		case "charge.succeeded":
			log::message("[INFO]: Charge succeeded", __FILE__, __LINE__);
			$store->finalize_order($event["data"]["object"]["payment_intent"]);
			break;
		default:
			log::message("[Warning]: Unhandled Stripe callback: " . $event["type"] , __FILE__, __LINE__);
			break;
	}
} catch (Exception $e) {
	log::message($e, __FILE__, __LINE__);
	print($e);
	http_response_code(500);
	return;
}

http_response_code(200);
