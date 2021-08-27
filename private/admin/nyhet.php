<?php 
global $settings;
$content = argsURL("POST", "content");
$header = argsURL("POST", "header");
$author = argsURL("SESSION", "name");

if ($content != "" and $header != "" and $author != "") {

	include_once("library/util/db.php");
	$conn = connect("web");
	$sql = "INSERT INTO forside (overskrift, innhold, av, tid) VALUES (?, ?, ?, NOW())";
	$query = $conn->prepare($sql);
	if (!$query) {
		log::die("could not prepare statement", __FILE__, __LINE__);
	}
	$query->bind_param("sss", $header, $content, $author);
	if (!$query) {
		log::die("could not bind parameters", __FILE__, __LINE__);
	}
	if (!$query->execute()) {
		log::die("Could not execute query", __FILE__, __LINE__);
	}

	$query->close();
	$conn->close();
}

?>
<div class="box">
	<form action="nyhet" method="POST">
		<label for="header">Overskrift:</label>
		<input name="header" id="inputHeader" type="text" placeholder="Overskrift" required />
		<label for="content">Melding:</label>
		<textarea name="content" id="inputContent" type="text" placeholder="Melding" required style="width: 100%; height: 200px"></textarea>
		<input type="submit" value="Send inn" />
	</form>
</div>

<div class="box">
	<h2>Forhåndsvisning:</h2>
</div>
<div class="box">
	<div id="preview" class="box">
		<h3 id="header"></h3>
		<p id="content"></p>
		<small>Av: <span id="author"><?php print $author ?></span> Tid <span id="time"></span>
	</div>
</div>
<script type="text/javascript" src="<?php print $settings['baseurl'];?>/js/admin/nyhet.js"></script>