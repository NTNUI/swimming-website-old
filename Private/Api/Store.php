<?php

declare(strict_types=1);

// Available services:
// [GET] get_product(product_id)
// [GET] get_products()
// [GET] get_groups()
// [POST] add_product(name_no, name_en, description_no, description_en, price, amount, start_date, start_time, image)
// [PATCH] set_order_status(order_id, status)
// [PATCH] set_product_visibility(product_id, visibility)

require_once("Library/Exceptions/Store.php");
require_once("Library/Util/Api.php");
require_once("Library/Util/Db.php");
require_once("Library/Util/Store.php");

$response = new Response();
$store = new Store($language);
$input = file_get_contents("php://input");

try {
	switch ($_SERVER['REQUEST_METHOD']) {
		case "GET":
			handle_get($store, $response);
			break;
		case "POST":
			handle_post($response);
			break;
		case "PATCH":
			handle_patch($store, $input, $response);
			break;
		default:
			$response->code = HTTP_INVALID_REQUEST;
			$response->data = [
				"error" => true,
				"message" => "unsupported request method : " . $_SERVER['REQUEST_METHOD'] . " Supported methods are GET, POST, PATCH"
			];
	}
} catch (StoreException | \JsonException $ex) {
	$response->code = HTTP_INVALID_REQUEST;
	$response->data = [
		"error" => true,
		"success" => false,
		"message" => $ex->getMessage(),
	];
} catch (\Exception $ex) {
	$response->code = HTTP_INTERNAL_SERVER_ERROR;
	$response->data = [
		"error" => true,
		"success" => false,
	];
	// append crash information if logged in
	if (Authenticator::is_logged_in()) {
		$response->data = array_merge($response->data, [
			"message" => $ex->getMessage(),
			"trace" => $ex->getTraceAsString(),
		]);
	} else {
		$response->data = array_merge($response->data, [
			"message" => "Something went wrong. Developers are alerted. We will look into this",
		]);
	}

	$response->send();
	throw $ex;
}
$response->send();
return;

/**
 * Handle PATCH requests.
 *
 * @param Store $store
 * @param string $input
 * @param Response $response
 * @return void
 */
function handle_patch(Store &$store, string $input, Response &$response)
{
	Authenticator::auth_API("api/store", "", __FILE__, __LINE__);
	if (!$input) {
		$response->code = HTTP_INVALID_REQUEST;
		$response->data = [
			"error" => true,
			"success" => false,
			"message" => "No input provided for " . $_SERVER['REQUEST_METHOD'] . " request",
		];
		$response->send();
		return;
	}
	$input_json = json_decode($input, true, flags: JSON_THROW_ON_ERROR);
	if (!array_key_exists("request_type", $input_json)) {
		throw new \InvalidArgumentException("recieved json object that does not contain required key");
	}
	if (!array_key_exists("params", $input_json)) {
		throw new \InvalidArgumentException("missing 'params' inside input_json");
	}

	switch ($input_json["request_type"]) {
		case 'update_delivered':
			if (!array_key_exists("order_id", $input_json["params"])) {
				throw new \InvalidArgumentException("missing 'order_id' inside 'params'");
			}
			if (empty($input_json["params"]["order_id"])) {
				throw new \InvalidArgumentException("'order_id' is empty");
			}
			if (!Store::order_id_exists($input_json["params"]["order_id"])) {
				throw new OrderNotFoundException();
			}
			if (!in_array($input_json["params"]["order_status"], ["DELIVERED", "FINALIZED"])) {
				throw new \InvalidArgumentException("order_status can only be DELIVERED or FINALIZED. Got " . $input_json["params"]["order_status"]);
			}
			$order_id = $input_json["params"]["order_id"];
			$order_status = $input_json["params"]["order_status"];
			$store->set_order_status($order_id, $order_status);
			break;
		case 'update_visibility':
			if (empty($input_json["params"]["product_hash"])) {
				throw new \InvalidArgumentException("'product_hash' is empty");
			}
			$product_hash = $input_json["params"]["product_hash"];
			$product = $store->get_product($product_hash);
			$visibility = filter_var($input_json["params"]["visibility"], FILTER_VALIDATE_BOOLEAN);
			$store->set_product_visibility($product["id"], $visibility);
			break;
		case 'update_availability':
			if (empty($input_json["params"]["product_hash"])) {
				throw new \InvalidArgumentException("'product_hash' is empty");
			}
			$product_hash = $input_json["params"]["product_hash"];
			$product = [];
			$product = $store->get_product($product_hash);
			// construct DateTime object
			$format = "d.m.Y, H:i:s"; // https://www.php.net/manual/en/datetime.createfromformat.php
			$date_start = new DateTime;
			if (array_key_exists("date_start", $input_json["params"])) {
				$date_start = DateTime::createFromFormat($format, $input_json["params"]["date_start"], new DateTimeZone("Europe/Oslo"));
			} else {
				$date_start = NULL;
			}

			$date_end = new DateTime;
			if (array_key_exists("date_end", $input_json["params"])) {
				$date_end = DateTime::createFromFormat($format, $input_json["params"]["date_end"], new DateTimeZone("Europe/Oslo"));
			} else {
				$date_end = NULL;
			}
			// save result and return
			Store::update_product_date($product_hash, $date_start, $date_end);
			break;
		case "update_price":
			if (!isset($input_json["params"]["product_hash"])) {
				throw new InvalidArgumentException("missing product_hash");
			}
			if (!isset($input_json["params"]["price"])) {
				throw new InvalidArgumentException("missing price");
			}
			// get product
			$product_hash = $input_json["params"]["product_hash"];
			$product = [];
			$product = $store->get_product($product_hash);

			$price = $input_json["params"]["price"];
			Store::update_price($product_hash, $price);
			break;

		case "product_inventory_count":
			if (empty($input_json["params"]["product_hash"])) {
				throw new \InvalidArgumentException("'product_hash' is empty");
			}
			if (empty($input_json["params"]["new_inventory_count"])) {
				throw new \InvalidArgumentException("'new_inventory_count' is empty");
			}

			$new_inventory_count = $input_json["params"]["new_inventory_count"];
			$new_inventory_count = filter_var($new_inventory_count, FILTER_VALIDATE_INT, ["flags" => FILTER_NULL_ON_FAILURE]);
			if ($new_inventory_count === NULL) {
				throw new InvalidArgumentException("argument new_inventory_count is not an integer");
			}
			$product_hash = $input_json["params"]["product_hash"];
			Store::update_inventory_count($product_hash, $new_inventory_count);
			break;
		default:
			$response->error("Got invalid request: '" . $input_json["request_type"] . "'. Valid requests are 'update_delivered', 'update_visibility', 'update_availability', 'update_price', 'product_inventory_count'");
	}
	$response->code = HTTP_OK;
	$response->data = [
		"error" => false,
		"success" => true
	];
}


/**
 * Handle POST requests
 *
 * @param Response $response
 * @return void
 */
function handle_post(Response &$response): void
{
	Authenticator::auth_API("api/store", "", __FILE__, __LINE__);
	// Get these arguments, throw on unexpected arguments
	$args = [];
	foreach ($_POST as $key => $value) {
		switch ($key) {
			case 'name_no':
			case 'name_en':
			case 'description_no':
			case 'description_en':
			case 'date_start':
			case 'date_end':
			case 'time_start':
			case 'time_end':
			case 'max_orders_per_customer_per_year':
			case 'require_phone':
			case 'require_email':
			case 'require_comment':
			case 'require_membership':
			case 'inventory_count':
			case 'price':
			case 'price_member':
			case 'product_visible':
			case 'product_enabled':
			case 'file':
				$args["$key"] = ($value ? $value : NULL);
				break;
			default:

				$response->code = HTTP_INVALID_REQUEST;
				$response->data = [
					"error" => true,
					"success" => false,
					"message" => "Unknown argument: $key has been passed in",
				];
				$response->send();
				return;
		}
	}

	// check required parameters have value
	foreach (['name_no', 'name_en', 'price'] as $entry) {
		if (!isset($args[$entry])) {
			log::message("Warning: received invalid request key: $entry, value: " . $args[$entry], __FILE__, __LINE__);
			$response->code = HTTP_INVALID_REQUEST;
			$response->data = [
				"error" => true,
				"success" => false,
				"message" => "parameter $entry has not been set",
			];
			return;
		}
	}

	if ($args["require_membership"] || $args["price_member"] || $args["max_orders_per_customer_per_year"]) {
		$args["require_phone"] = true;
	}

	// Default empty non-required values to 0
	foreach (['date_start', 'date_end', 'time_start', 'time_end'] as $entry) {
		if (!isset($args[$entry])) {
			$args[$entry] = 0;
		}
	}

	// Do some date and time packing for SQL
	$start = $args["date_start"] . " " . $args["time_start"];
	$end = $args["date_end"] . " " . $args["time_end"];
	if ($start != " " && strtotime($start) !== false) $start = date("Y-m-d H:i:s", strtotime($start));
	else $start = NULL;
	if ($end != " " && strtotime($end) !== false) $end = date("Y-m-d H:i:s", strtotime($end));
	else $end = NULL;

	// generate a random hash for new product
	$product_hash = "";
	do {
		$product_hash = substr(md5((string)time()), 0, 20);
	} while (Store::product_exists($product_hash));

	// upload image, set file name to be the same as the product hash
	if (validateUploadImage("image")) {
		$extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
		$image_name = $product_hash . "." . $extension;
		if (!move_uploaded_file($_FILES["image"]["tmp_name"], "img/store/" . $image_name)) {
			throw new AddProductFailedException("failed to move uploaded image to correct location");
		}
	} else {
		throw new AddProductFailedException("Image not accepted for upload");
	}

	$new_product = [
		"hash" => $product_hash,
		"name" => json_encode(["no" => $args["name_no"], "en" => $args["name_en"]]),
		"description" => json_encode(["no" => $args["description_no"], "en" => $args["description_en"]]),
		"available_from" => $start,
		"available_until" => $end,
		"image" => $image_name,
		"max_orders_per_customer_per_year" => (int)($args["max_orders_per_customer_per_year"] || 0),
		"require_phone" => $args["require_phone"] ?? false,
		"require_email" => $args["require_email"] ?? false,
		"require_comment" => $args["require_comment"] ?? false,
		"require_membership" => $args["require_membership"] ?? false,
		"inventory_count" => $args["inventory_count"] ?? 0,
		"price" => $args["price"] === NULL ? NULL : $args["price"] * 100,
		"price_member" => $args["price_member"] === NULL ? NULL : $args["price_member"] * 100,
		"visible" => $args["product_visible"] ?? true,
		"enabled" => $args["product_enabled"] ?? true,
	];
	Store::add_product($new_product);
	log::message("Info: New product " . $args["name_en"] . " added to store", __FILE__, __LINE__);

	$response->data = [
		"success" => true,
		"error" => false
	];
	$response->code = HTTP_OK;
	return;
}


/**
 * Handle GET request
 *
 * @param Store $store 
 * @param Response $response
 * @return void
 */
function handle_get(Store &$store, Response &$response): void
{
	switch (argsURL("GET", "request_type")) {
		case "get_orders":
			$product = $store->get_product($_GET["product_hash"]);
			$sql = "SELECT id, name, email, phone, comment, order_status FROM orders WHERE products_id=? AND (order_status='FINALIZED' OR order_status='DELIVERED') ORDER BY FIELD(order_status, 'FINALIZED', 'DELIVERED')";
			$db = new DB("web");
			$db->prepare($sql);
			$db->bind_param("i", $product["id"]);
			$db->execute();
			$order_id = 0;
			$name = "";
			$email = "";
			$phone = "";
			$comment = "";
			$status = "";
			$db->bind_result($order_id, $name, $email, $phone, $comment, $status);
			$result = [];
			while ($db->fetch()) {
				$row = [
					"id" => $order_id,
					"name" => $name,
					"email" => $email,
					"phone" => $phone,
					"comment" => $comment,
					"status" => $status,
				];
				array_push($result, $row);
			}
			$response->data = $result;
			break;
		case "get_products":
			$response->data = $store->get_products(0, 100, "", false, false);
			break;
		case "get_product":
			$response->data = $store->get_product($_GET["product_hash"]);
			break;
		case "get_product_groups":
			$db = new DB("web");
			$db->prepare("SELECT id, name FROM product_groups");
			$db->execute();
			$group_id = 0;
			$name = "";
			$groups = [];
			$db->bind_result($group_id, $name);
			while ($db->fetch()) {
				$groups[$group_id] = $name;
			}
			$response->data = $groups;
			break;
		default:
			$response->error("Got invalid request: '" . argsURL("GET", "request_type") . "'. Valid requests are get_orders, get_products and get_product_groups.");
	}
	$response->code = HTTP_OK;
}


/**
 * Validate image
 *
 * @param string $input_name
 * @return boolean true if image is accepted for upload. False otherwise.
 */
function validateUploadImage(string $input_name): bool
{
	if (!isset($_FILES[$input_name]["tmp_name"])) {
		// no file uploaded
		return false;
	}
	if (!is_uploaded_file($_FILES[$input_name]["tmp_name"])) {
		return false;
	}
	if (!is_array(getimagesize($_FILES[$input_name]["tmp_name"]))) {
		log::message("Warning: Someone uploaded a non-image file to " . $_FILES[$input_name]["tmp_name"], __FILE__, __LINE__);
		return false;
	}
	$extension = pathinfo($_FILES[$input_name]["name"], PATHINFO_EXTENSION);
	if (!in_array($extension, ["png", "jpg", "jpeg"])) {
		log::message("Warning: extension: $extension was rejected from being uploaded. Should it be added?", __FILE__, __LINE__);
		return false;
	}
	return true;
}
