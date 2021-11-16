"use strict";

//Remember elements
const successBox = document.getElementById("successBox");
const failureBox = document.getElementById("failureBox");
const searchingBox = document.getElementById("searchingBox");
const names = document.getElementById("names");

function check_member(err, json) {
	if (err || json.length < 1) {
		failureBox.style.display = "block";
	} else {
		successBox.style.display = "block";
		let text = "";
		for (let i = 0; i < json.length; i++) {
			text += "<li>" + json[i].first_name + ", " + json[i].surname + "</li>";
		}
		names.innerHTML = text;
	}
	searchingBox.style.display = "none";
}

function search() {
	//Hide boxes if show
	successBox.style.display = "none";
	failureBox.style.display = "none";
	searchingBox.style.display = "";

	const name = document.getElementById("searchBox").value;
	getJSON(api_src + "/isMember?surname=" + name, check_member);
}

//Set up events
document.getElementById("searchButton").onclick = search;

document.getElementById("searchBox").onkeydown = function (event) {
	if (event.code == "Enter") {
		search();
		document.getElementById("searchBox").value = "";
	}
}

document.getElementById("searchBox").addEventListener("focus", function (_) {
	//Clear content on focus gain 
	document.getElementById("searchBox").value = "";
});
