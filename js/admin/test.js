"use strict";
import Store from "../modules/store.js";
import { display_modal } from "../modules/modal.js";

addLoadEvent(async () => {
    const checkout = document.querySelector(".checkout");
    const alert = document.querySelector(".alert");
    const question = document.querySelector(".question");
    const wait = document.querySelector(".wait");
    const success = document.querySelector(".success");
    const failure = document.querySelector(".failure");

    const store = new Store(STRIPE_PUBLISHABLE_KEY, SERVER_TIME_OFFSET);
    await store.init(INVENTORY_URL);

    // copy element. Because js...
    const inventory = await store.inventory;
    let purchaseButtonHandler = () => {
        store.checkout(inventory[0])
            .then((order) => {
                display_modal("Loading", "Attempting to empty your bank account", "", "", "wait");
                store.charge(order.product, order.customer).then(async (serverResponse) => {
                    if (typeof (serverResponse == "object")) {
                        serverResponse = serverResponse.message;
                    }
                    await display_modal("Success", serverResponse, "Accept", "", "success");
                    Store.hide_checkout_modal();
                }).catch(async (error) => {
                    if (typeof (error == "object")) {
                        error = error.message;
                    }
                    await display_modal("Failure", error, "Accept", "", "failure");
                });
            });
    }

    checkout.addEventListener("click", purchaseButtonHandler);

    const message = "Sample message";
    alert.addEventListener("click", async () => {
        display_modal("Alert", message, "Accept", "")
            .then((button) => {
                console.log("User clicked " + button);
            });
    });
    question.addEventListener("click", async () => {
        display_modal("Alert", "Are you sure?", "Yes", "No")
            .then((button) => {
                console.log("User clicked " + button);
            });
    });
    wait.addEventListener("click", async () => {
        display_modal("Loading", "", "", "Close", "wait")
            .then((button) => {
                console.log("User clicked " + button);
            });
    });
    success.addEventListener("click", async () => {
        display_modal("Success", "Something went really well", "Accept", "", "success")
            .then((button) => {
                console.log("User clicked " + button);
            });
    });
    failure.addEventListener("click", async () => {
        display_modal("Failed", "Something didn't go as planned", "Accept", "", "failure")
            .then((button) => {
                console.log("user clicked " + button);
            });
    });

});