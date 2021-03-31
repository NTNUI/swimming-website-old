<?php
session_start();
if ($_SESSION['innlogget'] == 1) {

	// Connect to server
	$conn = connect("member");
	if (!$conn) {
	    die("Connection failed: " . mysqli_connect_error());
	}

	$sql = "SELECT id, regdato, etternavn, fornavn, kommentar, epost FROM ". $settings["memberTable"] . " WHERE kontrolldato IS NULL OR YEAR(kontrolldato) <> YEAR(NOW()) ORDER BY regdato";
	$query = $conn->prepare($sql);

	if (!$query->execute()) {
	    die("Connection failed: ". $query->error);
	}
	$query->bind_result($id, $regdato, $etternavn, $fornavn, $skole, $kommentar, $epost);

	$result = [];
	while($query->fetch()) {
		$etternavn = htmlspecialchars($etternavn);
		$fornavn = htmlspecialchars($fornavn);
		$kommentar = htmlspecialchars($kommentar);
		$epost = htmlspecialchars($epost);

		$interval = date_diff(date_create(), date_create($regdato));
		$tid = "";
		if ($interval->y != 0) { $tid .= $interval->y . " år, "; }
		if ($interval->m != 0) { $tid .= $interval->m . " måneder, "; }
		$tid .= $interval->d . " dager";
		$result[] = array(
			"id" => $id,
			"fornavn" => $fornavn,
			"etternavn" => $etternavn,
			"epost" => $epost,
			"regdato" => $regdato,
			"regdiff" => $tid,
			"kommentar" => $kommentar);
	}
	header("Content-Type: application/json");
	print(json_encode($result));
} else {
	header("HTTP/1.0 403 You need to log in first");
	print("access denied");
}
