"use strict";
function createCommand(command, target, value) {
	value = value || "";
	target = target || "";
	return {
		Command: command,
		Target: target,
		Value: value,
	};
}
function generateNavigator() {
	return [
		createCommand("open", "https://vg.no"),
		createCommand("open", document.getElementById("url").value),
		createCommand("selectFrame", "id=payments"),
		createCommand("selectFrame", "id=indhold"),
	];
}

function generateKID(kid) {
	return [
		createCommand("click", "name=txiFraTxt"),
		createCommand("type", "name=txiFraTxt", document.getElementById("label").value),
		createCommand("type", "name=txiTilKto", document.getElementById("kontonr").value),
		createCommand("type", "name=txiOCRRef", "" + kid),
		createCommand("type", "name=txiBetBel", "" + document.getElementById("amount").value),
		createCommand("clickAndWait", "id=lblBTSaveID"),
		createCommand("pause", document.getElementById("sleepdur").value),
	];
}


function generateOutput() {
	now = new Date();
	let obj = {
		Name: "NTNUISvommingAutopayer",
		CreationDate: now.getFullYear() + "-" + now.getMonth() + "-" + now.getDate(),
		Commands: [],
	};
	obj.Commands.push(...generateNavigator());
	let numbers = 0;

	document.getElementById("kidnumbers").value.split("\n").forEach((kid) => {
		if (isNaN(kid) || kid.length < 1) return;
		obj.Commands.push(...generateKID(kid));
		numbers++;
	});
	output = JSON.stringify(obj);
	document.getElementById("paymentsGenerated").innerText = numbers;
	document.getElementById("output").innerText = output;
}


function copyClipboard() {
	box = document.getElementById("output");
	box.select();
	box.setSelectionRange(0, 99999);
	document.execCommand("copy");
}

document.addEventListener("DOMContentLoaded", function () {
	["change", "keyup", "keypress"].forEach((ev) => { 
		document.querySelectorAll("input, textarea").forEach((el) => {
			el.addEventListener(ev, generateOutput);
		});
	});
	document.getElementById("clipboard").addEventListener("click", copyClipboard);

});

