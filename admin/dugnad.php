<style>
.box {
	padding: 0px;
	margin: 5px;
}
button:disabled {
	background-color: darkgray;
	cursor: default;
}

h2 {
	margin: 0;
}
</style>

<h1>Søk etter medlem</h1>

<label for="name">Navn</label>
<input name="name" type="text" onkeyup="search()"/>

<section id="searchResult">

</section>

<h1>Hent ut tilfeldige medlemmer</h1>
<label for="getRandom">Antall personer</label>
<input name="getRandom" type="number" min="1" max="100"/>
<button onclick="randomClick()">Hent</button>

<section id="members">

</section>
<template id="member">
	<div class="box">
		<h2 class="name"></h2>
		<p>
		<span class="status">Har ikke blitt spurt om dugnad</span><br>
		<a class="email"></a><br>
		Tlf: <strong class="phone"></strong>
		</p>
		<button class="approve">Godkjenn dugnad</button>
		<button class="reject red">Meld avlslag</button>
	</div>
</template>

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

function search() {
	let query = document.querySelector("input[name=name]").value;
	getJSON("<?php print $base_url ?>/api/dugnad?search=" + query, function (err, json) {
		if (err != null) {
			alert("Noe gikk galt: " + err);
			return;
		}
		let container = document.getElementById("searchResult");
		container.innerHTML = "";
		appendMembers(json, container);
	});
}

function randomClick() {
	let num = document.querySelector("input[name=getRandom]").value;
	getMembers(num);
	return false;
}

function approve(id) {
	let button = document.getElementById("approve-" + id);
	let button2 = document.getElementById("reject-" + id);
	button.disabled = true;
	button.innerText = "Godkjenner...";
	getJSON("<?php print $base_url ?>/api/dugnad?approve=" + id, function (err, json) {
		if (err != null) {
			alert("Noe gikk galt: " + err);
			return;
		}
		button.innerText = "Godkjent";
		button2.innerText = "Meld avslag";
		button2.disabled = false;
		document.getElementById("status-" + id).innerText = "Har sagt ja til dugnad i år";
	});
}
function reject(id) {
	let button = document.getElementById("reject-" + id);
	let button2 = document.getElementById("approve-" + id);
	button.disabled = true;
	button.innerText = "Melder avslag..."
	getJSON("<?php print $base_url ?>/api/dugnad?reject=" + id, function (err, json) {
		if (err != null) {
			alert("Noe gikk galt: " + err);
			return;
		}
		button.innerText = "Innmeldt avslag";
		button2.innerText = "Godkjenn";
		button2.disabled = false;
		document.getElementById("status-" + id).innerText = "Har avslått direkte spørsmål om dugnad";

	});
}
function getMembers(num) {
	getJSON("<?php print $base_url ?>/api/dugnad?getRandom=" + num, function (err, json) {
		if (err != null) {
			alert("Noe gikk galt: " + err);
			return;
		}
		let container = document.getElementById("members");
		container.innerHTML = "";
		appendMembers(json, container);	

	});
}
function appendMembers(json, container) {
	for (let i in json) {
		let member = json[i];
		const t = document.querySelector("#member");
		let node = document.importNode(t.content, true);
		node.querySelector(".name").innerText = member.name;
		node.querySelector(".email").innerText = member.email;
		node.querySelector(".email").href = "mailto:" + member.email;
		node.querySelector(".phone").innerText = member.phone;	
		node.querySelector(".approve").onclick = function() {
			approve(member.id);
		}
		node.querySelector(".approve").id = "approve-" + member.id;
		node.querySelector(".reject").onclick = function() {
			reject(member.id);
		}
		node.querySelector(".reject").id = "reject-" + member.id;

		node.querySelector(".status").id = "status-" + member.id;

		//Godkjent
		if (member.dugnad == 1) {
			node.querySelector(".approve").disabled = true;
			node.querySelector(".status").innerText = "Har sagt ja til dugnad i år";
		} else if (member.dugnad == 0) {
			node.querySelector(".reject").disabled = true;
			node.querySelector(".status").innerText = "Har avslått direkte spørsmål om dugnad";
		}

		container.appendChild(node);
	}
}
</script>
