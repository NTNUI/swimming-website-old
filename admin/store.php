<?php include_once("library/util/store_helper_v2.php");
$store = new StoreHelper($language);
if (isset($_GET["item_id"])) {
	$item = $store->get_item($_GET["item_id"]);
	if ($item === false) {
		print "Den tingen er ikke i butikken";
		return;
	}
	print "<h1>Liste over kjøp av ". $item["name"] . "</h1>";
	include_once("library/util/db.php");
	$conn = connect("web");
	$sql = "SELECT id, name, email, phone, kommentar, order_status FROM store_orders WHERE item_id=? AND (order_status='FINALIZED' OR order_status='DELIVERED') ORDER BY FIELD(order_status, 'FINALIZED', 'DELIVERED')";
	$query = $conn->prepare($sql);
	$query->bind_param("i", $item["id"]);
	$query->execute();
	$query->bind_result($id, $name, $email, $phone, $kommentar, $status);
	while ($query->fetch()) {
		$name = htmlspecialchars($name);
		$email = htmlspecialchars($email);
		print "<div id='box-$id' class='box" . ($status == "DELIVERED" ? " green" : "") . "'>";
		print "<h1>$name</h1>";
		print "Kontakt: &lt;<a href='mailto:$email'>$email</a>&gt; (Tlf: $phone)<br>";
		if ($kommentar != "") {
			print "Kommentar: " . htmlspecialchars($kommentar) . "<br>";
		}
		if ($status == "FINALIZED") print "<button onclick='mark_delivered(\"" . $_GET["item_id"] . "\", $id)' id='button-$id'>Sett levert</button><br>";
		if ($status == "DELIVERED") print "Utlevert<br>";

		print "</div>";
	}
	$query->close();
	$conn->close();
?>
<style>
.box {
	padding: 0px;
	margin: 0px;
}
button:disabled {
	background-color: darkgray;
	cursor: default;
}

h2 {
	margin: 0;
}
</style>


<script type="text/javascript">
function mark_delivered(item_id, id) {
	fetch("https://org.ntnu.no/svommer/api/storeadmin?type=mark_delivered&item_id=" + item_id + "&id=" + id)
		.then((data) => data.json)
		.then((json) => {
			if (json.error) {
				alert(json.error);
			} else {
				//alert("OK, refresh to see result");
				document.getElementById("box-" + id).classList.add("green");
				document.getElementById("button-" + id).disabled = true;
				document.getElementById("button-" + id).innerText = "Satt som levert";	
			}
		})	
}
</script>
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
	//Stores the filename as it was on the client computer.
	$imagename = $_FILES['image']['name'];
	//Stores the filetype e.g image/jpeg
	$imagetype = $_FILES['image']['type'];
	//Stores any error codes from the upload.
	$imageerror = $_FILES['image']['error'];
	//Stores the tempname as it is given by the host when uploaded.
	$imagetemp = $_FILES['image']['tmp_name'];

	//The path you wish to upload the image to
	$imagePath = "img/store/";

	if(is_uploaded_file($imagetemp)) {
		if(!move_uploaded_file($imagetemp, $imagePath . $imagename))  {
		    echo "Failed to move your image.";
		}
	} else {
		//Default image
	}

	// set a random hash to be id.
	$api_id = substr(hash("md5", time()),0,20);

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

// 			start, limit, api_id, raw_data, visiblity_check
$items = $store->get_items(0, 100, "", false, false);
?>
<style>
.weak {
	color: rgba(0, 0, 0, 0.4);
}
</style>
<table style="width: 100%;">
	<tr>
		<th>Tittel</th>
		<th>Antall kjøpt</th>
		<th>Antall tilgjengelig</th>
		<th>Pris</th>
		<th>Tiltak</th>
	<tr>
	<?php foreach ($items as $item) { 
		$class = "";
		$visibility = $item["visibility"];
		if (!$visibility) $class = "weak";
		print "<tr id='item-" . $item["api_id"] . "' class='$class'>";
		print "<td>" . $item["name"] . "</td>";
		print "<td><a href='?item_id=" . $item["api_id"] . "'>" . $item["amount_bought"] . "</a></td>";
		print "<td>" . ($item["amount_available"] == NULL ? "uendelig" : $item["amount_available"]) . "</td>";
		print "<td>" . $item["price"] . "</td>"; 
		print "<td>";
		print "<button id='vistoggle-" . $item["api_id"] . "' onclick='set_visibility(\"" . $item["api_id"] . "\", " . !$visibility . ")'>" . ($visibility ? "Gjem" : "Vis" ) . "</button>";
?>
		<button>slett</button>
<?php
		print "</td>";
}?>
</table>


<div id="add_container">
	<div class="green box">
		<h1>Legg til ny ting i butikken</h1>
	</div>
	<div class="box">
		<label for="">Pris uten avgifter</label>
		<br>
		<input type="number" name="" value="0" id="price-input" style="width: 50%;">
		<br>
		<label for="">Pris med avgifter: </label>
		<label for="" id="price-output"></label>
		
	</div>

	<form method="POST" enctype="multipart/form-data">
		<label for="name_no">Tittel (Norsk):</label>
		<input name="name_no" type="text" required/>
		<label for="name_en">Tittel (Engelsk):</label>
		<input name="name_en" type="text"/>
		<label for="description_no">Beskrivelse (Norsk): (<a href="https://en.wikipedia.org/wiki/Cross-site_scripting" target="_blank">HTML er støttet</a>)</label>
		<textarea name="description_no"></textarea>
		<label for="description_en">Beskrivelse (Engelsk):</label>
		<textarea name="description_en"></textarea>
		<label for="price">Pris (i øre)</label>
		<input name="price" type="number" min="100" required/>
		<label for="amount">Maks antall (Tom for evig antall):</label>
		<input name="amount" type="number" min="1"/>
		<label for="starttid">Tilgjengelig i tidsrommet (la være blank hvis alltid åpen)</label>
		<div class="form-row">
			<input type="date" name="startdate" placeholder="mm-dd-åååå" style="width: unset"/>
			<input type="time" name="starttime" placeholder="13:00" style="width: unset"/>
			til
			<input type="date" name="enddate" placeholder="mm-dd-åååå" style="width: unset"/>
			<input type="time" name="endime" placeholder="13:00" style="width: unset"/>
		</div>
		<label for="image">Bilde</label>
		<input name="image" type="file" accept="image/*"/>
		<button name="add" type="submit">Legg til</button>
	</form>
</div>
<script type="text/javascript">

window.onload = function (){	
	document.getElementById("price-input").addEventListener("change", function (){
		document.getElementById("price-output").innerHTML = (document.getElementById("price-input").value * 1.012 + 1.8);
	});
}

function set_visibility(id, visibility) {
	fetch("https://org.ntnu.no/svommer/api/storeadmin?type=set_visibility&item_id=" + id + "&visibility=" + visibility)
	.then((data) => data.json())
	.then((json) => {
		if (json.error) {
			alert("Noe gikk galt: " + json.error);
		} else if (json.success) {
			document.getElementById("vistoggle-" + id).innerText = visibility ? "Gjem" : "Vis";
			document.getElementById("vistoggle-" + id).onclick = function() { set_visibility(id, !visibility); }
			const row = document.getElementById("item-" + id);
			if (visibility) {
				row.classList.remove("weak");
			} else {
				row.classList.add("weak");
			}
		} else {
			alert("Rart svar fra server, se konsoll");
			console.log(json);
		}
	})
}
</script>
<?php }
