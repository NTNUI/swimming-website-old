"use strict";
import Store from "./modules/store.js";
import { display_modal } from "./modules/modal.js";

// phone validation
// I don't know how it works but if you remove it, client side phone validation will not work 🤷
const input = document.querySelector("input[name=phone]");
let itl = window.intlTelInput(input, {
    initialCountry: "no",
    separateDialCode: true,
    utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/14.0.6/js/utils.js"
});

function get_form_data() {
    const form_data = new FormData();
    // name selector collide with the one inside store modal. For some reason it wont match even if the form is selected as a parent
    form_data.append("name", document.getElementById("enrollment_form")[0].value);
    form_data.append("isMale", document.querySelector('#enrollment_form input[name="gender"]:checked').value == "Male" ? 1 : 0);
    form_data.append("licensee", document.querySelector(".licensee").value);

    const inputs = ["phone", "birthDate", "zip", "address", "email"];
    for (const i in inputs) {
        form_data.append(inputs[i], document.querySelector(`#enrollment_form input[name=${inputs[i]}]`).value);
    }
    return form_data;
}
/**
 * 
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
        if (member.get("licensee")) {
            const licenseQuestion = await display_modal("Do you have a valid license?", "You've selected that you have a valid license.\nPlease make sure you see your entry on https:\/\/medley.no\nIf you're not on the list you'll not be manually approved. You can either wait until you appear on the list and we see it ot you can continue to purchase an NSF license now.", "Continue", "Cancel");
            if (licenseQuestion == "Cancel") {
                return;
            }
        }
        // send membership request
        const enrollResponse = await enroll(member);
        console.table(enrollResponse);

        if (enrollResponse.error) {
            switch (enrollResponse.membership_status) {
                case "pending":
                    const user_response = await display_modal("Warning", "It seems like you already have a pending membership. We need to manually review your request. This might take some time. You can either wait or, if you wish, continue to purchase a license and get a active membership instantly.", "Continue", "Cancel");
                    if (user_response === "Cancel") {
                        return;
                    }
                    break;
                case "active":
                    await display_modal("Failure", enrollResponse.message, "Accept", "", "failure");
                    // window.location.href = BASEURL;
                    return;
                default:
                    throw enrollResponse.message
            }
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
            // display checkout for license payment
            const order = await store.checkout(license_product, customer);
            if (order === undefined) {
                // If user aborts the checkout customer object is not available
                return;
            }
            display_modal("Loading", "Attempting to empty your bank account", "", "", "wait");
            const chargeResponse = await store.charge(order.product, order.customer);
            await display_modal("Success", chargeResponse.message, "Accept", "", "success");
            await display_modal("Welcome as a new member!", "Together we will make Norwegian swimming more fun.\nSprut nice 💦💦", "Accept", "", "success");
            // window.location.href = BASEURL;
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
})