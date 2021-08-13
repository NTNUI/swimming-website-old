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

<div class="box">

	<h2>Nye medlemmer NTNUI Svømming <?php print date("Y") ?></h2>
	Dette er kun medlemmer hvor kasserer ennå ikke har godkjent innbetaling eller lisens fra annen klubb.<br>
	<br>

	<?php

	function createTime($year, $month, $day)
	{
		if ($year == 0 and $month == 0) {
			if ($day == 0) return "I dag";
			if ($day == 1) return "I går";
		}
		$time = "";

		if ($year != 0) {
			$time .= $year . " år ";
		}

		if ($month != 0) {
			$time .= $month . ($month == 1 ? " månede " : " måneder");
		}

		$time .= $day . ($day == 1 ? " dag" : " dager");
		$time .= " siden";
		return $time;
	}

	if (Authenticator::is_logged_in()) {
		global $settings;
		$conn = connect("medlem");

		$sql = "SELECT id, regdato, etternavn, fornavn, kommentar, epost, gammelKlubb, triatlon FROM ${settings['memberTable']} WHERE kontrolldato IS NULL OR YEAR(kontrolldato) <> DATE('y') ORDER BY id DESC";

		$query = $conn->prepare($sql);

		if (!$query->execute()) {
			die("Connection failed: " . $query->error);
		}

		$query->bind_result($id, $regdato, $etternavn, $fornavn, $kommentar, $epost, $gammelKlubb, $triatlon);
		while ($query->fetch()) {
			$etternavn = htmlspecialchars($etternavn);
			$fornavn = htmlspecialchars($fornavn);
			$kommentar = htmlspecialchars($kommentar);
			$epost = htmlspecialchars($epost);

			$interval = date_diff(date_create(), date_create($regdato));
			$tid = createTime($interval->y, $interval->m, $interval->d);
	?>
			<div class="box" id="medlem-<?php print $id ?>">
				<h2><?php print "$etternavn, $fornavn" ?><button style="" onclick="godkjenn(<?php print $id ?>)">Godkjenn</button></h2>
				<table class="center_table" style="width: 100%">
					<tr>
						<td>Registreringsdato:</td>
						<td tooltip="test"><?php print $tid ?> (<?php print $regdato ?>)</td>
					</tr>
					<?php if ($gammelKlubb != "") { ?>
						<tr>
							<td>Lisens betalt av annen klubb:</td>
							<td style="color: <?php $triatlon ? print "green" : print "red";?>">
								<?php print $gammelKlubb ?>
							</td>
						</tr>
					<?php } ?>
					<?php if ($kommentar != "") { ?> <tr>
							<td>Kommentar:</td>
							<td><?php print $kommentar ?></td>
						</tr><?php } ?>
				</table>
				<a class="btn" href="mailto:<?php print $epost ?>">Send epost</a>
			</div>
	<?php			}

		$query->close();
		mysqli_close($conn);
	}

	?>
</div>

<script type="text/javascript">
	var getJSON = function(url, callback) {
		var xhr = new XMLHttpRequest();
		xhr.open('GET', url, true);
		xhr.responseType = 'json';
		xhr.onload = function() {
			var status = xhr.status;
			if (status === 200) {
				callback(null, xhr.response);
			} else {
				callback(status, xhr.response);
			}
		};
		xhr.send();
	};

	function godkjenn(id) {
		let button = document.querySelector("#medlem-" + id).querySelector("button");
		button.disabled = true;
		button.innerText = "Godkjenner....";
		getJSON("<?php global $settings; print $settings['baseurl']; ?>/api/memberregister?id=" + id, function(err, json) {
			if (err == null && json.success) {
				button.disabled = true;
				button.innerText = "Godkjent";
			} else {
				alert("Noe gikk galt: " + err);
				button.innerText = "Feil!";
			}
		});
	}
</script>