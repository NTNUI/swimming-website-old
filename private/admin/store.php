<?php
global $settings;
// TODO: remove unused html classes
?>

<link href="<?php print($settings['baseurl']); ?>/css/admin/store.css" />
<link href="https://unpkg.com/tabulator-tables@4.5.3/dist/css/tabulator.min.css" rel="stylesheet" />
<script type="text/javascript" src="https://unpkg.com/tabulator-tables@4.5.3/dist/js/tabulator.min.js"></script>
<script type="text/javascript" src="https://momentjs.com/downloads/moment.min.js"></script>
<script type="text/javascript" src="<?php print($settings['baseurl']); ?>/js/admin/store.js"></script>

<?php include_once("library/util/store_helper.php");
$store = new StoreHelper($language);

$item_id = argsURL("GET", "item_id");
$add = argsURL("POST", "add");

if ($item_id) {
	$item = $store->get_item($item_id);
	if ($item === false) {
		print "Den tingen er ikke i butikken";
		return;
	}
	print "<div class='box'><h2>Liste over kjøp av " . $item["name"] . "</h2>";

	// Get order information for item_id
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

	while ($query->fetch()) {
		$name = htmlspecialchars($name);
		$email = htmlspecialchars($email);
		print "<div id='box-$id' class='box" . ($status == "DELIVERED" ? " green" : "") . "'>";
		print "<h3>$name</h3>";
		print "Kontakt: &lt;<a href='mailto:$email'>$email</a>&gt; (Tlf: $phone)<br>";
		if ($kommentar != "") {
			print "Kommentar: " . htmlspecialchars($kommentar) . "<br>";
		}
		if ($status == "FINALIZED") print "<button onclick='mark_delivered(\"" . $_GET["item_id"] . "\", $id)' id='button-$id'>Sett levert</button><br>";
		if ($status == "DELIVERED") print "Utlevert<br>";

		print "</div>";
	}
	print("</div>");
	$query->close();
	$conn->close();
?>

<?php
} else {
	if (isset($_POST["add"])) {
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
	}

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
?>
	<div class="box">
		<h3>
			Salgsvarer
		</h3>
		<label for="">Dobbeltklikk for å se salg</label>
		<div id="items"></div>

	</div>


	<div id="add_container">
		<div class="box">
			<h3>Priskalkulator</h3>
			<label for="">Pris uten avgifter</label>
			<br>
			<input type="number" name="" value="0" id="price-input" style="width: 50%;">
			<br>
			<label for="">Pris med avgifter: </label>
			<label for="" id="price-output"></label>

		</div>

		<div class="box">
			<h3>Legg til ny ting i butikken</h3>
			<form method="POST" enctype="multipart/form-data">
				<label for="name_no">Tittel (Norsk):</label>
				<input name="name_no" type="text" required />
				<label for="name_en">Tittel (Engelsk):</label>
				<input name="name_en" type="text" />
				<label for="description_no">Beskrivelse (Norsk): (HTML er støttet)</label>
				<textarea name="description_no"></textarea>
				<label for="description_en">Beskrivelse (Engelsk):</label>
				<textarea name="description_en"></textarea>
				<label for="price">Pris (i øre)</label>
				<input name="price" type="number" min="100" required />
				<label for="amount">Maks antall (Tom for evig antall):</label>
				<input name="amount" type="number" min="1" />
				<label for="starttid">Tilgjengelig i tidsrommet (la være blank hvis alltid åpen)</label>
				<div class="form-row">
					<input type="date" name="startdate" placeholder="mm-dd-åååå" style="width: unset" />
					<input type="time" name="starttime" placeholder="13:00" style="width: unset" />
					til
					<input type="date" name="enddate" placeholder="mm-dd-åååå" style="width: unset" />
					<input type="time" name="endime" placeholder="13:00" style="width: unset" />
				</div>
				<label for="image">Bilde</label>
				<input name="image" type="file" accept="image/*" />
				<button name="add" type="submit">Legg til</button>
			</form>
		</div>
	</div>
	<script type="text/javascript">
		function createTable() {
			const store_data = <?php print json_encode($items); ?>;
			const groups = <?php print json_encode($groups); ?>;
			createTableMatrix(store_data, groups);
		}

		addLoadEvent(createTable);
		addLoadEvent(() => {
			document.getElementById("price-input").addEventListener("change", function() {
				document.getElementById("price-output").innerHTML = (document.getElementById("price-input").value * 1.012 + 1.8);
			});
		});
	</script>
<?php }
