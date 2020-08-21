document.getElementById("menu_show").addEventListener("click", function(e) {
	document.getElementById("meny_container").classList.add("visible");
});
document.getElementById("meny_container").addEventListener("click", function(e) {
	document.getElementById("meny_container").classList.remove("visible");
});
