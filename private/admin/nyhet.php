<?php
$content = $_POST['content'];
$header = $_POST['header'];
$author = $_SESSION['navn'];

if ($content != "" and $header != "" and $author != "") {

	include_once("library/util/db.php");
	$conn = connect("web");

	$sql = "INSERT INTO forside (overskrift, innhold, av, tid) VALUES (?, ?, ?, NOW())";
	$query = $conn->prepare($sql);
	$query->bind_param("sss", $header, $content, $author);
	if ($query->execute()) {
		print "Innlegg postet";
	} else {
		print "Noe gikk til helvete";
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

<script type="text/javascript">
	var header = document.getElementById("header");
	var content = document.getElementById("content");
	var time = document.getElementById("time");

	setInterval(function() {
		var str = new Date().toISOString().replace("T", " ");
		time.innerHTML = str.substr(0, str.lastIndexOf("."));
	}, 250);
	document.getElementById("inputHeader").onkeyup = function(e) {
		header.innerHTML = document.getElementById("inputHeader").value;
	}
	document.getElementById("inputContent").onkeyup = function(e) {
		content.innerHTML = document.getElementById("inputContent").value;
	}
</script>