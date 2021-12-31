<?php
// TODO: split this class into product and order
require_once("library/util/db.php");
require_once("library/exceptions/store.php");

class Store
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

	/**
	 * retrieve Payment Intent given its id
	 *
	 * @see \Stripe\PaymentIntent::retrieve()
	 * @param string $paymentIntent_id of the PaymentIntent to retrieve
	 * @throws \Stripe\Exception\ApiErrorException — if the request fails
	 * @return \Stripe\PaymentIntent
	 */
	function get_intent_by_id(string $paymentIntent_id): \Stripe\PaymentIntent
	{
		return \Stripe\PaymentIntent::retrieve($paymentIntent_id);
	}


	/**
	 * Approve a member 
	 *
	 * @param string $phone
	 * @return void
	 */
	function approve_member(string $phone)
	{
		if (!$phone) {
			throw new InvalidArgumentException("phone is required");
		}
		$db = new DB("member");
		$db->prepare("UPDATE member SET approved_date=NOW() WHERE phone=?");
		$db->bind_param("s", $phone);
		$db->execute();
	}


	// Section products

	public static function update_product_date(string $product_hash, ?DateTime $date_from, ?DateTime $date_to)
	{
		if (!isset($date_from) && !isset($date_to)) {
			throw new InvalidArgumentException("one of the date arguments must be set");
		}
		if (isset($date_from)) {
			$db = new DB("web");
			$db->prepare("UPDATE products SET available_from=? WHERE hash=?");
			$val = $date_from->format("Y-m-d H:i:s");
			log::message("Attempting to save timestamp: " . $val, __FUNCTION__, __LINE__);
			$db->bind_param("ss", $val, $product_hash);
			$db->execute();
		}
		if (isset($date_to)) {
			$db = new DB("web");
			$db->prepare("UPDATE products SET available_until=? WHERE hash=?");
			$val = $date_to->format("Y-m-d H:i:s");
			log::message("Attempting to save timestamp: " . $val, __FUNCTION__, __LINE__);
			$db->bind_param("ss", $val, $product_hash);
			$db->execute();
		}
	}

	/**
	 * Undocumented function
	 *
	 * @param integer $start how many products to skip. Defaults to 0.
	 * @param integer $limit max amount of products to return. Defaults to 30
	 * @param string $product_hash hash of the product
	 * @param boolean $rawData idk
	 * @param boolean $visibility_check when true, return only visible products.
	 * @return array of products where each product is an array
	 */
	function get_products(int $start = 0, int $limit = 30, string $product_hash = "", bool $rawData = false, bool $visibility_check = true)
	{
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
			(	/* count completed orders */
				SELECT COUNT(*) FROM orders
				WHERE orders.products_id = products.id
				AND (orders.order_status='FINALIZED'
				OR orders.order_status='DELIVERED')
			) AS amount_sold,
			amount_available,
			image,
			group_id
			FROM products AS products $visibility ORDER BY visible DESC, id DESC LIMIT ? OFFSET ?";

			$db->prepare($sql);
			$db->bind_param("ii", $limit, $start);
		} else {
			// Select every column from products, add a column called "amount_sold" given column hash (which should really be called product_hash)
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
			(	/* count completed orders */
				SELECT COUNT(*) FROM orders WHERE 
				orders.products_id = products.id
				AND 
				(
					orders.order_status='FINALIZED'
					OR
					orders.order_status='DELIVERED'
				)
			) AS amount_sold,
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

		$language = $this->language;
		while ($db->fetch()) {
			if (!$rawData) {
				// documentation needed
				$name = json_decode($name);
				if (property_exists($name, $language)) $name = $name->$language;
				else if (array_key_exists("no", $name)) $name = $name->no;
				else $name = "";

				$description = json_decode($description);
				if (property_exists($description, $language)) $description = $description->$language;
				else if (array_key_exists("no", $description)) $description = $description->no;
				else $description = "";
			}

			// add timezone info
			$available_from = gettype($available_from) === "NULL" ? NULL : new DateTime($available_from, new DateTimeZone("Europe/Oslo"));
			$available_until = gettype($available_until) === "NULL" ? NULL : new DateTime($available_until, new DateTimeZone("Europe/Oslo"));

			// create date with time zone info
			$result[] = array(
				"id" => intval($id),
				"hash" => $product_hash,
				"name" => $name,
				"description" => $description,
				"price" => intval($price),
				"available_from" => gettype($available_from) === "NULL" ? NULL : $available_from->getTimestamp(),
				"available_until" => gettype($available_until) === "NULL" ? NULL : $available_until->getTimestamp(),
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


	/**
	 * Get a product given a product hash
	 *
	 * @param string $product_hash
	 * @param boolean $rawData idk
	 * @return array of one product
	 * @throws \StoreException if @param $product_hash is not fund
	 */
	function get_product(string $product_hash, bool $rawData = false): array
	{
		if (!$product_hash || $product_hash == "") return false;
		$result = $this->get_products(0, 1, $product_hash, $rawData);
		if (sizeof($result) < 1) throw \StoreException::ProductNotFound();
		return $result[0];
	}

	/**
	 * Update price for a product
	 *
	 * @param string $product_hash of the product
	 * @param integer $price in Norwegian øre. 1 NOK = 100 Øre
	 * @return void
	 */
	static function update_price(string $product_hash, int $price)
	{
		$db = new DB("web");
		$db->prepare("UPDATE products SET price=? WHERE hash=?");
		$db->bind_param("is", $price, $product_hash);
		$db->execute();
	}


	/**
	 * Check if product exists
	 *
	 * @param string $product_hash
	 * @return boolean true if product exists. False otherwise.
	 */
	static public function product_exists(string $product_hash): bool
	{
		$db = new DB("web");
		$db->prepare("SELECT COUNT(*) FROM products WHERE hash=?");
		$db->bind_param("s", $product_hash);
		$db->execute();
		$result = 0;
		$db->stmt->bind_result($result);
		$db->fetch();
		if ($result !== 0 && $result !== 1) {
			throw new UnexpectedValueException($result);
		}
		return (bool)$result;
	}


	/**
	 * Add new product to db
	 * 
	 * @param array $product to be added
	 * @return void
	 */
	static public function add_product(array $product)
	{
		$db = new DB("web");
		$sql = "INSERT INTO products (hash, name, description, price, amount_available, available_from, available_until, image) VALUES(?, ?, ?, ?, ?, ?, ?, ?)";
		$db->prepare($sql);
		$db->bind_param("sssiisss", $product["hash"], $product["name"], $product["description"], $product["price"], $product["amount"], $product["start"], $product["end"], $product["image_name"]);
		$db->execute();
	}


	/**
	 * Remove product from DB
	 * 
	 * @param array $product to be removed
	 * @return void
	 */
	static public function remove_product(string $product_hash)
	{
		if (!Store::product_exists($product_hash)) {
			throw StoreException::ProductNotFound();
		}
		// get image name
		$db = new DB("web");
		$db->prepare("SELECT image FROM products WHERE hash=?");
		$db->bind_param("s", $product_hash);
		$db->execute();
		$image_path = "img/store/" . $db->fetch();
		if (file_exists($image_path)) {
			if (!unlink($image_path)) {
				throw StoreException::RemoveProductFailed();
			}
		}
		$db->stmt->close();
		$db->prepare("DELETE FROM products WHERE hash=?");
		$db->bind_param("s", $product_hash);
		$db->execute();
	}


	/**
	 * Get string product hash given its int product id
	 *
	 * @param integer $product_id
	 * @return string product hash
	 */
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


	/**
	 * Read payment intent return its status
	 * Side effects:
	 * - Updates copy of the status in the DB if order succeeds. 
	 *
	 * @param \Stripe\PaymentIntent $intent
	 * @throws \Stripe\Exception\ApiErrorException - on illegal request
	 * @return array
	 */
	function update_order(\Stripe\PaymentIntent $intent): array
	{
		if ($intent->status == "requires_action" && $intent->next_action->type == "use_stripe_sdk") {
			$src = [
				"requires_action" => true,
				"payment_intent_client_secret" => $intent->client_secret
			];
		} else if ($intent->status == "succeeded") {
			$this->finalize_order($intent["id"]);
			$src = [
				"success" => true,
				"message" => "Purchase succeeded"
			];
		} else {
			throw new \Stripe\Exception\ApiErrorException("stripe_error");
		}
		return $src;
	}


	/**
	 * Update order to FINALIZED in db
	 * Side effects:
	 * - if order was used to purchase a license then member is approved. Following it's side effects.
	 * 
	 * @see Store_helper::approve_member()
	 * @param string $intent_id
	 * @return void
	 */
	function finalize_order(string $intent_id)
	{
		$db = new DB("web");
		$db->prepare("SELECT id, products_id, phone AS products_id FROM orders WHERE source_id=?");
		$db->bind_param("s", $intent_id);
		$db->execute();
		$db->stmt->bind_result($order_id, $product_id, $phone);

		if (!$db->fetch()) throw \StoreException::ProductNotFound();

		$this->set_order_status($order_id, "FINALIZED");

		// Member registration hook
		if ($this->get_product_hash($product_id) == $this->license_key) {
			$this->approve_member($phone);
		}
	}


	/**
	 * Update order in DB to be FAILED.
	 *
	 * @param \Stripe\Charge $charge_event
	 * @return void
	 */
	function fail_order(\Stripe\Charge $charge_event)
	{
		$db = new DB("web");
		$db->prepare("UPDATE orders SET order_status='FAILED' WHERE source_id=? OR charge_id=?");
		$db->bind_param("ss", $charge_event["payment_intent"], $charge_event["id"]);
		$db->execute();
	}


	/**
	 * Set order status
	 *
	 * @param integer $order_id row identifier in the database.
	 * @param string $status allowed input: 'FINALIZED' | 'DELIVERED' | 'FAILED'
	 * @throws \InvalidArgumentException if instructions above are ignored
	 * @return void
	 * @note @param int $order_id should not be confused with stripe id system witch uses strings as identifier.
	 */
	function set_order_status(int $order_id, string $status)
	{
		if ($status !== "FINALIZED" && $status !== "DELIVERED" && $status !== "FAILED") {
			throw new \InvalidArgumentException($status . " is not one of 'FINALIZED' | 'DELIVERED' | 'FAILED'");
		}
		$db = new DB("web");
		$db->prepare("UPDATE orders SET order_status=? WHERE id=?");
		$db->bind_param("si", $status, $order_id);
		$db->execute();
	}


	/**
	 * Set product visibility
	 *
	 * @param integer $product_id
	 * @param boolean $visibility
	 * @return void
	 */
	function set_product_visibility(int $product_id, bool $visibility)
	{
		$db = new DB("web");
		$db->prepare("UPDATE products SET visible=? WHERE id=?");
		$db->bind_param("ii", $visibility, $product_id);
		$db->execute();
	}


	// TODO: new signature: get_order_status
	/**
	 * Get order status
	 *
	 * @param string $paymentIntent_id
	 * @return string only 'FINALIZED' | 'DELIVERED' | 'FAILED'
	 * @throws StoreException::OrderNotFound if order is not found.
	 */
	function get_status(string $paymentIntent_id): string
	{
		$db = new DB("web");
		$db->prepare("SELECT order_status FROM orders WHERE source_id=?");
		$db->bind_param("s", $paymentIntent_id);
		$db->execute();
		$db->fetch();
		$db->stmt->bind_result($order_status);
		if ($order_status !== 'FINALIZED' && $order_status !== 'DELIVERED' && $order_status !== 'FAILED') {
			throw StoreException::OrderNotFound();
		}
		return $order_status;
	}


	/**
	 * Does order exists?
	 *
	 * @param integer $order_id identifier in the database
	 * @return boolean true if order exists. False otherwise.
	 */
	static public function order_id_exists(int $order_id): bool
	{
		$db = new DB("web");
		$db->prepare("SELECT COUNT(*) FROM orders WHERE id=?");
		$db->bind_param("i", $order_id);
		$db->execute();
		$result = 0;
		$db->stmt->bind_result($result);
		$db->fetch();
		if ($result !== 0 && $result !== 1) {
			throw new UnexpectedValueException($result);
		}
		return (bool)$result;
	}

	// section orders

	// old: function create_order(string $product_hash, string $paymentId, object $owner, string $comment)
	/**
	 * Create an order
	 *
	 * @param string $product_hash
	 * @param string $paymentId
	 * @param object $owner
	 * @return void
	 */
	function create_order(string $product_hash, string $paymentId, object $owner)
	{
		$name = $owner->name;
		$email = $owner->email;
		$phone = NULL;
		if (property_exists($owner, "phone")) {
			$phone = $owner->phone;
		}

		$product = $this->get_product($product_hash);

		if ($product["amount_available"] != null && $product["amount_sold"] >= $product["amount_available"]) throw \StoreException::ProductSoldOut();
		if ($product["available_from"] !== null && $product["available_from"] > time()) throw \StoreException::ProductNotAvailable("Current product is not yet available");
		if ($product["available_until"] !== null && $product["available_until"] < time()) throw \StoreException::ProductNotAvailable("Current product is no longer available");
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
}
