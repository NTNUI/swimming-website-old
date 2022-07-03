"use strict";
const form = document.getElementById("form");
const url = BASEURL + "/api/gavekort";

function sendJSON(requesturl, json) {
    return fetch(requesturl, {
        method: "POST",
        body: JSON.stringify(json)
    }).then((data) => {
        if (data.status !== 200) throw data.text();
        return data.text();
    }).catch((err) => {
        err.then((text) => {
            console.log(text);
            alert("Something went wrong: " + text);
        });
    });
}

function getPreview(e) {
    e.preventDefault();
    const data = getFormData();
    sendJSON(url, data).then((text) => {
        document.getElementById("preview").innerHTML = text;
        document.getElementById("sendData").disabled = !(data.name != "" && data.epost != "" && data.code != "");
    });

}

function resetForm() {

    document.getElementById("preview").innerHTML = "";
    const toClear = ["name", "email", "code"];
    for (i in toClear) {
        document.querySelector("input[name=" + toClear[i] + "]").value = "";
    }
}

function sendEmail(e) {
    e.preventDefault();
    const data = getFormData();
    sendJSON(url + "?submit=1", data).then((text) => {
        alert("Email has been sent");
        resetForm();
    });
}
document.getElementById("getPreview").addEventListener("click", getPreview);
document.getElementById("sendData").addEventListener("click", sendEmail);

function getFormData() {
    const reducer = (data, element) => {
        data[element.name] = element.value;
        return data;
    };
    let data = {};
    const form = document.getElementById("form");
    return [].reduce.call(form.elements, reducer, data);
}