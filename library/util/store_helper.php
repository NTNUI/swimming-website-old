<?php
// TODO: rename class to just Store
include_once("library/util/db.php");

class StoreHelper
{
	private $language;
	private $licence_key;
	function __construct($lang)
	{
		global $settings;
		\Stripe\Stripe::setApiKey($settings["stripe"]["secret_key"]);
		$this->language = $lang;
		$this->licence_key = $settings["defaults"]["licence_key"]; // deprecated
	}

	// id means really store_item_id
	// TODO: rename function to get_store_items
	function get_items(int $start = 0, int $limit = 10, string $id = "", bool $rawData = false, bool $visibility_check = true)
	{
		$language = $this->language;
		$mysqli = connect("web");
		if (!$mysqli) {
			log::die("Failed to connect to database", __FILE__, __LINE__);
		}
		$visibility = "";
		if ($visibility_check) {
			$visibility = "WHERE visible=TRUE";
		}

		// wtf is going on here?
		if ($id == "") {
			$sql = "SELECT id, api_id, name, description, price, available_from, available_until, require_phone, visible,
			(SELECT COUNT(*) FROM store_orders
			WHERE store_orders.item_id = store_items.id
			AND (store_orders.order_status='FINALIZED'
			OR store_orders.order_status='DELIVERED'))
			AS amount, amount_available, image, group_id FROM store_items AS store_items $visibility ORDER BY visible DESC, id DESC LIMIT ? OFFSET ?";

			$query = $mysqli->prepare($sql);
			$query->bind_param("ii", $limit, $start);
		} else {
			$sql = "SELECT id, api_id, name, description, price, available_from, available_until, require_phone, visible, (SELECT COUNT(*) FROM store_orders WHERE store_orders.item_id = store_items.id AND (store_orders.order_status='FINALIZED' OR store_orders.order_status='DELIVERED')) AS amount, amount_available, image, group_id FROM store_items AS store_items WHERE api_id=? ORDER BY id DESC LIMIT ? OFFSET ?";
			$query = $mysqli->prepare($sql);
			$query->bind_param("sii", $id, $limit, $start);
		}

		if (!$query->execute()) {
			return false;
		}

		$result = array();
		$query->bind_result($id, $item_hash, $name, $description, $price, $available_from, $available_until, $require_phone, $visibility, $amount, $amount_available, $image, $group_id);
		if (!$query) {
			log::die("Failed to bind result", __FILE__, __LINE__);
		}
		while ($query->fetch()) {
			if (!$rawData) {
				$name = json_decode($name);
				if (property_exists($name, $language)) $name = $name->$language;
				else if (array_key_exists("no", $name)) $name = $name->no;
				else $name = "";

				$description = json_decode($description);
				if (property_exists($description, $language)) $description = $description->$language;
				else if (array_key_exists("no", $description)) $description = $description->no;
				else $description = "";
			}

			$result[] = array(
				"id" => intval($id),
				"item_hash" => $item_hash,
				"name" => $name,
				"description" => $description,
				"price" => intval($price),
				"available_from" => strtotime($available_from),
				"available_until" => strtotime($available_until),
				"require_phone" => $require_phone,
				"amount_available" => $amount_available,
				"amount_bought" => $amount,
				"visibility" => $visibility,
				"image" => $image,
				"group_id" => $group_id
			);
		}

		$query->close();
		$mysqli->close();

		return $result;
	}

	function get_item(string $id, bool $rawData = false)
	{
		if (!$id || $id == "") return false;
		$result = $this->get_items(0, 1, $id, $rawData);
		if (sizeof($result) < 1) return false;
		return $result[0];
	}

	function create_order($api_id, $paymentId, $owner, $comment)
	{
		$mysqli = connect("web");

		$name = $owner->name;
		$email = $owner->email;
		$phone = NULL;
		if (property_exists($owner, "phone")) {
			$phone = $owner->phone;
		}

		$item = $this->get_item($api_id);

		if ($item === false) throw new Exception("no_such_item");
		if ($item["amount_available"] != null && $item["amount_bought"] >= $item["amount_available"]) throw new Exception("item_soldout");
		if ($item["available_from"] !== false && $item["available_from"] > time()) throw new Exception("not_available_yet");
		if ($item["available_until"] !== false && $item["available_until"] < time()) throw new Exception("no_longer_available");
		if ($name == "") throw new Exception("missing_name");
		if ($email == "") throw new Exception("missing_email");
		if ($phone == NULL && $item["require_phone"]) throw new Exception("missing_phone");

		//Perform a 3D secure checkout
		$intent = \Stripe\PaymentIntent::create([
			"payment_method" => $paymentId,
			"amount" => $item["price"],
			"currency" => "nok",
			"confirmation_method" => "manual",
			"confirm" => true,
			"receipt_email" => $email,
			"description" => $item["name"],
		]);

		//Create store record

		$sql = "INSERT INTO store_orders (item_id, name, email, phone, source_id, kommentar) VALUES (?, ?, ?, ?, ?, ?)";

		$query = $mysqli->prepare($sql);
		$intent_id = $intent["id"];
		$query->bind_param("isssss", $item["id"], $name, $email, $phone, $intent_id, $comment);

		if (!$query->execute()) throw new Exception("sql_error");

		$query->close();
		$mysqli->close();

		return $this->update_order($intent);
	}

	function get_intent_by_id($id)
	{
		return \Stripe\PaymentIntent::retrieve($id);
	}

	function update_order($intent)
	{
		if ($intent->status == "requires_action" && $intent->next_action->type == "use_stripe_sdk") {
			$src = [
				"requires_action" => true,
				"payment_intent_client_secret" => $intent->client_secret
			];
		} else if ($intent->status == "succeeded") {
			$this->finalize_order($intent["id"]);
			$src = ["success" => true];
		} else {
			throw new Exception("stripe_error");
		}
		return $src;
	}

	function finalize_order(string $intentId)
	{
		$mysqli = connect("web");

		if (!$mysqli) {
			log::die("failed to connect to database", __FILE__, __LINE__);
		}

		$sql = "SELECT id, item_id, email FROM store_orders WHERE source_id=?";
		$query = $mysqli->prepare($sql);
		if (!$query) {
			log::die("Failed to prepare query", __FILE__, __LINE__);
		}

		$query->bind_param("s", $intentId);
		if (!$query) {
			log::die("Failed to bind params", __FILE__, __LINE__);
		}

		$query->execute();
		if (!$query) {
			log::die("Failed to execute query", __FILE__, __LINE__);
		}

		$query->bind_result($id, $api_id, $email);
		if (!$query) {
			log::die("Failed to bind results", __FILE__, __LINE__);
		}

		if (!$query->fetch()) throw new Exception("no_such_order");

		$query->close();
		$this->update_status($id, "FINALIZED", $mysqli);
		$mysqli->close();

		// Member registration hook
		if ($api_id == $this->licence_key) $this->approve_member($email);
	}

	// TODO: When members don't provide the same email this will fail. Consider making fields read only after registration
	function approve_member(string $email)
	{
		// TODO: ignore not found errors
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			log::message("[Warning] Dropping invalid email " . $email, __FILE__, __LINE__);
			return;
		}
		$mysqli = connect("medlem");
		$sql = "UPDATE medlem SET kontrolldato=NOW() WHERE epost=?";
		$query = $mysqli->prepare($sql);
		$query->bind_param("s", $email);
		if (!$query->execute()) {
			log::message("[Warning]: Could not execute query", __FILE__, __LINE__);
		}
		$query->close();
		$mysqli->close();
	}

	// TODO: delete rows instead of modifying
	function fail_order($charge)
	{
		$mysqli = connect("web");
		$sql = "UPDATE store_orders SET order_status='FAILED' WHERE source_id=? OR charge_id=?";
		$query = $mysqli->prepare($sql);
		$query->bind_param("ss", $charge["payment_intent"], $charge["id"]);
		$query->execute();
		$query->close();
		$mysqli->close();
	}

	// TODO: change function signature. set_order_delivery is more descriptive
	function update_status(int $order_id, string $status, mysqli $mysqli = NULL)
	{
		$close = false;
		if ($mysqli == NULL) {
			$mysqli = connect("web");
			$close = true;
		}
		$sql = "UPDATE store_orders SET order_status=? WHERE id=?";
		$query = $mysqli->prepare($sql);
		$query->bind_param("si", $status, $order_id);
		$query->execute();
		$query->close();
		if ($close) $mysqli->close();
	}

	// set store item visibility
	function set_visibility(int $item_id, bool $visibility)
	{
		$mysqli = connect("web");
		$sql = "UPDATE store_items SET visible=? WHERE id=?";
		$query = $mysqli->prepare($sql);
		$query->bind_param("ii", $visibility, $item_id);
		$query->execute();
		$query->close();
		$mysqli->close();
	}

	// TODO: new signature: get_order_status
	function get_status($source): string
	{
		$mysqli = connect("web");

		$sql = "SELECT order_status FROM store_orders WHERE source_id=?";
		$query = $mysqli->prepare($sql);
		$query->bind_param("s", $source);
		if (!$query->execute()) return "";
		$query->bind_result($charge);
		if (!$query->fetch()) return "";

		$query->close();
		$mysqli->close();

		return $charge;
	}

	static public function order_id_exists(int $order_id): bool
	{
		$mysqli = connect("web");
		$sql = "SELECT COUNT(*) FROM store_orders WHERE id=?";
		$query = $mysqli->prepare($sql);
		$query->bind_param("i", $order_id);
		$query->execute();
		$query->bind_result($result);
		$query->fetch();
		$query->close();
		$mysqli->close();
		return $result;
	}

	static public function item_id_exists(string $item_id): bool
	{
		include_once("library/util/db.php");

		$mysqli = connect("web");
		$sql = "SELECT COUNT(*) FROM store_items WHERE api_id=?";
		$query = $mysqli->prepare($sql);
		if (!$query) {
			log::die("Failed to prepare query", __FILE__, __LINE__);
		}
		if (!$query->bind_param("s", $item_id)) {
			log::die("Could not bind parameters", __FILE__, __LINE__);
		}
		if (!$query->execute()) {
			log::die("Failed to execute query", __FILE__, __LINE__);
		}
		$result = true;
		if (!$query->bind_result($result)) {
			log::die("Failed to bind results", __FILE__, __LINE__);
		}
		if (!$query->fetch()) {
			log::die("Failed to fetch rows?", __FILE__, __LINE__);
		}
		$query->close();
		$mysqli->close();
		return $result;
	}

	static public function add_store_item(array $store_item): bool
	{
		global $access_control;
		$mysqli = connect("web");
		$sql = "INSERT INTO store_items (api_id, name, description, price, amount_available, available_from, available_until, image) VALUES(?, ?, ?, ?, ?, ?, ?, ?)";
		$query = $mysqli->prepare($sql);

		$query->bind_param("sssiisss", $store_item["item_id"], $store_item["name"], $store_item["description"], $store_item["price"], $store_item["amount"], $store_item["start"], $store_item["end"], $store_item["image_name"]);
		if (!$query->execute()) {
			log::die("Failed executing query", __FILE__, __LINE__);
			return false;
		}

		$query->close();
		$mysqli->close();
		$access_control->log("admin/store", "created item", $store_item["item_id"]);
		return true;
	}
}
