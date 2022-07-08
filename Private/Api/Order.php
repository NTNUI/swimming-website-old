<?php
/* 
declare(strict_types=1);

require_once("Library/Util/Store.php");
function handle_error($id, $error)
{
	global $t;
	if (array_key_exists("message", $error)) $error = $error["message"];
	$url = $t->get_url("store?item_id=$id&error=" . urlencode($error));
	header("Location: $url");
	exit();
}
$store = new Store("en");

$source = $_POST["source"];
$hash = $_POST["id"]; //TODO:  update incoming requests to pass inn hash or product_hash
$comment = $_POST["kommentar"];

$t->load_translation("store");

try {
	// TODO: refactor create_order to accept one argument object $order
	$src = $store->create_order($hash, $source, $comment);
	if ($src["status"] == "chargeable") {
		$charge = $store->charge($src);
		header("Location: " . $t->get_url("checkout?source=" . $src["id"] . "&charge=" . $charge["id"]));
		exit();
	} else {
		header("Location: " . $src["redirect"]["url"]);
		exit();
	}
} catch (\Stripe\Error\Card $e) {
	$body = $e->getJsonBody();
	$e = $body["error"];
	handle_error($hash, $e);
	exit();
} catch (\Stripe\Error\Source $e) {
	$body = $e->getJsonBody();
	$e = $body["error"];
	handle_error($hash, $e);
	exit();
} catch (\Stripe\Error\InvalidRequest $e) {
	$body = $e->getJsonBody();
	$e = $body["error"];
	handle_error($hash, $e);
	exit();
} catch (Exception $e) {
	handle_error($hash, $t->get_translation($e->getMessage(), "store"));
	exit();
}
 */