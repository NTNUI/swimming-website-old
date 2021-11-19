"use strict";

// phone validation
let input = document.querySelector("input[name=phoneNumber]");
let itl = window.intlTelInput(input, {
    initialCountry: "no",
    separateDialCode: true,
    utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/14.0.6/js/utils.js"
});

function send_enrollment_form() {
    let form_data = new FormData();
    form_data.append("name", document.getElementById("enrollment_form")[0].value);
    form_data.append("isMale", document.querySelector('input[name="gender"]:checked').value == "Male" ? 1 : 0);
    form_data.append("phoneNumber", document.getElementById("enrollment_form")[3].value);
    form_data.append("birthDate", document.getElementById("enrollment_form")[4].value);
    form_data.append("zip", document.getElementById("enrollment_form")[5].value);
    form_data.append("address", document.getElementById("enrollment_form")[6].value);
    form_data.append("email", document.getElementById("enrollment_form")[7].value);
    form_data.append("licensee", document.getElementById("enrollment_form")[8].value);
    let requestOptions = {
        method: 'POST',
        body: form_data,
        redirect: 'follow'
    };

    fetch(BASEURL + "/api/enrollment", requestOptions)
        .then(response => response.text())
        .then((result) => {
            const response = JSON.parse(result);
            let user = {};
            user.name = response.name;
            user.phone = response.phoneNumber;
            user.email = response.email;
            if (!document.querySelector("#enrollment_form > input[type=submit]:nth-child(10)").className) {
                // if user does not have a license
                display_store(license_store_item, user);
            } else {
                // show success modal.
                var temp = document.getElementById("modal_template");
                var clone = temp.content.cloneNode(true);
                clone.querySelector(".modal_header").innerText = "You are now registered!";
                clone.querySelector(".modal_message").innerText = "You've set licensed club. We'll have to manually check that it is correct. You'll receive an email when your membership is ready.";
                clone.querySelector(".modal_button").innerText = "Accept";
                clone.querySelector(".modal_button").addEventListener("click", (e) => {
                    window.location.href = BASEURL;
                });
                document.body.appendChild(clone);
            }
        }
        )
        .catch(error => console.log('error', error));
}


addLoadEvent(() => {
    const enrollment_form = document.getElementById("enrollment_form");
    enrollment_form.addEventListener("submit", (event) => {
        event.preventDefault();
        send_enrollment_form();
    });
    document.querySelector("#enrollment_form > div:nth-child(8) > div > select").addEventListener("change", (e) => {
        if (e.target.value) {
            document.querySelector("#enrollment_form > input[type=submit]:nth-child(10)").className = "licensed";
        } else {
            document.querySelector("#enrollment_form > input[type=submit]:nth-child(10)").className = "";
        }
    })
})