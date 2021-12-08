<?php
// Available services:
// [GET] get_product(product_id)
// [GET] get_products()
// [GET] get_groups()
// [POST] add_product(name_no, name_en, description_no, description_en, price, amount, start_date, start_time, image)
// [PATCH] set_order_status(order_id, status)
// [PATCH] set_product_visibility(product_id, visibility)

include_once("library/util/db.php");
include_once("library/util/store_helper.php");
include_once("library/util/api.php");

$response = new Response();
$store = new StoreHelper($language);
$input = file_get_contents("php://input");

Authenticator::auth_API("api/store", "", __FILE__, __LINE__);

$conn = connect("web");
switch ($_SERVER['REQUEST_METHOD']) {
	case "GET":
		handle_get($conn, $store, $response);
		break;
	case "POST":
		handle_post($conn, $response);
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
$conn->close();
$response->send();
return;

function handle_patch(StoreHelper $store, string $input, Response &$response)
{
	global $access_control;
	if (!$input) {
		$response->code = HTTP_INVALID_REQUEST;
		$response->data = ["error" => true, "message" => "No input provided for " . $_SERVER['REQUEST_METHOD'] . " request"];
		$response->send();
		return;
	}

	$input_json = json_decode($input);
	if ($input_json === NULL) {
		$response->code = HTTP_INVALID_REQUEST;
		$response->error("Syntax error in json object");
		$response->send();
		return;
	}

	switch ($input_json->{"request_type"}) {
		case 'update_delivered':
			if (!isset($input_json->{"params"}->{"order_id"})) {
				$response->code = HTTP_INVALID_REQUEST;
				$response->data = [
					"error" => true,
					"message" => "Missing required parameter order_id"
				];
				break;
			}
			if (!isset($input_json->{"params"}->{"order_status"})) {
				$response->code = HTTP_INVALID_REQUEST;
				$response->data = [
					"error" => true,
					"message" => "Missing required parameter order_status"
				];
				break;
			}
			if (!StoreHelper::order_id_exists($input_json->{"params"}->{"order_id"})) {
				$response->code = HTTP_NOT_FOUND;
				$response->data = [
					"error" => true,
					"message" => "Order not found"
				];
				break;
			}

			if (!is_int(array_search($input_json->{"params"}->{"order_status"}, ["DELIVERED", "FINALIZED"]))) {
				$response->code = HTTP_INVALID_REQUEST;
				$response->data = [
					"error" => true,
					"message" => "Invalid input on property order_status. Got : " . $input_json->{"params"}->{"order_status"} . " Valid inputs are DELIVERED and FINALIZED."
				];
				break;
			}
			$order_id = $input_json->{"params"}->{"order_id"};
			$order_status = $input_json->{"params"}->{"order_status"};
			$store->set_order_status($order_id, $order_status);
			$response->code = HTTP_OK;
			$response->data = [
				"success" => true
			];
			$access_control->log("admin/store", "order delivery status update", $order_id);
			break;
		case 'update_visibility':
			$product_hash = $input_json->{"params"}->{"product_hash"};
			$product = $store->get_product($product_hash);
			if ($product == false) {
				$response->code = HTTP_NOT_FOUND;
				$response->data = [
					"error" => true,
					"message" => "Product not found"
				];
				break;
			}
			$visibility = filter_var($input_json->{"params"}->{"visibility"}, FILTER_VALIDATE_BOOLEAN);
			$store->set_product_visibility($product["id"], $visibility);
			$response->code = HTTP_OK;
			$response->data = [
				"success" => true
			];
			$access_control->log("admin/store", "update visibility", $product_hash);
			break;
		default:
			$response->error("Got invalid request: '" . $input_json->{"request_type"} . "'. Valid requests are request_type and update_visibility");
	}
}

function handle_post(mysqli $conn, Response &$response)
{
	// get these arguments, ignore rest
	$args = [];
	foreach ($_POST as $key => $value) {
		switch ($key) {
			case 'name_no':
			case 'name_en':
			case 'description_no':
			case 'description_en':
			case 'amount':
			case 'price':
			case 'date_start':
			case 'date_end':
			case 'time_start':
			case 'time_end':
				$args["$key"] = ("$value" ? "$value" : NULL);
				break;
			default:
				break;
		}
	}

	// check required parameters have value
	foreach (['name_no', 'name_en', 'price'] as $entry) {
		if (!isset($args[$entry])) {
			log::message("Warning: received invalid request key: $entry, value: " . $args[$entry], __FILE__, __LINE__);
			$response->code = HTTP_INVALID_REQUEST;
			$response->data = [
				"error" => true,
				"message" => "parameter $entry has not been set"
			];
			return;
		}
	}

	// Default empty non-required values to 0
	foreach (['date_start', 'date_end', 'time_start', 'time_end'] as $entry) {
		if (!isset($args[$entry])) {
			$args[$entry] = 0;
		}
	}

	// Move uploaded file to img/store with a random name. On error default to default.jpg
	// TODO: same hash for image and for product_hash
	$image_name = "default.jpg";

	if (validateUploadImage("image")) {
		$extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
		do {
			$image_name = md5(time()) . "." . $extension;
		} while (file_exists("img/store/" . $image_name));
		move_uploaded_file($_FILES["image"]["tmp_name"], "img/store/" . $image_name);
		if (!file_exists("img/store/" . $image_name)) {
			$image_name = "default.jpg";
		}
	} else {
		log::message("Warning: Uploaded a new product to store without image", __FILE__, __LINE__);
	}
	// Do some date and time packing for SQL
	$start = $args["date_start"] . " " . $args["time_start"];
	$end = $args["date_end"] . " " . $args["time_end"];
	if ($start != " " && strtotime($start) !== false) $start = date("Y-m-d H:i:s", strtotime($start));
	else $start = null;
	if ($end != " " && strtotime($end) !== false) $end = date("Y-m-d H:i:s", strtotime($end));
	else $end = null;

	// generate a random hash for new product
	$product_hash = "";
	do {
		$product_hash = substr(md5(time()), 0, 20);
	} while (StoreHelper::product_exists($product_hash));

	$new_product = [
		"hash" => $product_hash,
		"name" => json_encode(["no" => $args["name_no"], "en" => $args["name_en"]]),
		"description" => json_encode(["no" => $args["description_no"], "en" => $args["description_en"]]),
		"price" => $args["price"],
		"amount" => $args["amount"],
		"start" => $start,
		"end" => $end,
		"image_name" => $image_name
	];
	// TODO: add_product returns always true
	if (!StoreHelper::add_product($new_product)) {
		$response->data = ["success" => false, "message" => "Could not add new product to store"];
		$response->code = HTTP_INTERNAL_SERVER_ERROR;
		log::message("Error: Could not add new product to store", __FILE__, __LINE__);
		return;
	}

	$response->data = ["success" => true];
	$response->code = HTTP_OK;
	return;
}

function handle_get(mysqli $conn, StoreHelper $store, Response &$response)
{
	switch (argsURL("GET", "request_type")) {
		case "get_orders":
			$product = $store->get_product($_GET["product_hash"]);
			$sql = "SELECT id, name, email, phone, comment, order_status FROM orders WHERE products_id=? AND (order_status='FINALIZED' OR order_status='DELIVERED') ORDER BY FIELD(order_status, 'FINALIZED', 'DELIVERED')";
			$query = $conn->prepare($sql);
			$query->bind_param("i", $product["id"]);
			$query->execute();
			$id = "";
			$name = "";
			$email = "";
			$phone = "";
			$comment = "";
			$status = "";
			$query->bind_result($id, $name, $email, $phone, $comment, $status);
			$result = array();
			while ($query->fetch()) {
				$row = [
					"id" => $id,
					"name" => $name,
					"email" => $email,
					"phone" => $phone,
					"comment" => $comment,
					"status" => $status
				];
				array_push($result, $row);
			}
			$response->data = $result;
			$response->code = HTTP_OK;
			break;
		case "get_products":
			log::message("Info: Requesting products", __FILE__, __LINE__);
			$response->data = $store->get_products(0, 100, "", false, false);
			$response->code = HTTP_OK;
			break;
		case "get_product":
			log::message("Info: Requesting product : " . $_GET["product_hash"], __FILE__, __LINE__);
			$response->data = $store->get_product($_GET["product_hash"]);
			$response->code = HTTP_OK;
			break;
		case "get_product_groups":
			$sql = "SELECT id, name FROM product_groups";
			$query = $conn->prepare($sql);
			$query->execute();
			$group_id = 0;
			$name = "";
			$groups = [];
			$query->bind_result($group_id, $name);
			while ($query->fetch()) {
				$groups[$group_id] = $name;
			}
			$response->data = $groups;
			$response->code = HTTP_OK;
			break;
		default:
			foreach ($_GET as $key => $value) {
				print("$key: $value\n");
			}
			$response->error("Got invalid request: '" . argsURL("GET", "request_type") . "'. Valid requests are get_orders, get_products and get_product_groups.");
	}
}

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
