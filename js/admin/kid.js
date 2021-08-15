function getMembers() {
    getJSON(BASEURL + "/api/get_members_without_kid", function(err, json) {
        if (err != null) {
            alert("Error: " + err);
            console.log(err);
            title_status.innerHTML = "Det har oppstått en feil";
            return;
        }
        let container = document.getElementById("members");
        members = appendMembers(json, container);
        title_status = document.getElementById("title-status");

        if (members == 0) {
            title_status.innerHTML = "Ingen medlemmer funnet";
            return;
        }

        if (members) {
            title_status.innerHTML = "Følgende medlemmer har ikke KID registrert i medlemsdatabasen";
            return;
        }

        title_status.innerHTML = "Alle medlemmer har gyldig KID nummer i databasen";

    });
}

function appendMembers(json, container) {
    let members = 0;
    console.log(json);
    for (let i in json) {
        members++;
        let member = json[i];
        const t = document.querySelector("#member");
        let node = document.importNode(t.content, true);

        node.querySelector(".name").innerText = member.name;
        node.querySelector(".email").innerText = member.email;
        node.querySelector(".email").href = "mailto:" + member.email;
        node.querySelector(".phone-number").innerText = member.phone;
        node.querySelector(".name").innerText = member.name;
        node.querySelector(".save").onclick = function(e) {
            var kid_number = e.srcElement.parentNode.previousElementSibling.children[0].value;
            if (valid_kid(kid_number)) {
                save_kid_number(member.id, kid_number);
                e.srcElement.parentNode.parentElement.remove();
                return;
            }
            console.log("that input is not a valid KID number");
            e.originalTarget.parentElement.previousElementSibling.children[0].classList.add("error");
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
    var url = BASEURL + "/api/update_kid?";
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