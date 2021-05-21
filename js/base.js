document.getElementById("menu_show").addEventListener("click", function(e) {
    document.getElementById("menu_container").classList.add("visible");
});
document.getElementById("menu_container").addEventListener("click", function(e) {
    document.getElementById("menu_container").classList.remove("visible");
});