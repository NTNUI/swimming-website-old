<style>
button:disabled {
	background-color: darkgray;
	cursor: default;
}
</style>
<form id="form">
	<label for="name">Mottakers navn</label>
	<input type="text" name="name" id="name" placeholder="Ola Nordmann"/>
	<label for="email">Epostaddresse</label>
	<input type="email" name="email" id="email" placeholder="me@example.biz"/>
	<label for="amount">Beløp på gavekort (sjekk selv at det stemmer med koden></label>
	<input type="number" name="amount" id="amount" value="50"/>
	<label for="code">Gavekortkode</label>
	<input type="text" name="code" id="code"/>
	<label for="extra">Ekstra tekst (f. eks hvor man går for å bruke gavekortet</label>
	<textarea name="extra" id="extra"></textarea>
	<button id="getPreview">Get preview</button>

<div id="preview">
</div>
<button id="sendData" disabled="true">Send eposten</button>

</form>
<script type="text/javascript">
const form = document.getElementById("form");
const url = "<?php print $base_url ?>/api/gavekort";
function sendJSON(requesturl, json) {
	return fetch(requesturl, {
		method: "POST",
		body: JSON.stringify(json)
	}).then((data) => {
		if (data.status !== 200) throw data.text();
		return data.text();
	}).catch((err) => { 
		err.then((text) => {
			console.log(text); 
			alert("Noe gikk galt: " + text);
		});
	});
}


function getPreview(e) {
	e.preventDefault();
	const data = getFormData();
	sendJSON(url, data).then((text) => {
		document.getElementById("preview").innerHTML = text;
		document.getElementById("sendData").disabled = !(data.name != "" && data.epost != "" && data.code != "");
	});

}

function resetForm() {

	document.getElementById("preview").innerHTML = "";
	const toClear = ["name", "email", "code"];
	for (i in toClear) {
		document.querySelector("input[name=" + toClear[i] + "]").value = "";
	}	
}

function sendEmail(e) {
	e.preventDefault();
	const data = getFormData();
	sendJSON(url + "?submit=1", data).then((text) => {
		alert("Epost er sendt. TODO: ikke bruk alert her");
		resetForm();
	});
}
document.getElementById("getPreview").addEventListener("click", getPreview);
document.getElementById("sendData").addEventListener("click", sendEmail);
function getFormData() {
	const reducer = (data, element) => {
		data[element.name] = element.value;
		return data;
	};
	let data = {};
	const form = document.getElementById("form");
	return [].reduce.call(form.elements, reducer, data);
}
</script>
