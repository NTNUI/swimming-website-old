<?php
if (isset($_POST["name"])
	&& isset($_POST["year"])
	&& isset($_POST["email"])
//	&& isset($_POST["phone"]) -- not required
	&& isset($_POST["description"])) {
	$name = $_POST["name"];
	$year = $_POST["year"];
	$email = $_POST["email"];
	$phone = isset($_POST["phone"]) ? $_POST["phone"] : 0;
	$description = $_POST["description"];
	$conn = connect("web");
	$query = $conn->prepare("INSERT INTO alumni (name, year, email, phone, description) VALUES (?, ?, ?, ?, ?)");
	$query->bind_param("sisis", $name, $year, $email, $phone, $description);

	$query->execute();
	$query->close();
	$conn->close();
} 
?>
<div class="box">
<button onclick="useBoxes()">Bruk bokser</button>
<button onclick="useMatrix()">Bruk matrise</button>
</div>
<?php
$conn = connect("web");
$query = $conn->prepare("SELECT name, year, email, phone, description FROM alumni");
$query->execute();
$query->bind_result($name, $year, $email, $phone, $description);
$result = array();
print "<div id='boxes' class='hidden'>";
while ($query->fetch()) {
	$result[] = array(
		"name" => $name,
		"year" => $year,
		"email" => $email,
		"phone" => ($phone == 0 ? "<not given>" : $phone),
		"description_b64" => base64_encode($description),
	);
	if ($phone == 0) $phone = "&lt;not given&gt;";
	print "<div class='box'>";
	print "<h3>$name ($year)</h3>";
	print "Kontakt: <a href='mailto:$email'>$email</a> | Tlf: $phone<br>";
	print "<p>$description</p>";
	print "</div>";
}
print "</div>";
?>

<div class="box green" id="matrix">
	<h1>Alumni Matrise</h1>
	<div id="table"></div>
</div>
<link href="https://unpkg.com/tabulator-tables@4.5.3/dist/css/tabulator.min.css" rel="stylesheet">
<script type="text/javascript" src="https://unpkg.com/tabulator-tables@4.5.3/dist/js/tabulator.min.js"></script>
<script type="text/javascript">
function b64DecodeUnicode(str) {
    // Going backwards: from bytestream, to percent-encoding, to original string.
    return decodeURIComponent(atob(str).split('').map(function(c) {
        return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
    }).join(''));
}
const alumni_data = JSON.parse('<?php print json_encode($result) ?>');
for (let i in alumni_data) {
	alumni_data[i].description = b64DecodeUnicode(alumni_data[i].description_b64);
	alumni_data[i].description = alumni_data[i].description.replace(/(?:\r\n|\r|\n)/g, "<br>");
	delete alumni_data[i].description_b64;
}
const table = new Tabulator("#table", {
	layout: "fitDataStretch",
	data: alumni_data,
	columns: [
		{title: "Navn", field: "name"},
		{title: "Aktiv i år", field: "year"},
		{title: "Kontakt", columns: [
			{title: "Epost", field: "email"},
			{title: "Tlf", field: "phone"},
		]},
	],
	rowFormatter: (row) => {
		var holderEl = document.createElement("div");
		var tableEl = document.createElement("div");

		holderEl.style.boxSizing = "border-box";
		holderEl.style.padding = "10px 30px 10px 10px";
		holderEl.style.borderTop = "1px solid #333";
		holderEl.style.borderBotom = "1px solid #333";
		holderEl.style.background = "#ddd";

		tableEl.style.border = "1px solid #333";

		holderEl.appendChild(tableEl);

		row.getElement().appendChild(holderEl);

		tableEl.innerHTML = row.getData().description;
	},
});

function useMatrix() {
	document.getElementById("boxes").classList.add("hidden");
	document.getElementById("matrix").classList.remove("hidden");
}

function useBoxes() {
	document.getElementById("boxes").classList.remove("hidden");
	document.getElementById("matrix").classList.add("hidden");
}
</script>

<div class="box">
<form method="POST">
	<label for="name">Navn:</label>
	<input type="text" name="name" />
	<label for="year">Aktiv i år</label>
	<input type="number" name="year" value="<?php print date("Y"); ?>"/>
	<label for="email">Privat epost</label>
	<input type="email" name="email" />
	<label for="phone">Privat telefon</label>
	<input type="number" name="phone" />
	<label for="description">Beskrivelse:</label>
	<textarea name="description"></textarea>
	<button type="submit">Send inn</button>
</form>
</div>
