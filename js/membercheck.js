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

function checkmember(err, json) {
	if (err != "null" && json.length > 0) {
		successBox.style.display = "block";
		var text = "";
		for (var i = 0; i < json.length; i++) {
			text += "<li>" + json[i].fornavn + ", " + json[i].etternavn + "</li>";
		}
		names.innerHTML = text;
	} else {
		failureBox.style.display = "block";
	}
	searchingBox.style.display = "none";
}

//Remember elements
var successBox = document.getElementById("successBox");
var failureBox = document.getElementById("failureBox");
var searchingBox = document.getElementById("searchingBox");
var names = document.getElementById("names");

function search() {
	//Hide boxes if show
	successBox.style.display = "none";
	failureBox.style.display = "none";
	searchingBox.style.display = "";

	var name = document.getElementById("searchBox").value;
	getJSON(api_src + "/membercheck?lname=" + name, checkmember);
}


//Set up events
document.getElementById("searchButton").onclick = search;

document.getElementById("searchBox").onkeypress = function(e) {
	if (e.keyCode == 13) { //Enter
		//Call button click, sue me
		search();
		document.getElementById("searchBox").value = "";

	}
}

document.getElementById("searchBox").addEventListener("focus", function (e) {
	//Clear content on focus gain 
	document.getElementById("searchBox").value = "";
});
