"use strict";
function approve(id) {
    let button = document.querySelector("#member-" + id).querySelector("button");
    button.disabled = true;
    button.innerText = "Approving";
    getJSON(BASEURL + "/api/member_register?id=" + id, function(err, json) {
        if (err == null && json.success) {
            button.disabled = true;
            button.innerText = "Approved";
        } else {
            alert("Something went wrong: " + err);
            button.innerText = "Error";
        }
    });
}