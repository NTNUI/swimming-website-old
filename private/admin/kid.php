<?php
/*
TODO:
- Refactor: Move styles to /css
- Refactor: Move all javascript to /js
- New feature: onChange() -> validate. if !valid set color as red, else normal.
- New feature: dump database
- New feature: show hidden users (those with a KID number in database)
*/
?>

<style>
	.max-width {
		width: 100%;
	}
</style>

<div class="box">
	<h2>Kid nummer registrering</h2>
	<p>Her finner man en liste over medlemmer uten gyldig KID nummer i databasen</p>
</div>

<div class="box">

	<h3 id="title-status">Laster...</h3>

	<table id="members" class="max-width">
		<tr>
			<th scope="col" class="header-name">Navn</th>
			<th scope="col" class="header-Email">E-post</th>
			<th scope="col" class="header-phone-number">Telefonnummer</th>
			<th scope="col" class="header-KID">KID</th>
			<th scope="col" class="header-actions">Actions</th>
		</tr>

	</table>

	<template id="member">
		<tr>
			<td class="name"></td>
			<td class="email"></td>
			<td class="phone-number"></td>
			<td class="KID"><input type="number" pattern="/^\d{8}$/gm"></input></td>
			<td class="actions"><button class="save">Lagre</button></td>
		</tr>
	</template>


</div>

<script>
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

	function getMembers() {
		getJSON("<?php global $settings; print($settings["baseurl"]); ?>/api/get_members_without_kid", function(err, json) {
			if (err != null) {
				alert("Error: " + err);
				console.log(err);
				title_status.innerHTML = "Det har oppstått en feil";
				return;
			}
			let container = document.getElementById("members");
			members = appendMembers(json, container);
			title_status = document.getElementById("title-status");

			if (members == 0) {
				title_status.innerHTML = "Ingen medlemmer funnet";
				return;
			}

			if (members) {
				title_status.innerHTML = "Følgende medlemmer har ikke KID registrert i medlemsdatabasen";
				return;
			}

			title_status.innerHTML = "Alle medlemmer har gyldig KID nummer i databasen";

		});
	}

	function appendMembers(json, container) {
		let members = 0;
		console.log(json);
		for (let i in json) {
			members++;
			let member = json[i];
			const t = document.querySelector("#member");
			let node = document.importNode(t.content, true);

			node.querySelector(".name").innerText = member.name;
			node.querySelector(".email").innerText = member.email;
			node.querySelector(".email").href = "mailto:" + member.email;
			node.querySelector(".phone-number").innerText = member.phone;
			node.querySelector(".name").innerText = member.name;
			node.querySelector(".save").onclick = function(e) {
				var kid_number = e.srcElement.parentNode.previousElementSibling.children[0].value;
				if (valid_kid(kid_number)) {
					save_kid_number(member.id, kid_number);
					e.srcElement.parentNode.parentElement.remove();
					return;
				}
				console.log("that input is not a valid KID number");
				e.originalTarget.parentElement.previousElementSibling.children[0].classList.add("error");
			};
			container.appendChild(node);
		}
		return members;
	}

	function valid_kid(kid) {
		if (isNaN(kid)) {
			return false;
		}
		if (kid > 99999999) {
			return false;
		}
		if (kid < 10000000) {
			return false;
		}
		return true;
	}

	function save_kid_number(id, kid) {
		var url = "<?php global $settings; print $settings["baseurl"]; ?>/api/update_kid?";
		url += "ID=" + id;
		url += "&";
		url += "KID=" + kid;
		getJSON(url, (err, json) => {
			if (err) {
				console.log(err);
			}
			console.log(json);
		});

	}

	getMembers();
</script>