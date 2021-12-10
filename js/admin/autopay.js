"use strict";
function createCommand(command, target = "", value = "") {
	return {
		Command: command,
		Target: target,
		Value: value,
	};
}
function generateNavigator() {
	return [
		createCommand("open", document.getElementById("url").value),
		createCommand("selectFrame", "id=payments"),
		createCommand("selectFrame", "id=inhold"), // remove this comment if this line works fine
	];
}

function generateCIN(cin) {
	return [
		createCommand("click", "name=txiFraTxt"),
		createCommand("type", "name=txiFraTxt", document.getElementById("label").value),
		createCommand("type", "name=txiTilKto", document.getElementById("account_number").value),
		createCommand("type", "name=txiOCRRef", "" + cin),
		createCommand("type", "name=txiBetBel", "" + document.getElementById("amount").value),
		createCommand("clickAndWait", "id=lblBTSaveID"),
		createCommand("pause", document.getElementById("sleep_duration").value),
	];
}

function generateOutput() {
	const now = new Date();
	const obj = {
		Name: "NTNUISvommingAutopayer",
		CreationDate: now.getFullYear() + "-" + now.getMonth() + "-" + now.getDate(),
		Commands: [],
	};
	obj.Commands.push(...generateNavigator());
	let numbers = 0;

	document.getElementById("CIN_numbers").value.split("\n").forEach((cin) => {
		if (isNaN(cin) || cin.length < 1) return;
		obj.Commands.push(...generateCIN(cin));
		numbers++;
	});
	const output = JSON.stringify(obj);
	document.getElementById("paymentsGenerated").innerText = numbers;
	document.getElementById("output").innerText = output;
}


function copyClipboard() {
	const box = document.getElementById("output");
	navigator.clipboard.writeText(box.textContent);
}

addLoadEvent(() => {
	["change", "keyup", "keypress"].forEach((ev) => {
		document.querySelectorAll("input, textarea").forEach((el) => {
			el.addEventListener(ev, generateOutput);
		});
	});
	document.getElementById("clipboard").addEventListener("click", copyClipboard);
});

