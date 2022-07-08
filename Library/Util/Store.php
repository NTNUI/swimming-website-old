<?php

declare(strict_types=1);
// TODO: split this class into product and order
require_once("Library/Exceptions/Store.php");
require_once("Library/Util/Db.php");
require_once("Library/Util/Member.php");

class Store
{
	private $language;
	private $license_key;
	function __construct($lang)
	{
		\Stripe\Stripe::setApiKey($_ENV["STRIPE_SECRET_KEY"]);
		$this->language = $lang;
		$this->license_key = Settings::get_instance()->get_license_product_hash();
	}

	/**
	 * retrieve Payment Intent given its id
	 *
	 * @see \Stripe\PaymentIntent::retrieve()
	 * @param string $payment_intent_id of the PaymentIntent to retrieve
	 * @throws \Stripe\Exception\ApiErrorException — if the request fails
	 * @return \Stripe\PaymentIntent
	 */
	function get_intent_by_id(string $payment_intent_id): \Stripe\PaymentIntent
	{
		return \Stripe\PaymentIntent::retrieve($payment_intent_id);
	}

	// Section products

	public static function update_product_date(string $product_hash, ?DateTime $date_from, ?DateTime $date_to)
	{
		if (!isset($date_from) && !isset($date_to)) {
			throw new InvalidArgumentException("one of the date arguments must be set");
		}
		$db = new DB("web");
		if (isset($date_from)) {
			$db->prepare("UPDATE products SET available_from=? WHERE hash=?");
			$val = $date_from->format("Y-m-d H:i:s");
			log::message("Product available from update: " . $val  . " on product with hash " . $product_hash, __FILE__, __LINE__);
			$db->bind_param("ss", $val, $product_hash);
			$db->execute();
			$db->reset();
		}
		if (isset($date_to)) {
			$db->prepare("UPDATE products SET available_until=? WHERE hash=?");
			$val = $date_to->format("Y-m-d H:i:s");
			log::message("Product available to update: " . $val  . " on product with hash " . $product_hash, __FILE__, __LINE__);
			$db->bind_param("ss", $val, $product_hash);
			$db->execute();
		}
	}

	/**
	 * Return products
	 *
	 * @param integer $start how many products to skip. Defaults to 0.
	 * @param integer $limit max amount of products to return. Defaults to 30
	 * @param string $product_hash hash of the product
	 * @param boolean $rawData idk
	 * @param boolean $visibility_check when true, return only visible products.
	 * @return array of products where each product is an array
	 */
	function get_products(int $start = 0, int $limit = 30, string $product_hash = "", bool $rawData = false, bool $visibility_check = true): array
	{
		$db = new DB("web");
		$visibility = "";
		if ($visibility_check) {
			$visibility = "WHERE visible=TRUE";
		}

		// wtf is going on here?
		// TODO: replace this mess with SELECT * ...
		if ($product_hash == "") {
			$sql = "SELECT
			id,
			hash,
			name,
			description,
			price,
			price_member,
			available_from,
			available_until,
			max_orders_per_customer_per_year,
			require_phone,
			require_email,
			require_comment,
			require_active_membership,
			visible,
			enabled, 
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
			FROM products AS products $visibility ORDER BY visible DESC, id DESC LIMIT ? OFFSET ?";

			$db->prepare($sql);
			$db->bind_param("ii", $limit, $start);
		} else {
			// Select every column from products, add a column called "amount_sold" given column hash (which should really be called product_hash)
			$sql = "SELECT
			id,
			hash,
			name,
			description,
			price,
			price_member,
			available_from,
			available_until,
			max_orders_per_customer_per_year,
			require_phone,
			require_email,
			require_comment,
			require_active_membership,
			visible,
			enabled,
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

		$db->execute();

		$result = array();
		$db->bind_result(
			$id,
			$product_hash,
			$name,
			$description,
			$price,
			$price_member,
			$available_from,
			$available_until,
			$max_orders_per_customer_per_year,
			$require_phone,
			$require_email,
			$require_comment,
			$require_active_membership,
			$visibility,
			$enabled,
			$amount_sold,
			$amount_available,
			$image,
			$group_id
		);

		$language = $this->language;
		while ($db->fetch()) {
			if (!$rawData) {
				// Unpack json into array.
				// Can this be done client side?
				$name = json_decode($name, true, flags: JSON_THROW_ON_ERROR);
				if (array_key_exists($language, $name)) {
					$name = $name[$language];
				} else if (array_key_exists("no", $name)) {
					$name = $name["no"];
				} else {
					$name = "";
				}

				$description = json_decode($description, true, flags: JSON_THROW_ON_ERROR);
				if (array_key_exists($language, $description)) {
					$description = $description[$language];
				} else if (array_key_exists("no", $description)) {
					$description = $description["no"];
				} else {
					$description = "";
				}
			}

			// add timezone info
			$available_from = $available_from === NULL ? NULL : new DateTime($available_from, new DateTimeZone("Europe/Oslo"));
			$available_until = $available_until === NULL ? NULL: new DateTime($available_until, new DateTimeZone("Europe/Oslo"));

			// create date with time zone info
			$result[] = array(
				"id" => intval($id),
				"hash" => $product_hash,
				"name" => $name,
				"description" => $description,
				"price" => intval($price) / 100,
				"price_member" => intval($price_member) / 100,
				"available_from" => $available_from === NULL ? NULL : $available_from->getTimestamp(),
				"available_until" => $available_until === NULL ? NULL : $available_until->getTimestamp(),
				"max_orders_per_customer_per_year" => $max_orders_per_customer_per_year,
				"require_phone" => $require_phone,
				"require_email" => $require_email,
				"require_comment" => $require_comment,
				"require_active_membership" => $require_active_membership,
				"amount_available" => $amount_available,
				"amount_sold" => $amount_sold,
				"visibility" => $visibility,
				"enabled" => $enabled,
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
	 * @throws ProductNotFoundException if @param $product_hash is not fund
	 */
	function get_product(string $product_hash, bool $rawData = false): array
	{
		if (!self::product_exists($product_hash)) {
			throw new ProductNotFoundException();
		}
		$result = $this->get_products(start: 0, limit: 1, product_hash: $product_hash, rawData: $rawData);
		return $result[0];
	}

	/**
	 * Update price for a product
	 *
	 * @param string $product_hash of the product
	 * @param integer $price in NOK
	 * @throws ProductNotFoundException
	 * @return void
	 */
	static function update_price(string $product_hash, int $price): void
	{
		if (!self::product_exists($product_hash)) {
			throw new ProductNotFoundException("Product not found");
		}

		$price *= 100; // convert from NOK to øre
		$db = new DB("web");
		$db->prepare("UPDATE products SET price=? WHERE hash=?");
		$db->bind_param("is", $price, $product_hash);
		$db->execute();
	}

	/**
	 * Get number of orders for a product
	 * 
	 * @param string $product_hash of the product
	 * @throws ProductNotFoundException
	 * @return int number of fulfilled orders
	 */
	static public function get_order_count(string $product_hash): int
	{
		if (!self::product_exists($product_hash)) {
			throw new ProductNotFoundException();
		}
		$db = new DB("web");
		$sql = "SELECT COUNT(*) FROM orders WHERE products_id = (SELECT id FROM products WHERE hash=?) AND (order_status='DELIVERED' OR order_status='FINALIZED')";
		$db->prepare($sql);
		$db->bind_param("s", $product_hash);
		$db->execute();
		$result = 0;
		$db->bind_result($result);
		$db->fetch();
		return $result;
	}

	/**
	 * Update number of products available for purchase
	 * 
	 * @param string $product_hash of the product to be modified
	 * @throws ProductNotFoundException if product is not found
	 * @throws ModifyProductException if new inventory count is lower than number of total orders on the product
	 * @param int new inventory count
	 */
	static public function update_inventory_count(string $product_hash, int $new_inventory_count): void
	{
		if (!self::product_exists($product_hash)) {
			throw new ProductNotFoundException();
		}

		$num_orders = self::get_order_count($product_hash);
		$UNLIMITED = 0;
		if ($num_orders > $new_inventory_count && $new_inventory_count !== $UNLIMITED) {
			throw new ModifyProductException("Cannot set inventory count lower than number of orders.\nMinimum value is " . $num_orders . " or 0 for unlimited.\nReceived: " . $new_inventory_count);
		}

		$db = new DB("web");
		$db->prepare("UPDATE products SET amount_available=? WHERE hash=?");
		$db->bind_param("is", $new_inventory_count, $product_hash);
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
		$db->bind_result($result);
		$db->fetch();
		if ($result !== 0 && $result !== 1) {
			throw new UnexpectedValueException((string)$result);
		}
		return (bool)$result;
	}


	/**
	 * Add new product to db
	 * 
	 * @param array $product to be added
	 * @return void
	 */
	static public function add_product(array $product): void
	{
		$db = new DB("web");
		$sql = "INSERT INTO products
		(
			hash,
			name,
			description,
			image,
			available_from,
			available_until,
			max_orders_per_customer_per_year,
			require_phone,
			require_email,
			require_comment,
			require_active_membership,
			amount_available,
			price,
			price_member,
			visible,
			enabled
		) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$db->prepare($sql);
		$db->bind_param(
			"ssssssiiiiiiiiii",
			$product["hash"],
			$product["name"],
			$product["description"],
			$product["image"],
			$product["available_from"],
			$product["available_until"],
			$product["max_orders_per_customer_per_year"],
			$product["require_phone"],
			$product["require_email"],
			$product["require_comment"],
			$product["require_membership"],
			$product["inventory_count"],
			$product["price"],
			$product["price_member"],
			$product["visible"],
			$product["enabled"]
		);
		$db->execute();
	}


	/**
	 * Remove product from DB
	 * 
	 * @param array $product to be removed
	 * @throws ProductNotFoundException if the product is not found
	 * @throws RemoveProductFailedException if the product could not be removed
	 * @return void
	 */
	static public function remove_product(string $product_hash): void
	{
		if (!self::product_exists($product_hash)) {
			throw new ProductNotFoundException();
		}
		// get image name
		$db = new DB("web");
		$db->prepare("SELECT image FROM products WHERE hash=?");
		$db->bind_param("s", $product_hash);
		$db->execute();
		$image_path = "img/store/" . $db->fetch();
		if (file_exists($image_path)) {
			if (!unlink($image_path)) {
				throw new RemoveProductFailedException();
			}
		}
		$db->reset();
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
	public static function get_product_hash(int &$product_id): string
	{
		$db = new DB("web");
		$db->prepare("SELECT hash FROM products WHERE id=?");
		$db->bind_param("i", $product_id);
		$db->execute();
		$product_hash = "";
		$db->bind_result($product_hash);
		$db->fetch();
		return $product_hash;
	}

	/**
	 * Get database order id from stripe payment intent string
	 * 
	 * @param string $paymentIntent_id Stripe payment intent
	 * @return int database row id of the order
	 */
	public static function get_order_id(string $paymentIntent_id): int
	{
		$db = new DB("web");
		$sql = "SELECT id FROM orders WHERE source_id=?";
		$db->prepare($sql);
		$db->stmt->bind_param("s", $paymentIntent_id);
		$db->execute();
		$result = 0;
		$db->bind_result($result);
		$db->fetch();
		return $result;
	}

	/**
	 * Update order to FINALIZED in db
	 * Side effects:
	 * - if order was used to purchase a license then member is approved. Following it's side effects.
	 * 
	 * @see Store_helper::approve_member() FIXME
	 * @param string $payment_intent_id
	 * @throws ProductNotFoundException if the product is not found
	 * @return void
	 */
	function finalize_order(string $payment_intent_id): void
	{
		$db = new DB("web");
		$db->prepare("SELECT id, products_id, phone AS products_id FROM orders WHERE source_id=?");
		$db->bind_param("s", $payment_intent_id);
		$db->execute();
		$db->bind_result($order_id, $product_id, $phone);

		if ($db->fetch() === NULL) {
			throw new ProductNotFoundException();
		}

		self::set_order_status($order_id, "FINALIZED");

		// Member registration hook
		if (self::get_product_hash($product_id) === $this->license_key) {
			Member::approve($phone);
		}
	}


	/**
	 * Set order status
	 *
	 * @param integer $order_id row identifier in the database.
	 * @param string $status allowed input: 'FINALIZED' | 'DELIVERED' | 'FAILED'
	 * @throws \InvalidArgumentException
	 * @return void
	 * @note @param int $order_id should not be confused with stripe id system witch uses strings as identifier.
	 */
	static function set_order_status(int $order_id, string $status): void
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
	function set_product_visibility(int $product_id, bool $visibility): void
	{
		$db = new DB("web");
		$db->prepare("UPDATE products SET visible=? WHERE id=?");
		$db->bind_param("ii", $visibility, $product_id);
		$db->execute();
	}


	/**
	 * Get order status
	 *
	 * @param string $paymentIntent_id
	 * @return string only 'FINALIZED' | 'DELIVERED' | 'FAILED'
	 * @throws OrderNotFoundException if order is not found.
	 */
	function get_order_status(string $paymentIntent_id): string
	{
		$db = new DB("web");
		$db->prepare("SELECT order_status FROM orders WHERE source_id=?");
		$db->bind_param("s", $paymentIntent_id);
		$db->execute();
		$db->fetch();
		$db->bind_result($order_status);
		if ($order_status !== 'FINALIZED' && $order_status !== 'DELIVERED' && $order_status !== 'FAILED') {
			throw new OrderNotFoundException();
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
		$db->bind_result($result);
		$db->fetch();
		if ($result !== 0 && $result !== 1) {
			throw new \Exception("Unexpected value received $result");
		}
		return (bool)$result;
	}

	// section orders

	/**
	 * Create an order
	 * TODO: change parameters to accept only object $order where
	 * order.product, order.customer and optionally order.comment exists.
	 *
	 * @param string $product_hash of the product to be purchased
	 * @param string $payment_method_id id of the payment method for the customer
	 * @param array $customer containing name and optionally email and phone
	 * @param string $comment optional argument if comment is attached with the order
	 * @throws \InvalidArgumentException if customer->phone is not a valid phone number
	 * @throws \Stripe\Exception\ApiErrorException if request to stripe fails
	 * @throws CustomerIsNotMemberException if customer does not have required membership
	 * @throws MaxOrdersExceededException if customer cannot purchase any more of this product
	 * @throws MissingCustomerDetailsException if customer is missing some information
	 * @throws PriceErrorException if attempting to perform a purchase where the price is outside of allowed range
	 * @throws ProductNotAvailableException if the product is currently not available
	 * @throws ProductNotEnabledException when product cannot be purchased
	 * @throws ProductSoldOutException if the product is sold out
	 * @return array containing response. Should be a Response object instead tbh.
	 */
	function create_order(string $product_hash, string $payment_method_id, array $customer, ?string $comment = NULL): array
	{
		$name = $customer["name"];
		$email = NULL;
		$phone = NULL;
		if (!empty($customer["email"])) {
			$email = $customer["email"];
		}
		if (!empty($customer["phone"])) {
			$phone = $customer["phone"];
			$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
			$parsedPhone = $phoneUtil->parse($phone);
			$isValid = $phoneUtil->isValidNumber($parsedPhone);
			if (!$isValid) {
				throw new \InvalidArgumentException("Could not validate phone number");
			}
		}

		$product = $this->get_product($product_hash);
		if (!self::product_exists($product_hash)) {
			throw new ProductNotFoundException("product not found");
		}
		if (!$product["enabled"]) throw new ProductNotEnabledException();

		// amount available 0 => unlimited
		if ($product["amount_available"] !== NULL && $product["amount_available"] !== 0) {
			if ($product["amount_sold"] >= $product["amount_available"]) {
				throw new ProductSoldOutException();
			}
		}

		if ($product["available_from"] !== NULL && $product["available_from"] > time()) throw new ProductNotAvailableException("Current product is not yet available");
		if ($product["available_until"] !== NULL && $product["available_until"] < time()) throw new ProductNotAvailableException("Current product is no longer available");
		if (empty($name)) throw new MissingCustomerDetailsException("Missing customer name");
		if (empty($phone) && $product["require_phone"]) throw new MissingCustomerDetailsException("A phone number is required for this purchase");
		if (empty($email) && $product["require_email"]) throw new MissingCustomerDetailsException("An email is required for this purchase");
		if (empty($comment) && $product["require_comment"]) throw new MissingOrderDetailsException("A comment is required for this purchase");

		// Note: Only checking purchases for current year
		if ($product["max_orders_per_customer_per_year"] !== NULL) {
			if (empty($phone)) {
				throw new MissingCustomerDetailsException("A phone number is required for this purchase");
			}
			if ($product["max_orders_per_customer_per_year"] < self::completed_orders($product_hash, $phone)) {
				throw new MaxOrdersExceededException();
			}
		}

		if ($product["require_active_membership"]) {
			if (empty($phone)) {
				throw new MissingCustomerDetailsException("A phone number is required for this purchase");
			}

			if (!Member::is_active($phone)) {
				throw new CustomerIsNotMemberException();
			}
		}

		// charge member price if customer has an active membership
		$price = $product["price"];
		if (!empty($product["price_member"]) && Member::is_active($phone)) {
			$price = min($product["price_member"], $product["price"]);
		}

		if ($price <= 3) { // 3 NOK - minimum chargeable amount
			throw new PriceErrorException("Cannot charge below minimum charge amount");
		}

		if ($price >= 2000) { // 2000 NOK failsafe
			mail(Settings::get_instance()->get_email_address("developer"), "Charge blocked", "A charge of $price NOK has been blocked. Check the logs");
			throw new PriceErrorException("Cannot charge this amount. Contact developers if this is a mistake");
		}

		//Perform a 3D secure checkout
		$intent = \Stripe\PaymentIntent::create([
			"payment_method" => $payment_method_id,
			"amount" => $price * 100, // convert from Norwegian krone to Norwegian øre
			"currency" => "nok",
			"confirmation_method" => "manual",
			"confirm" => true,
			"receipt_email" => $email,
			"description" => $product["name"],
			"metadata" => [
				"comment" => $comment,
				"product_hash" => $product_hash,
				"product_name" => $product["name"],
				"is_member" => Member::is_active($phone),
			],
		]);

		// Save order
		$db = new DB("web");
		$sql = "INSERT INTO orders (products_id, name, email, phone, source_id, comment) VALUES (?, ?, ?, ?, ?, ?)";
		$db->prepare($sql);
		$intent_id = $intent["id"];
		$db->bind_param("isssss", $product["id"], $name, $email, $phone, $intent_id, $comment);
		$db->execute();

		\Stripe\PaymentIntent::update($intent["id"], ["metadata" => ["order_id" => $db->inserted_id()]]);

		if ($intent->status === "requires_action" && $intent->next_action->type === "use_stripe_sdk") {
			return [
				"requires_action" => true,
				"payment_intent_client_secret" => $intent->client_secret,
			];
		} else if ($intent->status === "succeeded") {
			$this->finalize_order($intent["id"]);
			return [
				"success" => true,
				"error" => false,
				"message" => "Purchase succeeded.\nYou've been charged " . $price . " NOK.",
			];
		} else {
			throw new \Exception("Stripe error");
		}
	}

	/**
	 * Get number of successful orders.
	 *
	 * @param string $product_hash of product in question
	 * @param string $phone of the customer
	 * @param boolean $thisYear if true then only orders purchased current year are counted
	 * @throws InvalidArgumentException If phone number is not a valid phone number
	 * @note returns only completed orders this year unless @param this_year is set overwritten false
	 * @return integer number of purchases
	 */
	static public function completed_orders(string $product_hash, string $phone, bool $this_year = true): int
	{
		$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
		$parsedPhone = $phoneUtil->parse($phone);
		$isValid = $phoneUtil->isValidNumber($parsedPhone);
		if (!$isValid) {
			throw new InvalidArgumentException("Could not validate phone number");
		}

		$db = new DB("web");
		$sql = "SELECT COUNT(*) FROM orders WHERE products_id = (SELECT id FROM products WHERE hash=?) AND phone=? AND (order_status='DELIVERED' OR order_status='FINALIZED')";
		if ($this_year) {
			$sql .= "AND EXTRACT(YEAR FROM timestamp) = YEAR(NOW())";
		}
		$db->prepare($sql);
		$db->bind_param("ss", $product_hash, $phone);
		$db->execute();
		$db->bind_result($result);
		$db->fetch();
		return $result;
	}
}
