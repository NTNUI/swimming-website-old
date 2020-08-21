<?php
include_once("library/util/store_helper.php");

$store = new StoreHelper("en");

//if(1){
//	$secret = $settings["stripe"]["secret_key"];
//}else {
	$secret = "whsec_Xkpj5LTAjDtrW3kN5hcNX1412njJfoTr";
//}



$data = @file_get_contents("php://input");
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$event = null;

try {

	$event = \Stripe\Webhook::constructEvent(
		$data, $sig_header, $secret);
} catch(\UnexpectedValueException $e) {
	  // Invalid payload
	http_response_code(400); // PHP 5.4 or greater
	print "Invalid payload";
	exit();
} catch (\Stripe\Error\SignatureVerification $e) {
	http_response_code(400);
	print "Wrong signature";
	exit();
}
try {
	switch($event["type"]) {
		case "source.chargeable":
			$store->charge($event["data"]["object"]);
			print "Charge created";
			break;
		case "source.failed":
		case "source.canceled":
		case "charge.failed":
			$store->fail_order($event["data"]["object"]);
			break;
		case "charge.succeeded":
			$store->finalize_order($event["data"]["object"]);
			print "Purchase recoreded";
			break;
	}
} catch (Exception $e) {
	var_dump($e);
	exit();
}

http_response_code(200);
