var header = document.getElementById("header");
var content = document.getElementById("content");
var time = document.getElementById("time");

setInterval(function() {
    var str = new Date().toISOString().replace("T", " ");
    time.innerHTML = str.substr(0, str.lastIndexOf("."));
}, 250);
document.getElementById("inputHeader").onkeyup = function(e) {
    header.innerHTML = document.getElementById("inputHeader").value;
}
document.getElementById("inputContent").onkeyup = function(e) {
    content.innerHTML = document.getElementById("inputContent").value;
}