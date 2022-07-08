<?php
declare(strict_types=1);

require_once("Library/Util/Store.php");
require_once("Library/Util/Api.php");

$response = new Response();

try {
	switch ($_SERVER['REQUEST_METHOD']) {
		case "GET":
			inventory($response);
			break;
		default:
			$response->code = HTTP_INVALID_REQUEST;
			$response->data = [
				"error" => true,
				"message" => "unsupported request method : " . $_SERVER['REQUEST_METHOD'] . " Supported method are GET"
			];
	}
} catch (StoreException $ex) {
	$response->code = HTTP_INVALID_REQUEST;
	$response->data = [
		"error" => true,
		"message" => $ex->getMessage()
	];
} catch (\Exception $ex) {
	$response->code = HTTP_INTERNAL_SERVER_ERROR;
	$response->data = [
		"error" => true
	];
	// append crash information if logged in
	if (Authenticator::is_logged_in()) {
		$response->data = array_merge($response->data, [
			"message" => $ex->getMessage(),
			"trace" => $ex->getTraceAsString()
		]);
	} else {
		$response->data = array_merge($response->data, [
			"message" => "Something went wrong. Developers are alerted. We will look into this",
		]);
		log::message($ex->getMessage(), __FILE__, __LINE__);
		log::message($ex->getTraceAsString(), __FILE__, __LINE__);
	}

	$response->send();
	throw $ex;
}
$response->send();
return;

function inventory(Response &$response)
{
	$language = argsURL("GET", "language");
	if ($language !== "no" && $language !== "en") {
		$response->code = HTTP_INVALID_REQUEST;
		$response->data = ["success" => false, "error" => true, "message" => "Language parameter can only be one of 'en'|'no' "];
		return;
	}
	$store = new Store($language);
	$products = $store->get_products(0, 30, "", false, false); // get 30 products
	if ($products === false) {
		http_response_code(404);
		return;
	}
	foreach ($products as $i => $product) {
		if (array_key_exists("image", $product)) {
			$products[$i]["image"] = Settings::get_instance()->get_baseurl() . "/img/store/" . $product["image"];
		}
		$products[$i]["hash"] = $product["hash"];
		unset($products[$i]["id"]);
	}

	$response->data = $products;
	$response->code = HTTP_OK;
	return;
}
