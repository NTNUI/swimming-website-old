<?php
// TODO: rename class to just Store
include_once("library/util/db.php");
include_once("library/exceptions/store.php");

class StoreHelper
{
	private $language;
	private $license_key;
	function __construct($lang)
	{
		global $settings;
		\Stripe\Stripe::setApiKey($settings["stripe"]["secret_key"]);
		$this->language = $lang;
		$this->license_key = $settings["license_product_hash"];
	}

	function get_products(int $start = 0, int $limit = 10, string $product_hash = "", bool $rawData = false, bool $visibility_check = true)
	{
		$language = $this->language;
		$db = new DB("web");
		$visibility = "";
		if ($visibility_check) {
			$visibility = "WHERE visible=TRUE";
		}

		// wtf is going on here?
		if ($product_hash == "") {
			// get 10 variables
			$sql = "SELECT 
			id, 
			hash, 
			name, 
			description, 
			price, 
			available_from, 
			available_until, 
			require_phone, 
			visible,
			(SELECT COUNT(*) FROM orders
			WHERE orders.products_id = products.id
			AND (orders.order_status='FINALIZED'
			OR orders.order_status='DELIVERED'))
			AS amount, amount_available, image, group_id FROM products AS products $visibility ORDER BY visible DESC, id DESC LIMIT ? OFFSET ?";

			$db->prepare($sql);
			$db->bind_param("ii", $limit, $start);
		} else {
			// Select every column from products, add a column called "amount" (which should really be called num_sold or something) given column hash (which should really be called product_hash)
			// get 13 variables
			$sql = "SELECT
			id, 
			hash, 
			name, 
			description, 
			price, 
			available_from, 
			available_until, 
			require_phone, 
			visible, 
			(SELECT COUNT(*) FROM orders WHERE orders.products_id = products.id AND (orders.order_status='FINALIZED' OR orders.order_status='DELIVERED')) AS amount_sold,
			amount_available, 
			image, 
			group_id 
			FROM products AS products WHERE hash=? ORDER BY id DESC LIMIT ? OFFSET ?";

			$db->prepare($sql);
			$db->bind_param("sii", $product_hash, $limit, $start);
		}

		try {
			$db->execute();
		} catch (\mysqli_sql_exception $ex) {
			log::message($ex->getMessage());
			return false;
		}

		$result = array();
		$db->stmt->bind_result(
			$id,
			$product_hash,
			$name,
			$description,
			$price,
			$available_from,
			$available_until,
			$require_phone,
			$visibility,
			$amount_sold,
			$amount_available,
			$image,
			$group_id
		);
		while ($db->fetch()) {
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
				"hash" => $product_hash,
				"name" => $name,
				"description" => $description,
				"price" => intval($price),
				"available_from" => strtotime($available_from),
				"available_until" => strtotime($available_until),
				"require_phone" => $require_phone,
				"amount_available" => $amount_available,
				"amount_sold" => $amount_sold,
				"visibility" => $visibility,
				"image" => $image,
				"group_id" => $group_id
			);
		}
		return $result;
	}

	// TODO: throw on error
	function get_product(string $product_hash, bool $rawData = false)
	{
		if (!$product_hash || $product_hash == "") return false;
		$result = $this->get_products(0, 1, $product_hash, $rawData);
		if (sizeof($result) < 1) return false;
		return $result[0];
	}

	// old: function create_order(string $product_hash, $paymentId, $owner, $comment)
	function create_order(string $product_hash, $paymentId, $owner)
	{

		$name = $owner->name;
		$email = $owner->email;
		$phone = NULL;
		if (property_exists($owner, "phone")) {
			$phone = $owner->phone;
		}

		$product = $this->get_product($product_hash);

		if ($product === false) throw \StoreException::ProductNotFound();
		if ($product["amount_available"] != null && $product["amount_sold"] >= $product["amount_available"]) throw \StoreException::ProductSoldOut();
		if ($product["available_from"] !== false && $product["available_from"] > time()) throw \StoreException::ProductNotAvailable("Current product is not yet available");
		if ($product["available_until"] !== false && $product["available_until"] < time()) throw \StoreException::ProductNotAvailable("Current product is no longer available");
		if ($name == "") throw \StoreException::MissingCustomerDetails("Missing customer name");
		if ($email == "") throw \StoreException::MissingCustomerDetails("Missing customer email");
		if ($phone == NULL && $product["require_phone"]) throw \StoreException::MissingCustomerDetails("Missing customer phone");

		//Perform a 3D secure checkout
		$intent = \Stripe\PaymentIntent::create([
			"payment_method" => $paymentId,
			"amount" => $product["price"],
			"currency" => "nok",
			"confirmation_method" => "manual",
			"confirm" => true,
			"receipt_email" => $email,
			"description" => $product["name"],
		]);

		// Save payment intent. Wait for callback
		$db = new DB("web");
		//$sql = "INSERT INTO orders (products_id, name, email, phone, source_id, comment) VALUES (?, ?, ?, ?, ?, ?)";
		$sql = "INSERT INTO orders (products_id, name, email, phone, source_id) VALUES (?, ?, ?, ?, ?)";
		$db->prepare($sql);
		$intent_id = $intent["id"];
		//$db->bind_param("isssss", $product["id"], $name, $email, $phone, $intent_id, $comment);
		$db->bind_param("issss", $product["id"], $name, $email, $phone, $intent_id);
		$db->execute();

		return $this->update_order($intent);
	}

	function get_intent_by_id($id)
	{
		return \Stripe\PaymentIntent::retrieve($id);
	}

	function update_order(\Stripe\PaymentIntent $intent)
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
			throw new \Stripe\Exception\ApiErrorException("stripe_error");
		}
		return $src;
	}

	function get_product_hash(int &$product_id): string
	{
		$db = new DB("web");
		$db->prepare("SELECT hash FROM products WHERE id=?");
		$db->bind_param("i", $product_id);
		$db->execute();
		$res = "";
		$db->stmt->bind_result($res);
		$db->fetch();
		return $res;
	}

	function finalize_order($intent_id)
	{
		$db = new DB("web");
		$sql = "SELECT id, products_id, phone AS products_id FROM orders WHERE source_id=?";
		$db->prepare($sql);
		$db->bind_param("s", $intent_id);
		$db->execute();
		$db->stmt->bind_result($id, $product_id, $phone);

		if (!$db->fetch()) throw \StoreException::ProductNotFound();

		$this->set_order_status($id, "FINALIZED");

		// Member registration hook
		if ($this->get_product_hash($product_id) == $this->license_key) {
			$this->approve_member($phone);
		}
	}

	function fail_order(\Stripe\Charge $charge_event)
	{
		$db = new DB("web");
		$sql = "UPDATE orders SET order_status='FAILED' WHERE source_id=? OR charge_id=?";
		$db->prepare($sql);
		$db->bind_param("ss", $charge_event["payment_intent"], $charge_event["id"]);
		$db->execute();
	}

	function approve_member(string $phone)
	{
		log::message("Info: Approving member with phone number: " . $phone, __FILE__, __LINE__);
		$db = new DB("member");
		$sql = "UPDATE member SET approved_date=NOW() WHERE phone=?";
		$db->prepare($sql);
		$db->bind_param("s", $phone);
		$db->execute();
	}

	function set_order_status(int $order_id, string $status)
	{
		if ($status !== "FINALIZED" && $status !== "DELIVERED" && $status !== "FAILED") {
			throw new \InvalidArgumentException($status . " is not one of 'FINALIZED' | 'DELIVERED' | 'FAILED'");
		}
		$db = new DB("web");
		$sql = "UPDATE orders SET order_status=? WHERE id=?";
		$db->prepare($sql);
		$db->bind_param("si", $status, $order_id);
		$db->execute();
	}

	function set_product_visibility(int $product_id, bool $visibility)
	{
		$db = new DB("web");
		$sql = "UPDATE products SET visible=? WHERE id=?";
		$db->prepare($sql);
		$db->bind_param("ii", $visibility, $product_id);
		$db->execute();
	}

	// TODO: new signature: get_order_status
	function get_status(string $source): string
	{
		$db = new DB("web");
		$sql = "SELECT order_status FROM orders WHERE source_id=?";
		$db->prepare($sql);
		$db->bind_param("s", $source);
		$db->execute();
		$db->fetch();
		$db->stmt->bind_result($charge);
		return $charge;
	}

	static public function order_id_exists(int $order_id): bool
	{
		$db = new DB("web");
		$sql = "SELECT COUNT(*) FROM orders WHERE id=?";
		$db->prepare($sql);
		$db->bind_param("i", $order_id);
		$db->execute();
		$db->stmt->bind_result($result);
		$db->fetch();
		return $result;
	}

	static public function product_exists(string $product_hash): bool
	{
		$db = new DB("web");
		$sql = "SELECT COUNT(*) FROM products WHERE hash=?";
		$db->prepare($sql);
		$db->bind_param("s", $product_hash);
		$db->execute();
		$result = true;
		$db->stmt->bind_result($result);
		$db->fetch();
		return $result;
	}

	static public function add_product(array $product): bool
	{
		global $access_control;
		$db = new DB("web");
		$sql = "INSERT INTO products (hash, name, description, price, amount_available, available_from, available_until, image) VALUES(?, ?, ?, ?, ?, ?, ?, ?)";
		$db->prepare($sql);

		$db->bind_param("sssiisss", $product["hash"], $product["name"], $product["description"], $product["price"], $product["amount"], $product["start"], $product["end"], $product["image_name"]);
		$db->execute();
		$access_control->log("admin/store", "add product", $product["hash"]);
		return true;
	}
}
