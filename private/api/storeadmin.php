<?php
// TODO: collect all variables at the start.
// TODO: All results in json
// TODO: get action and switch it instead of $_GET["type"]
// TODO: refactor db access
Authenticator::auth_API("api/storeadmin", "", __FILE__, __LINE__);

include_once("library/util/db.php");
include_once("library/util/store_helper.php");

$store = new StoreHelper($language);
header("Content-Type: application/json");

log::message("Incoming POST request", __FILE__, __LINE__);
foreach ($_POST as $key => $value)
{
	log::message("$key : $value", __FILE__, __LINE__);
}

switch ($_GET["type"]) {
	case "mark_delivered":
		$item = $store->get_item($_REQUEST["item_id"]);
		if ($item == false) {
			print json_encode([
				"error" => "Item id not found",
			]);
			return;
		}
		$id = $_REQUEST["id"];

		$store->update_status($id, "DELIVERED");
		print json_encode([
			"success" => true,
		]);
		break;
	case "set_visibility":
		$item = $store->get_item($_REQUEST["item_id"]);
		if ($item == false) {
			print json_encode([
				"error" => "Item id not found"
			]);
			return;
		}
		$visibility = filter_var($_REQUEST["visibility"], FILTER_VALIDATE_BOOLEAN);
		$store->set_visibility($item["id"], $visibility);
		print json_encode([
			"success" => true,
		]);
		break;
	case "add_item":
		$name = array(
			"no" => $_POST["name_no"],
			"en" => $_POST["name_en"]
		);
		$desc = array(
			"no" => $_POST["description_no"],
			"en" => $_POST["description_en"]
		);
		$price = intval($_POST["price"]);
		$amount = intval($_POST["amount"]);
		if ($amount == 0) $amount = null;
	
		$start = ($_POST["startdate"] . " " . $_POST["starttime"]);
		$end = ($_POST["enddate"] . " " . $_POST["endtime"]);
		if ($start != " " && strtotime($start) !== false) $start = date("Y-m-d H:i:s", strtotime($start));
		else $start = null;
		if ($end != " " && strtotime($end) !== false) $end = date("Y-m-d H:i:s", strtotime($end));
		else $end = null;
	
		$image = $_POST['image'];
		// Stores the filename as it was on the client computer.
		$imagename = $_FILES['image']['name'];
		// Stores the filetype e.g image/jpeg
		$imagetype = $_FILES['image']['type'];
		// Stores any error codes from the upload.
		$imageerror = $_FILES['image']['error'];
		// Stores the tempname as it is given by the host when uploaded.
		$imagetemp = $_FILES['image']['tmp_name'];
	
		//The path you wish to upload the image to
		$imagePath = "img/store/";
	
		if (is_uploaded_file($imagetemp)) {
			if (!move_uploaded_file($imagetemp, $imagePath . $imagename)) {
				echo "Failed to move your image.";
			}
		} else {
			//Default image
		}
	
		// set a random hash to be id.
		$api_id = substr(hash("md5", time()), 0, 20);
	
		$conn = connect("web");
		$sql = "INSERT INTO store_items (api_id, name, description, price, available_from, available_until, amount_available, image) VALUES(?, ?, ?, ?, ?, ?, ?, ?)";
		$query = $conn->prepare($sql);
		if (!$query->bind_param("sssissis", $api_id, json_encode($name), json_encode($desc), $price, $start, $end, $amount, $imagename)) {
			print $query->error;
		}
		if (!$query->execute()) {
			print $query->error;
		}
		$query->close();
		$conn->close();
	
		$access_control->log("admin/store", "created item", $api_id);
		break;
	case "get_item":
		$item_id = argsURL("GET", "item_id");
		$item = $store->get_item($item_id);
		if ($item === false) {
			print json_encode(["error" => "That item does not exist"]);
			return;
		}
		include_once("library/util/db.php");
		$conn = connect("web");
		$sql = "SELECT id, name, email, phone, kommentar, order_status FROM store_orders WHERE item_id=? AND (order_status='FINALIZED' OR order_status='DELIVERED') ORDER BY FIELD(order_status, 'FINALIZED', 'DELIVERED')";
		$query = $conn->prepare($sql);
		if (!$query) {
			log::die("Failed to prepare query in store", __FILE__, __LINE__);
		}

		$query->bind_param("i", $item["id"]);
		if (!$query) {
			log::die("Failed to bind parameters in store", __FILE__, __LINE__);
		}

		$query->execute();
		if (!$query) {
			log::die("Failed to execute query", __FILE__, __LINE__);
		}

		$query->bind_result($id, $name, $email, $phone, $kommentar, $status);
		if (!$query) {
			log::die("Failed to bind results", __FILE__, __LINE__);
		}
		$result = array();
		while ($query->fetch()) {
			$row = [
				"id" => $id,
				"name" => $name,
				"email" => $email,
				"phone" => $phone,
				"comment" => $kommentar,
				"status" => $status
			];
			array_push($result, $row);
		}
		$query->close();
		$conn->close();
		print json_encode($result);
		break;

	case "get_items":
		// fetch store items from db
		// start, limit, api_id, raw_data, visiblity_check
		$items = $store->get_items(0, 100, "", false, false);
		$conn = connect("web");
		$sql = "SELECT id, name FROM store_groups";
		$query = $conn->prepare($sql);
		$query->execute();
		$query->bind_result($id, $name);
		$groups = [];
		while ($query->fetch()) {
			$groups[$id] = $name;
		}
		$query->close();
		$conn->close();
		print json_encode($groups);
		break;
	default:
		print json_encode([
			"error" => "type '" . $_GET["type"] . "' is not a type",
		]);
}
