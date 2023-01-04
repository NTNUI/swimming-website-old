"use strict";
import Store from "./modules/store.js";
import { display_modal } from "./modules/modal.js";

// phone validation
const enrollmentPhoneInput = document.querySelector("form#enrollment_form input[name=phone]");
window.enrollmentPhone = window.intlTelInput(enrollmentPhoneInput, {
    initialCountry: "no",
    customPlaceholder: () => { return ""; },
    separateDialCode: true,
    utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.15/js/utils.min.js"
});

function get_form_data() {
    const form_data = new FormData();
    // name selector collide with the one inside store modal. For some reason it wont match even if the form is selected as a parent
    form_data.append("name", document.getElementById("enrollment_form")[0].value);
    form_data.append("isMale", document.querySelector('#enrollment_form input[name="gender"]:checked').value == "Male" ? 1 : 0);
    form_data.append("licensee", document.querySelector(".licensee").value);

    const inputs = ["birthDate", "zip", "address", "email"];
    for (const i in inputs) {
        form_data.append(inputs[i], document.querySelector(`#enrollment_form input[name=${inputs[i]}]`).value);
    }
    form_data.append("phone", window.enrollmentPhone.getNumber());
    return form_data;
}

/**
 * @param {formData} formData with member enrollment information
 * @returns Promise enrollment response
 */
function enroll(formData) {
    const requestOptions = {
        method: "POST",
        body: formData,
        redirect: "follow"
    }
    return fetch(BASEURL + "/api/enrollment", requestOptions)
        .then(response => response.json());
}

addLoadEvent(() => {
    const enrollment_form = document.getElementById("enrollment_form");
    enrollment_form.addEventListener("submit", async (event) => {
        event.preventDefault();
        const member = get_form_data();

        // if licensed confirm with modal
        let licenseQuestion = "";
        if (member.get("licensee")) {
            licenseQuestion = await display_modal("Do you have a valid license?", "You've selected that you have a valid license.\nPlease make sure you see your entry on https:\/\/medley.no\/utoveroversikt.aspx\nIf you're not on the list, click 'Back', unselect that you have a valid license and send the form again.\nIf you see your self on the list, then click 'Send form' ", "Send form", "Back");
            if (licenseQuestion === "Back") {
                return;
            }
        }

        // send membership request
        let enrollResponse = {};
        try {
            enrollResponse = await enroll(member);
        } catch (err) {
            display_modal("Error", "Something unexpected happened. Developers are alerted 👷\nI think...", "Accept", "", "failure");
            console.error(err);
            return;
        }
        console.table(enrollResponse);

        if (enrollResponse.error) {
            switch (enrollResponse.membership_status) {
                case "pending":
                    const user_response = await display_modal("Warning", "It seems like you have a pending membership.\nThat means you've registered but not yet approved.\nIf you wish to auto approve your membership click 'Continue to purchase'.\nIf you want to wait for manual approval click 'Wait'.", "Continue to purchase", "Wait");
                    if (user_response === "Wait") {
                        return;
                    }
                    break;
                case "active":
                    await display_modal("Failure", enrollResponse.message, "Accept", "", "failure");
                    // window.location.href = BASEURL;
                    return;
                default:
                    display_modal("Error", enrollResponse.message, "Accept", "", "failure");
                    console.error(enrollResponse);
                    return;
            }
        }

        if (licenseQuestion === "Send form") {
            display_modal("Membership request sent", "Your membership request has been sent\nYou'll receive an email when your membership is ready.", "Accept", "", "success");
            return;
        }

        // purchase license
        const customer = {
            name: enrollResponse.name,
            email: enrollResponse.email,
            phone: enrollResponse.phone
        }
        const store = new Store(STRIPE_PUBLISHABLE_KEY, SERVER_TIME_OFFSET, LANGUAGE);
        store.init(INVENTORY_URL);
        try {
            window.open("https://medlem.ntnui.no/groups/swomming", "_blank");
            await display_modal("NTNUI membership", "Please create an account at NTNUI and join the swimming group", "Continue to license payment", "", "success");
            // display checkout for license payment
            const order = await store.checkout(license_product, customer);
            if (order === "abort") {
                // If user aborts the checkout customer object is not available
                return;
            }
            display_modal("Loading", "Attempting to empty your bank account", "", "", "wait");
            const chargeResponse = await store.charge(order);
            let message = chargeResponse.message;
            message += "\n\n";
            message += "Together we will make Norwegian swimming more fun.\nSprut nice 💦💦\n\n";
            message += "Join us on Spond!\n";
            message += "Spond is an app widely used in NTNUI to join practices, parties, meets and many other events.\n";
            message += "Spond is also used as an official communication channel for the group.\n";
            await display_modal("Welcome as a new member!", message, "Continue to Spond", "", "success");
            window.open("https://group.spond.com/HJAVO", "_blank");
        } catch (error) {
            console.error(error);
            if (typeof (error) === "object") {
                display_modal("Error", error.message, "Accept", "", "failure");
                return;
            }
            display_modal("Error", error, "Accept", "", "failure");
        }
    });
    // set different color on submit button if user is licensed.
    document.querySelector(".licensee").addEventListener("change", (event) => {
        if (event.target.value) {
            document.querySelector("input[type=submit]").className = "licensed";
        } else {
            document.querySelector("input[type=submit]").className = "";
        }
    })
    // enable submit when all event listeners has been fully loaded.
    document.querySelector("input[type=submit]").removeAttribute("disabled");
})