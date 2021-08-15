function godkjenn(id) {
    let button = document.querySelector("#medlem-" + id).querySelector("button");
    button.disabled = true;
    button.innerText = "Godkjenner....";
    getJSON(BASEURL + "/api/memberregister?id=" + id, function(err, json) {
        if (err == null && json.success) {
            button.disabled = true;
            button.innerText = "Godkjent";
        } else {
            alert("Noe gikk galt: " + err);
            button.innerText = "Feil!";
        }
    });
}