"use strict";
import { display_modal } from "/js/modules/modal.js";

function createCommand(command, target = "", value = "") {
    return {
        Command: command,
        Target: target,
        Value: value,
    };
}
function generateNavigator() {
    return [
        createCommand("open", document.getElementById("url").value),
        createCommand("selectFrame", "id=payments"),
        createCommand("selectFrame", "id=inhold"), // remove this comment if this line works fine
    ];
}

function generateCIN(cin) {
    return [
        createCommand("click", "name=txiFraTxt"),
        createCommand("type", "name=txiFraTxt", document.getElementById("label").value),
        createCommand("type", "name=txiTilKto", document.getElementById("account_number").value),
        createCommand("type", "name=txiOCRRef", "" + cin),
        createCommand("type", "name=txiBetBel", "" + document.getElementById("amount").value),
        createCommand("clickAndWait", "id=lblBTSaveID"),
        createCommand("pause", document.getElementById("sleep_duration").value),
    ];
}

function generateOutput() {
    const now = new Date();
    const obj = {
        Name: "NTNUISvommingAutopayer",
        CreationDate: now.getFullYear() + "-" + now.getMonth() + "-" + now.getDate(),
        Commands: [],
    };
    obj.Commands.push(...generateNavigator());
    let numbers = 0;

    document.getElementById("CIN_numbers").value.split("\n").forEach((cin) => {
        if (isNaN(cin) || cin.length < 1) return;
        obj.Commands.push(...generateCIN(cin));
        numbers++;
    });
    const output = JSON.stringify(obj);
    document.getElementById("paymentsGenerated").innerText = numbers;
    document.getElementById("output").innerText = output;
}


function copyClipboard() {
    const box = document.getElementById("output");
    navigator.clipboard.writeText(box.textContent);
}


async function get_not_payed() {
    const request = {
        function: "get_not_payed"
    };
    const options = {
        method: "SEARCH",
        headers: {
            "Accept": "Application/json"
        },
        body: JSON.stringify(request)
    }
    try {
        const response = await fetch(BASEURL + "/api/cin", options);
        if (response.status !== 200) {
            throw response.statusText;
        }
        let lines_arr = [];
        const response_json = await response.json();
        response_json.forEach((item) => {
            lines_arr.push(item.cin)
        });
        return lines_arr;
    } catch (error) {
        console.error(error);
        if (typeof (error) === "object") {
            error = JSON.stringify(error);
        }
        display_modal("Error", "Could not retrieve unpayed CIN numbers\n" + error, "Accept", "", "failure");
        throw error;
    }
}

async function getMembers() {
    const container = document.getElementById("members");

    try {
        const request = {
            function: "get_missing"
        }
        const options = {
            method: "SEARCH",
            headers: {
                "Accept": "Application/json"
            },
            body: JSON.stringify(request)
        };
        const response = await fetch(BASEURL + "/api/cin", options);
        if (response.status !== 200) {
            throw response.statusText;
        }

        const rowsCreated = appendMembers(await response.json(), container);
        if (rowsCreated === 0) {
            display_modal("Success", "All members have a valid CIN", "Accept", "", "success");
            return;
        }
    } catch (error) {
        console.error(error);
        if (typeof (error) === "object") {
            error = JSON.stringify(error);
        }
        display_modal("Error", error, "Accept", "", "failure");

    }
    return;
}

function appendMembers(json, container) {
    let members = 0;
    for (let i in json) {
        members++;
        let member = json[i];
        const t = document.querySelector("#member");
        let node = document.importNode(t.content, true);

        node.querySelector(".name").innerText = member.first_name + " " + member.surname;
        node.querySelector(".gender").innerText = member.gender;
        node.querySelector(".birth_date").innerText = member.birth_date;
        node.querySelector(".email").innerText = member.email;
        node.querySelector(".phone").innerText = member.phone;
        node.querySelector(".address").innerText = member.address;
        node.querySelector(".zip").innerText = member.zip;

        node.querySelector(".save").onclick = function(e) {
            let cin_number = e.srcElement.parentNode.previousElementSibling.children[0].value;
            if (valid_cin(cin_number)) {
                save_cin_number(e.srcElement.parentNode, member.id, cin_number);
                return;
            }
            console.log("that input is not a valid CIN");
            e.target.parentElement.previousElementSibling.children[0].classList.add("error");
        };
        container.appendChild(node);
    }
    return members;
}

function valid_cin(cin) {
    if (isNaN(cin)) {
        return false;
    }
    if (cin > 99999999) {
        return false;
    }
    if (cin < 10000000) {
        return false;
    }
    return true;
}

async function save_cin_number(node, id, cin) {
    console.log("id: " + id + ". cin: " + cin);
    const request = {
        function: "patch_cin",
        args: {
            id: id,
            cin: cin
        }
    };
    const options = {
        method: "PATCH",
        headers: {
            "Accept": "Application/json"
        },
        body: JSON.stringify(request)
    };
    try {
        const response = await fetch(BASEURL + "/api/cin", options);
        if (response.status !== 200) {
            throw response;
        }
        // delete that row on success
        console.log((await response.json()).message);
        node.parentElement.remove();
    } catch (response) {
        console.log(response)
        display_modal(response.statusText, (await response.json()).message, "Accept", "", "failure");
    }
}

addLoadEvent(async () => {
    ["change", "keyup", "keypress"].forEach((ev) => {
        document.querySelectorAll("input, textarea").forEach((el) => {
            el.addEventListener(ev, generateOutput);
        });
    });
    const cin_raw = await get_not_payed();
    let cin_lines = "";

    if (Array.isArray(cin_raw)) {
        cin_raw.forEach(element => {
            cin_lines += element + "\n";
        });
    }

    document.querySelector("#CIN_numbers").textContent = cin_lines;
    generateOutput();
    document.getElementById("clipboard").addEventListener("click", copyClipboard);

    getMembers();

    // toggle visibility options
    document.querySelector("#btn-cin-options").addEventListener("click", () => {
        const options_div = document.querySelector(".cin-options");
        if (options_div.hasAttribute("hidden")) {
            options_div.removeAttribute("hidden");
        } else {
            options_div.setAttribute("hidden", "true");
        }
    });


    document.querySelector("#btn-cin-mark-forwarded").addEventListener("click", async () => {
        const cin_numbers = document.querySelector("#CIN_numbers");
        const request = {
            function: "set_forwarded",
            args: {
                cin: cin_numbers.textContent.split("\n")
            }
        };
        const options = {
            method: "PATCH",
            headers: {
                "Accept": "Application/json"
            },
            body: JSON.stringify(request)
        };
        try {
            const response = await fetch(BASEURL + "/api/cin", options);
            if (response.status !== 200) {
                throw response;
            }
            cin_numbers.textContent = "";
            document.getElementById("output").innerText = "";
            display_modal(response.statusText, (await response.json()).message, "Accept", "", "success");
        } catch (response) {
            console.log(response);
            const resolved = await response;
            const response_json = await resolved.json();
            display_modal(resolved.statusText, `Error:\n${response_json.error}\n\nBacktrace:\n${response_json.backtrace}`, "Accept", "", "failure");
        }

    });


    // enable buttons
    document.querySelectorAll("button").forEach((el) => {
        el.removeAttribute("disabled");
    })
});