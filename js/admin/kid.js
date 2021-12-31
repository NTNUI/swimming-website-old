"use strict";
import { display_modal } from "../modules/modal.js";
function getMembers() {
    const container = document.getElementById("members");

    getJSON(BASEURL + "/api/get_members_without_kid", function(err, json) {
        if (err !== null) {
            display_modal("Error", err, "Accept", "", "failure");
            return;
        }
        const members = appendMembers(json, container);

        if (members === 0) {
            display_modal("Success", "All members have a valid CIN number", "Accept", "", "success");
            return;
        }
    });
}

function appendMembers(json, container) {
    let members = 0;
    for (let i in json) {
        members++;
        let member = json[i];
        const t = document.querySelector("#member");
        let node = document.importNode(t.content, true);

        node.querySelector(".name").innerText = member.name;
        node.querySelector(".email").innerText = member.email;
        node.querySelector(".email").href = "mailto:" + member.email;
        node.querySelector(".phone").innerText = member.phone;
        node.querySelector(".name").innerText = member.name;
        node.querySelector(".save").onclick = function(e) {
            let kid_number = e.srcElement.parentNode.previousElementSibling.children[0].value;
            if (valid_kid(kid_number)) {
                save_kid_number(member.id, kid_number);
                e.srcElement.parentNode.parentElement.remove();
                return;
            }
            console.log("that input is not a valid KID number");
            e.target.parentElement.previousElementSibling.children[0].classList.add("error");
        };
        container.appendChild(node);
    }
    return members;
}

function valid_kid(kid) {
    if (isNaN(kid)) {
        return false;
    }
    if (kid > 99999999) {
        return false;
    }
    if (kid < 10000000) {
        return false;
    }
    return true;
}

function save_kid_number(id, kid) {
    let url = BASEURL + "/api/update_kid?";
    url += "ID=" + id;
    url += "&";
    url += "KID=" + kid;
    getJSON(url, (err, json) => {
        if (err) {
            console.log(err);
        }
        console.log(json);
    });

}

addLoadEvent(getMembers);