"use strict";
const accepted = "Accepted";
const rejected = "Rejected";
const notAsked = "Not requested";

function removeElementsByClass(className) {
    let elements = document.getElementsByClassName(className);
    while (elements.length > 0) {
        elements[0].parentNode.removeChild(elements[0]);
    }
}

function search() {
    let query = document.querySelector("input[name=name]").value;
    getJSON(BASEURL + "/api/dugnad?search=" + query, function(err, json) {
        if (err != null) {
            alert("Noe gikk galt: " + err);
            return;
        }
        let container = document.getElementById("members");
        removeElementsByClass("member-row");
        appendMembers(json, container);
    });
}

function randomClick() {
    let num = document.querySelector("input[name=getRandom]").value;
    getMembers(num);
    return false;
}

function approve(id) {
    let button_approve = document.getElementById("approve-" + id);
    let button_reject = document.getElementById("reject-" + id);
    button_approve.disabled = true;
    button_approve.innerText = "Godkjenner...";
    getJSON(BASEURL + "/api/dugnad?approve=" + id, function(err, json) {
        if (err != null) {
            alert("Noe gikk galt: " + err);
            return;
        }
        button_approve.innerText = "Godkjent";
        button_reject.innerText = "Meld avslag";
        button_reject.disabled = false;
        document.getElementById("status-" + id).innerText = accepted;
    });
}

function reject(id) {
    let button_reject = document.getElementById("reject-" + id);
    let button_approve = document.getElementById("approve-" + id);
    button_reject.disabled = true;
    button_reject.innerText = "Melder avslag..."
    getJSON(BASEURL + "/api/dugnad?reject=" + id, function(err, json) {
        if (err != null) {
            alert("Noe gikk galt: " + err);
            return;
        }
        button_reject.innerText = "Innmeldt avslag";
        button_approve.innerText = "Godkjenn";
        button_approve.disabled = false;
        document.getElementById("status-" + id).innerText = rejected;
    });
}

function getMembers(num) {
    getJSON(BASEURL + "/api/dugnad?getRandom=" + num, function(err, json) {
        if (err != null) {
            alert("Noe gikk galt: " + err);
            return;
        }
        let container = document.getElementById("members");
        removeElementsByClass("member-row");
        appendMembers(json, container);

    });
}

function appendMembers(json, container) {
    for (let i in json) {
        let member = json[i];
        const t = document.querySelector("#member");
        let node = document.importNode(t.content, true);
        node.querySelector(".name").innerText = member.name;
        node.querySelector(".email").innerText = member.email;
        node.querySelector(".email").href = "mailto:" + member.email;
        node.querySelector(".phone").innerText = member.phone;
        node.querySelector(".approve").onclick = function() {
            approve(member.id);
        }
        node.querySelector(".approve").id = "approve-" + member.id;
        node.querySelector(".reject").onclick = function() {
            reject(member.id);
        }
        node.querySelector(".reject").id = "reject-" + member.id;
        node.querySelector(".status").id = "status-" + member.id;

        // Approved
        if (member.dugnad == 1) {
            node.querySelector(".approve").disabled = true;
            node.querySelector(".status").innerText = accepted;
        } else if (member.dugnad == 0) {
            node.querySelector(".reject").disabled = true;
            node.querySelector(".status").innerText = rejected;
        } else if (member.dugnad == null) {
            node.querySelector(".status").innerText = notAsked;
        }

        container.appendChild(node);
    }
}