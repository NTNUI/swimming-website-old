"use strict";

import Store from "./modules/store.js";
import {
    display_modal
} from "./modules/modal.js";

function handle_order(store, order) {
    // on purchase click, display loading modal and send charge request to server
    display_modal("Loading", "Attempting to empty your bank account", "", "", "wait");
    store.charge(order.product, order.customer)
        .then(async (serverResponse) => {
            if (typeof (serverResponse === "object")) {
                serverResponse = serverResponse.message;
            }
            await display_modal("Success", serverResponse, "Accept", "", "success");
        }).catch((error) => {
            console.error(error);
            if (typeof (error === "object")) {
                error = error.message;
            }
            display_modal("Failure", error, "Accept", "", "failure");
            console.error(error);
        });
}

const store = new Store(STRIPE_PUBLISHABLE_KEY, SERVER_TIME_OFFSET);
store.init(BASEURL + "/api/inventory").then(async () => {
    const inventory = await store.inventory;
    const requested_product = inventory.find(product => product.hash === REQUESTED_PRODUCT_HASH);
    console.log(inventory);
    const empty_store = document.querySelector("#storeEmpty");

    for (let i = 0; i < inventory.length; ++i) {
        // don't display hidden elements
        if (!inventory[i].visibility) {
            continue;
        }
        // Create a product entry
        const productEntry = store.createProductEntry(inventory[i]);

        const purchaseButtonHandler = () => {
            // display checkout modal on purchase click
            store.checkout(inventory[i])
                .then((order) => {
                    handle_order(store, order);
                })
                .catch((error) => {
                    display_modal("Failure", error, "Accept", "", "failure");
                    console.error(error);
                });
        }
        productEntry.querySelector(".store_button").addEventListener("click", purchaseButtonHandler);
        // append product entry to DOM
        document.querySelector("#store_container").appendChild(productEntry);

        if (!empty_store.classList.contains("hidden")) {
            empty_store.classList.add("hidden");
        }
    }

    // if some product is requested, display the purchase modal right away.
    if (requested_product) {
        store.checkout(requested_product)
            .then((order) => {
                handle_order(store, order);
            })
            .catch((error) => {
                display_modal("Failure", error, "Accept", "", "failure");
                console.error(error);
            });
    }
});