"use strict";
export default class Store {
    static CheckoutLock = false; // internal state for performing one purchase at a time.
    static ChargeLock = false;
    /**
     * Store class opens up the possibility to perform checkouts, list inventories and perform charges.
     * @param {string} publishable_key Stripe publishable API key
     * @param {int} server_time_offset Difference between client time and server time. Defaults to 0.
     * @param {string} lang language. Accepted values are {"no", "en"}. Defaults to "en"
     */
    constructor(publishable_key, server_time_offset = 0, lang = "en") {
        this.server_time_offset = server_time_offset;
        this.displayed_product = "";
        this.lang = lang;
        this.inventory = [];
        this.card_validation_error = true;

        this.stripe = Stripe(publishable_key);
        this.elements = this.stripe.elements();
        this.card = this.elements.create('card');
    }

    /**
     * fetch inventory from server
     * @param {string} inventory_url url of the inventory 
     */
    init(inventory_url) {
        return fetch(inventory_url)
            .then(result => result.json())
            .then(inventory => this.inventory = inventory);
    }

    /**
     * Display checkout modal 
     * @param {object} product to be purchased
     * @param {object | null} customer if set, then customer info will be read only
     * @returns a Promise for an Order object. Gets resolved when user commits to a purchase
     */
    checkout(product, customer) {
        return new Promise((resolve) => {
            // create a new checkout modal
            const checkout_template = document.querySelector("#checkout_template").cloneNode(true).content;
            document.querySelector(".content").appendChild(checkout_template);
            const checkout_node = document.querySelector(".content #checkout_overlay")

            // product
            const checkout_description = checkout_node.querySelector("#checkout_description");
            const checkout_title = checkout_node.querySelector("#checkout_title");
            const checkout_price = checkout_node.querySelector("#checkout_price");
            const checkout_price_member = checkout_node.querySelector("#checkout_price_member");
            const checkout_price_member_label = checkout_node.querySelector("#checkout_price_member_label");
            const checkout_img = checkout_node.querySelector("#checkout_img");
            const checkout_hash = checkout_node.querySelector("#product_hash");

            // customer
            const checkout_name = checkout_node.querySelector("#checkout_name");
            const checkout_phone = checkout_node.querySelector("#checkout_phone");
            const checkout_email = checkout_node.querySelector("#checkout_email");
            const checkout_comment = checkout_node.querySelector("#checkout_comment");

            // create phone input
            console.log(checkout_phone);
            window.checkoutPhone = window.intlTelInput(checkout_phone, {
                initialCountry: "no",
                separateDialCode: true,
                customPlaceholder: () => { return ""; },
                utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.15/js/utils.min.js"
            });

            // Update product description
            this.displayed_product = product;
            checkout_title.textContent = product.name;
            checkout_hash.value = product.hash;
            checkout_description.innerHTML = product.description;
            checkout_img.src = product.image;
            checkout_price.textContent = this.formatCurrency(product.price);

            // set attributes on user input based on product properties
            if (product.price_member) {
                checkout_price_member_label.style.display = "block";
                checkout_price_member.textContent = this.formatCurrency(product.price_member);
            } else {
                checkout_price_member_label.style.display = "none";
            }

            if (product.require_email) {
                checkout_email.setAttribute("required", true);
            } else {
                checkout_email.removeAttribute("required");
            }

            if (product.require_phone) {
                checkout_phone.setAttribute("required", true);
            } else {
                checkout_phone.removeAttribute("required");
            }

            if (product.require_comment) {
                checkout_comment.setAttribute("required", true);
            } else {
                checkout_comment.removeAttribute("required");
            }

            // lock user from editing personal info if customer is defined
            if (customer !== undefined) {
                const inputName = checkout_node.querySelector("#checkout_name");
                const inputEmail = checkout_node.querySelector("#checkout_email");

                checkout_name.value = customer.name;
                checkout_email.value = customer.email;
                window.checkoutPhone.setNumber(customer.phone);

                inputName.disabled = true;
                inputEmail.disabled = true;
                checkout_phone.disabled = true;
            }

            // event handlers
            function submitHandler(event) {
                event.preventDefault();

                // disable charge button when cards validation errors are present
                if (this.card_validation_error) {
                    return;
                }

                const comment = checkout_node.querySelector("#checkout_comment").value;
                if (customer === undefined) {
                    // get customer info from checkout overlay
                    const customer = {};
                    customer.name = checkout_node.querySelector("#checkout_name").value;
                    customer.email = checkout_node.querySelector("#checkout_email").value;
                    customer.phone = window.checkoutPhone.getNumber();

                    const order = {
                        "product": product,
                        "customer": customer,
                        "comment": comment
                    }

                    window.checkoutPhone.destroy();
                    // checkout_node.parentNode.removeChild(checkout_node);
                    this.old_checkout = checkout_node;
                    resolve(order);
                    return;
                }

                const order = {
                    "product": product,
                    "customer": customer,
                    "order_comment": comment
                }
                window.checkoutPhone.destroy();
                // checkout_node.parentNode.removeChild(checkout_node);
                this.old_checkout = checkout_node;
                resolve(order);
            }
            function cancelHandler() {
                window.checkoutPhone.destroy();
                checkout_node.parentNode.removeChild(checkout_node);
                resolve("abort");
            }

            this.error_div = document.querySelector('#card-errors');

            // remount validated input fields
            const card_element = checkout_node.querySelector("#card-element");
            this.card.mount(card_element);

            // Attach event listeners
            checkout_node.querySelector(".close").addEventListener("click", cancelHandler.bind(this));
            checkout_node.querySelector(".locked").addEventListener("click", cancelHandler.bind(this));
            document.querySelector('#payment-form').addEventListener("submit", submitHandler.bind(this));
            this.card.addEventListener('change', this.card_validation_handler.bind(this));
        });
    }

    /**
     * Charge a order.customer for a order.product 
     * @param {object} order 
     * @returns a promise that get fulfilled when successful request from server is received.
     */
    charge(order) {
        return new Promise(async (resolve, reject) => {
            try {
                if (Store.chargeLock) reject("Lock not acquired");
                Store.chargeLock = true;
                console.log("Charge lock acquired");
                if (order.product == null || order.customer == null) {
                    reject("Cannot perform a charge without a customer or a product");
                }
                // docs: https://stripe.com/docs/js/payment_methods/create_payment_method
                const payment = await this.stripe.createPaymentMethod(
                    {
                        type: "card",
                        card: this.card,
                        billing_details: order.customer
                    }
                );
                if (payment.error !== undefined) {
                    reject(payment.error);
                }
                this.old_checkout.parentNode.removeChild(this.old_checkout);
                // absolute path is required because of dynamic document root
                const chargeResponse = await fetch(BASEURL + "/api/charge", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    // TODO: refactor as one object order
                    body: JSON.stringify({
                        payment_method_id: payment.paymentMethod.id,
                        product_hash: order.product.hash,
                        customer: order.customer,
                        comment: order.comment
                    })
                });
                const response = await chargeResponse.json();
                if (!chargeResponse.ok) {
                    reject(await response);
                }
                if (response.success) {
                    resolve(response)
                }

                if (response.error) {
                    reject(response);
                }

                if (response.requires_action) {
                    // 3d secure authentication
                    const cardHandlerResponse = await this.stripe.handleCardAction(response.payment_intent_client_secret)
                    if (cardHandlerResponse.error) {
                        reject(cardHandlerResponse.error.message);
                    }

                    const chargeResponse = fetch(BASEURL + "/api/charge", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify({
                            payment_intent_id: cardHandlerResponse.paymentIntent.id,
                            product_hash: order.product.hash,
                            customer: order.customer,
                            comment: order.comment
                        }),
                    }).then((response) => { return response.json() });

                    if (!(await chargeResponse).success) {
                        reject(await chargeResponse);
                    }

                    resolve(await chargeResponse);
                }

            } catch (error) {
                reject(error);
            } finally {
                console.log("Charge lock released");
                Store.chargeLock = false;
            }
        });
    }

    card_validation_handler(event) {
        const submitButton = document.querySelector("#payment-form [type='submit']");

        if (event.error) {
            this.card_validation_error = true;
            if (!submitButton.classList.contains("btn_disabled")) {
                submitButton.classList.add("btn_disabled");
            }
            this.error_div.textContent = event.error.message;
            return;
        }
        this.error_div.textContent = '';
        this.card_validation_error = false;
        if (submitButton.classList.contains("btn_disabled")) {
            submitButton.classList.remove("btn_disabled");
        }
    }

    /**
     * @deprecated
     */
    static hide_checkout_modal() {
        this.overlay.style.display = "none";
    }

    /**
    * Construct a product entry from template
    * @param {object} product 
    * @returns html node element
    */
    createProductEntry(product) {
        let product_hash = product.product_hash;
        let header = product.name;
        let description = product.description;
        let image = product.image;
        let price = product.price;
        let price_member = product.price_member;
        let sold = product.amount_sold || 0;
        let available = product.amount_available;

        // clone a product container block from <template>
        let t = document.querySelector("#store_dummy");
        let node = document.importNode(t.content, true);
        let productContainer = node.querySelector(".product_container");
        let bottom = node.querySelector(".bottom");
        productContainer.id = product_hash; // todo: change .id to .hash
        node.querySelector(".store_header").textContent = header;
        node.querySelector(".store_description").innerHTML = description;
        node.querySelector(".store_price").textContent = this.formatCurrency(price);
        if (price_member) {
            node.querySelector(".store_price_member_label").style.display = "block";
            node.querySelector(".store_price_member").textContent = this.formatCurrency(price_member);
        } else {
            node.querySelector(".store_price_member_label").style.display = "none";
        }
        node.querySelector(".store_availability").textContent = available == null ? "Unlimited" : sold + " / " + available;
        node.querySelector("img").src = image;

        if (product.amount_available && (product.amount_sold >= product.amount_available)) {
            node.querySelector(".purchase_button").textContent = "Sold out";
            return node;
        }

        const startTime = product.available_from ? new Date(1000 * product.available_from - this.server_time_offset) : false;
        const endTime = product.available_until ? new Date(1000 * product.available_until - this.server_time_offset) : false;
        const current_time = new Date();
        if (startTime && (current_time < startTime)) {
            node.querySelector(".purchase_button").textContent = "Product will be available " + startTime.toLocaleDateString() + " " + startTime.toLocaleTimeString();
            return node;
        }

        if (endTime && (current_time > endTime)) {
            node.querySelector(".purchase_button").textContent = "Product is no longer available";
            return node;
        }
        node.querySelector(".purchase_button").removeAttribute("disabled");
        return node;
    }

    formatCurrency(price) {
        return price + ",-";
    }

}
