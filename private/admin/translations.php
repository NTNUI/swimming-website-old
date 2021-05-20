<?php
$dir = $translations_dir;

$page = NULL;
$type = NULL;
$action = NULL;

if (isset($_REQUEST["page"])) {
	$page = $_REQUEST["page"];
}
if (isset($_REQUEST["type"])) {
	$type = $_REQUEST["type"];
}
if (isset($_REQUEST["action"])) {
	$type = $_REQUEST["action"];
}


log_message("page variable: " . $_REQUEST["page"], __FILE__, __LINE__);
log_message("type variable: " . $_REQUEST["type"], __FILE__, __LINE__);
log_message("action variable: " . $_REQUEST["action"], __FILE__, __LINE__);

if ($page != "" and file_exists("$dir/$page.json")) {
	$text = json_decode(file_get_contents("$dir/$page.json"));

	print "Redigerer side: $page - <a href='$base_url/admin/translations'>Tilbake til listen</a>";
?>
	<div class="green box">
		- For å redigere kan du skrive rett i boksene, eller trykke på rediger og bruke den større editoren nederst på siden. <br>
		- Husk å trykke 'Lagre endringer' når du er ferdig!
	</div>
	<?php
	print "<table style='width: 100%'>";
	print "<tr><th>Key</th><th>Norsk</th><th>English</th><th>Actions</th></tr>";
	foreach ($text->no as $key => $value) { ?>
		<tr class="translation">
			<td><input class="key" type="text" value="<?php print $key ?>" /></td>
			<td><textarea class="noValue"><?php print $value ?></textarea></td>
			<td><textarea class="enValue"><?php if (array_key_exists($key, $text->en)) print $text->en->$key; ?></textarea></td>
			<td>
				<button class="delete">Slett</button>
				<button class="edit">Rediger</button>
			</td>
		</tr>
	<?php
	} ?>
	<tr class="translation" id="dummy">
		<td><input class="key" type="text" placeholder="Legg til ny"></td>
		<td><textarea class="noValue"></textarea></td>
		<td><textarea class="enValue"></textarea></td>
		<td style="display:none">
			<button class="delete">Slett</button>
			<button class="edit">Rediger</button>
		</td>
	</tr>
	<?php
	print "</table>"; ?>
	<button onclick="sendJSON()">Lagre endringer</button> <br>
	<div id="editor">
		<label for="key">Nøkkel</label>
		<input class="key" name="key" type="text" placeholder="key" />
		<label for="noValue">Norsk:</label>
		<textarea class="noValue" name="noValue" style="width: 100%; height: 6em"></textarea>
		<label for="enValue">Engelsk:</label>
		<textarea class="enValue" name="enValue" style="width: 100%; height: 6em"></textarea>
	</div>
	<button onclick="sendJSON()">Lagre endringer</button> <br>
	<script type="text/javascript">
		function makeJSON() {
			var translations = document.getElementsByClassName("translation");
			var result = {
				"no": {},
				"en": {}
			};
			for (var i = 0; i < translations.length; i++) {
				var t = translations[i];
				var key = t.getElementsByTagName("input")[0].value;
				var no = t.getElementsByTagName("textarea")[0].value;
				var en = t.getElementsByTagName("textarea")[1].value;
				if (key == "") continue;
				if (no == "" && en == "") continue;
				if (no != "") result.no[key] = no;
				if (en != "") result.en[key] = en;
			}

			return result;
		}

		function bindInputs() {
			var translations = document.getElementsByClassName("translation");
			var editor = document.getElementById("editor");
			for (var i = 0; i < translations.length; i++) {
				let t = translations[i];
				let removeButton = t.getElementsByClassName("delete")[0];
				let editButton = t.getElementsByClassName("edit")[0];

				let keyVal = t.getElementsByClassName("key")[0];
				let noVal = t.getElementsByClassName("noValue")[0];
				let enVal = t.getElementsByClassName("enValue")[0];

				removeButton.onclick = function(e) {
					e.preventDefault();
					t.parentNode.removeChild(t);
					return false;
				}

				editButton.onclick = function(e) {
					e.preventDefault();
					let key = editor.getElementsByClassName("key")[0];
					let no = editor.getElementsByClassName("noValue")[0];
					let en = editor.getElementsByClassName("enValue")[0];

					key.value = keyVal.value;
					no.value = noVal.value;
					en.value = enVal.value;

					key.onchange = function() {
						keyVal.value = key.value;
					}
					no.onchange = function() {
						noVal.value = no.value;
					}
					en.onchange = function() {
						enVal.value = en.value;
					}

					editor.scrollIntoView();
				}
			}

			let dummy = document.getElementById("dummy");
			dummy.addEventListener("focus", cloneDummy, true);
		}

		function cloneDummy() {
			let dummy = document.getElementById("dummy");
			let newDummy = dummy.parentNode.appendChild(dummy.cloneNode(true));
			dummy.id = "";
			dummy.removeEventListener("focus", cloneDummy, true);
			dummy.getElementsByTagName("td")[3].style.display = "";
			bindInputs();
		}

		bindInputs();

		var requesturl = "<?php print "$base_url/api/translations?page=$page" ?>";

		function sendJSON() {
			fetch(requesturl, {
				method: "POST",
				body: JSON.stringify(makeJSON())
			}).then((data) => {
				console.log(data);
				if (data.status !== 200) throw data.text();
				return data.text();
			}).then((text) => {
				console.log(text);
				alert("Endringene ble lagret: " + text.length);
			}).catch((err) => {
				err.then((text) => {
					console.log(text);
					alert("Noe gikk galt: " + text);
				});
			});
		}
	</script>
	<?php

} else {
	print("<div class='box'>");
	print "<h2>Please choose a file to edit</h2>";
	foreach (glob("$dir/*") as $trans) {
		//Omit directory and file extension
		$pageName = substr($trans, strlen("$dir/"));
		$pageName = substr($pageName, 0, -strlen(".json"));
	?>
		<a href="?page=<?php print $pageName ?>"><?php print $pageName ?></a><br>
<?php
	}
	print("</div>");
}
?>