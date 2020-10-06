<?php

class StoreHelper {
	private $language;
	private $licence_key = "NSFLicence2019";
	function __construct($lang) {
		//Set API key
		\Stripe\Stripe::setApiKey("sk_live_r51zgekQ1xLUvPavrfCwCqRo");
		//\Stripe\Stripe::setApiKey("sk_test_8NXxtSWrHXG33iGpr07ec3fo");


		$this->language = $lang;
	}

	function get_items($start = 0, $limit = 10, $id = "", $rawData = false, $visibility_check=true) {
		$language = $this->language;
		include_once("library/util/db.php");
		$mysqli = connect("web");
		$visibility = "";
		if ($visibility_check) $visibility = "WHERE visible=TRUE";
		
		if ($id == "") {
			$sql = "SELECT id, api_id, name, description, price, available_from, available_until, require_phone, visible, (SELECT COUNT(*) FROM store_orders WHERE store_orders.item_id = store_items.id AND (store_orders.order_status='FINALIZED' OR store_orders.order_status='DELIVERED')) AS amount, amount_available, image, group_id FROM store_items AS store_items $visibility ORDER BY visible DESC, id DESC LIMIT ? OFFSET ?"; 

			$query = $mysqli->prepare($sql);
			$query->bind_param("ii", $limit, $start);

		} else {
			$sql = "SELECT id, api_id, name, description, price, available_from, available_until, require_phone, visible, (SELECT COUNT(*) FROM store_orders WHERE store_orders.item_id = store_items.id AND (store_orders.order_status='FINALIZED' OR store_orders.order_status='DELIVERED')) AS amount, amount_available, image, group_id FROM store_items AS store_items WHERE api_id=? ORDER BY id DESC LIMIT ? OFFSET ?";
			$query = $mysqli->prepare($sql);
			$query->bind_param("sii", $id, $limit, $start);
		}

		if (!$query->execute()) return false;
		
		$result = array();
		$query->bind_result($id, $api_id, $name, $description, $price, $available_from, $available_until, $require_phone, $visibility, $amount, $amount_available, $image, $group_id);

		while ($query->fetch()) {
			if (!$rawData) {
				$name = json_decode($name);
				if (array_key_exists($language, $name)) $name = $name->$language;
				else if (array_key_exists("no", $name)) $name = $name->no;
				else $name = "";

				$description = json_decode($description);
				if (array_key_exists($language, $description)) $description = $description->$language;
				else if (array_key_exists("no", $description)) $description = $description->no;
				else $description = "";
			}

			$result[] = array(
				"id" => intval($id),
				"api_id" => $api_id,
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
				"group_id" => $group_id);

		}

		$query->close();
		$mysqli->close();

		return $result;
	}

	function get_item($id, $rawData = false) {
		if (!$id || $id == "") return false;
		$result = $this->get_items(0, 1, $id, $rawData);
		if (sizeof($result) < 1) return false;
		return $result[0];
	}

	function create_order($api_id, $paymentId, $owner, $kommentar) {
		global $base_url;
		include_once("library/util/db.php");
		$mysqli = connect("web");
		$res = array();


		$name = $owner->name;
		$email = $owner->email;
		$phone = $owner->phone;
		
		$item = $this->get_item($api_id);	
		
		if ($item === false) throw new Exception("no_such_item");
		if ($item["amount_available"] != null && $item["amount_bought"] >= $item["amount_available"]) throw new Exception("item_soldout");
		if ($item["available_from"] !== false && $item["available_from"] > time()) throw new Exception("not_available_yet");
		if ($item["available_until"] !== false && $item["available_until"] < time()) throw new Exception("no_longer_available");
		if ($name == "") throw new Exception("missing_name");
		if ($email == "") throw new Exception("missing_email");
		if ($phone == NULL && $item["require_phone"]) throw new Exception("missing_phone");


		//Perform a 3D secure checkout	
		//
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
		$query->bind_param("isssss", $item["id"], $name, $email, $phone, $intent["id"], $kommentar); 
		
		if (!$query->execute()) throw new Exception("sql_error");

		$query->close();
		
	 	$mysqli->close();
		return $this->update_order($intent);
	}

	function get_intent_by_id($id) {
		return \Stripe\PaymentIntent::retrieve($id);
	}

	function update_order($intent) {
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


	function finalize_order($intentId) {
		include_once("library/util/db.php");
		$mysqli = connect("web");
		$sql = "SELECT id, item_id, email FROM store_orders WHERE source_id=?";
		$query = $mysqli->prepare($sql);
		$query->bind_param("s", $intentId);
		$query->execute();
		$query->bind_result($id, $api_id, $email);
		if (!$query->fetch()) throw new Exception("no_such_order");

		$query->close();
		//Send email notification to confirmation email
		//$sendTo = "svommer-okonomi@ntnui.no";
		$sendTo = "olavbb@hotmail.com";
//		mail($sendTo, "New order placed", "");

		$this->update_status($id, "FINALIZED", $mysqli);
		//Member registration hook
		if ($api_id == $this->licence_key) $this->licence_control($email);
		$mysqli->close();
	}

	function licence_control($email) {
		$mysqli = connect("member");
		$sql = "UPDATE medlem_2018 SET kontrolldato=NOW() WHERE epost=?";
		$query = $mysqli->prepare($sql);
		$query->bind_param("s", $email);
		if (!$query->execute()) {
			//mail("svommer-teknisk@ntnui.no", "Error on licence rececption: $email");
		}
		$query->close();
		print "Registered";
		$mysqli->close();
	}

	function fail_order($charge) {
		//Could be changed to just deleting?
		include_once("library/util/db.php");
		$mysqli = connect("web");
		$sql = "UPDATE store_orders SET order_status='FAILED' WHERE source_id=? OR charge_id=?";
		$query = $mysqli->prepare($sql);
		$query->bind_param("ss", $charge["source"]["id"], $charge["id"]);
		$query->execute();
		$query->close();
		$mysqli->close();
	}

	function update_status($id, $status, $mysqli = NULL) {
		$close = false;
		if ($mysqli == NULL){

			$mysqli = connect("web");
			$close = true;
		}
		$sql = "UPDATE store_orders SET order_status=? WHERE id=?";
		$query = $mysqli->prepare($sql);
		$query->bind_param("si", $status, $id);
		$query->execute();
		$query->close();
		if ($close) $mysqli->close();
	}

	function set_visibility($id, $visibility) {
		$mysqli = connect("web");
		$sql = "UPDATE store_items SET visible=? WHERE id=?";
		$query = $mysqli->prepare($sql);
		$query->bind_param("ii", $visibility, $id);
		$query->execute();
		$query->close();
		$mysqli->close();
	}

	function get_status($source) {
		include_once("library/util/db.php");
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
}
