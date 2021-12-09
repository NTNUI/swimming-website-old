"use strict";
import { display_modal } from "../modules/modal.js";

function search() {
	const successBox = document.getElementById("successBox");
	const failureBox = document.getElementById("failureBox");
	const searchingBox = document.getElementById("searchingBox");
	// Hide boxes if show
	successBox.style.display = "none";
	failureBox.style.display = "none";
	searchingBox.style.display = "";

	const names = document.getElementById("names");
	const name = document.getElementById("searchBox").value;
	fetch(BASEURL + "/api/isMember?surname=" + name)
		.then(response => response.json())
		.then((people) => {
			people.forEach((person) => {
				console.log(person);
				names.innerHTML += "<li>" + person.first_name + " " + person.surname + "</li>";
			});
			successBox.style.display = "block";
			searchingBox.style.display = "none";
		})
		.catch((error) => {
			display_modal("Error", error, "Accept", "", "failure");
		});
}

// Set up events
document.getElementById("searchButton").onclick = search;

document.getElementById("searchBox").onkeydown = function(event) {
	if (event.code == "Enter") {
		search();
		document.getElementById("searchBox").value = "";
	}
}

document.getElementById("searchBox").addEventListener("focus", function(_) {
	// Clear content on focus gain 
	document.getElementById("searchBox").value = "";
});
