function register(username) {
    fetch(BASEURL + "/api/friday_beer?register=" + username)
        .then((response) => response.json())
        .then((json) => {
            if (json.error && json.error != "Already drank beer") {
                alert(json.error);
                return;
            }
            document.querySelectorAll("#" + username + " > .nobeer").forEach((e) => {
                e.classList.add("hidden");
            });
            document.querySelectorAll("#" + username + " > .beer").forEach((e) => {
                e.classList.remove("hidden");
            });


        });
}