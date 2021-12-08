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
        if (member.licensee) {
            const licenseQuestion = await display_modal("Confirm", "you're licensed, plz confirm blablabla use translation API later", "Continue", "Cancel");
            if (licenseQuestion == "cancel") {
                return;
            }
        }
        // send membership request
        const enrollResponse = await enroll(member);
        console.table(enrollResponse);
        switch (enrollResponse.membership_status) {
            // BUG: New users will always have membership_status = pending. 
            case "pending":
                const user_response = await display_modal("Warning", "It seems like you already have a pending membership. We need to manually review your request. This might take some time. If you wish, you can continue to purchase a license and get a active membership instantly.", "Continue", "Cancel");
                if (user_response === "Cancel") {
                    return;
                }
                break;
            case "active":
                await display_modal("Failure", "This user already has an active and valid membership", "Accept", "", "failure");
                // window.location.href = BASEURL;
                return;
            default:
                throw enrollResponse.message
        }
        // purchase license
        const customer = {
            name: enrollResponse.name,
            email: enrollResponse.email,
            phone: enrollResponse.phone
        }
        const store = new Store(STRIPE_PUBLISHABLE_KEY, SERVER_TIME_OFFSET, LANGUAGE);
        store.init(INVENTORY_URL);
        // display checkout for license
        try {
            const order = await store.checkout(license_product, customer);
            if (order === undefined) {
                // If user aborts the checkout customer object is not available
                return;
            }
            const chargeResponse = await store.charge(order.product, order.customer);
            await display_modal("Success", chargeResponse, "Accept", "", "success");
            // window.location.href = BASEURL;
        } catch (error) {
            console.error(error);
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