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

        this.error_div = document.querySelector('#card-errors');
        this.form = document.querySelector('#payment-form');
        this.submitButton = this.form.querySelector("[type='submit']");
        this.overlay = document.querySelector("#checkout_overlay");

        this.stripe = Stripe(publishable_key);
        this.elements = this.stripe.elements();
        this.card = this.elements.create('card');
        // this.card.mount("#card-element");
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
            // Update product description
            this.displayed_product = product;
            this.overlay.querySelector("#checkout_title").textContent = product.name;
            this.overlay.querySelector("#product_hash").value = product.product_hash;
            this.overlay.querySelector("#checkout_description").innerHTML = product.description;
            this.overlay.querySelector("#checkout_img").src = product.image;
            this.overlay.querySelector("#checkout_price").textContent = product.price / 100 + " NOK";

            // unhide the overlay
            this.overlay.style.display = "block";

            // Recreate the node to clear out old event listeners
            // If old event listeners are not cleared out multiple
            // charges might occur for one click.
            this.form = document.querySelector('#payment-form');
            let old_element = this.form;
            let new_element = old_element.cloneNode(true);
            old_element.parentNode.replaceChild(new_element, old_element);

            // remount validated input fields
            this.card.mount("#card-element");
            this.checkoutPhoneInput = this.overlay.querySelector("#checkout_phone");
            window.checkoutPhone = window.intlTelInput(this.checkoutPhoneInput, {
                initialCountry: "no",
                separateDialCode: true,
                utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.15/js/utils.min.js"
            });

            // lock user from editing personal info if customer is defined
            if (customer !== undefined) {
                const inputName = this.overlay.querySelector("#checkout_name");
                const inputEmail = this.overlay.querySelector("#checkout_email");

                inputName.value = customer.name;
                inputEmail.value = customer.email;
                window.checkoutPhone.setNumber(customer.phone);

                inputName.disabled = true;
                inputEmail.disabled = true;
                this.checkoutPhoneInput.disabled = true;
                // this.overlay.querySelector("#checkout_comment").style.display = "none";
            }
            
            function submitHandler(event) {
                event.preventDefault();

                // disable charge button when cards validation errors are present
                if (this.card_validation_error) {
                    return;
                }

                if (customer === undefined) {
                    // get customer info from checkout overlay
                    const customer = {};
                    customer.name = this.overlay.querySelector("#checkout_name").value;
                    customer.email = this.overlay.querySelector("#checkout_email").value;
                    customer.phone = window.checkoutPhone.getNumber();

                    // close checkout modal and return new order
                    this.overlay.style.display = "none";
                    resolve({ product: product, customer: customer });
                    return;
                }
                // close checkout modal and return new order
                this.overlay.style.display = "none";
                resolve({ product: product, customer: customer });
            }

            // Attach event listeners
            document.querySelector('#payment-form').addEventListener("submit", submitHandler.bind(this));
            this.card.addEventListener('change', this.card_validation_handler.bind(this));
            this.overlay.querySelectorAll("span.close, button.locked").forEach(element => {
                element.addEventListener("click", () => {
                    this.overlay.style.display = "none";
                });
            });
        });
    }

    /**
     * Charge a customer for a product 
     * @param {object} product 
     * @param {object} customer 
     * @returns a promise that get fulfilled when successful request from server is received.
     */
    charge(product, customer) {
        console.log("charge called");
        return new Promise(async (resolve, reject) => {
            try {
                if (Store.chargeLock) reject("Lock not acquired");
                Store.chargeLock = true;
                console.log("Lock acquired");
                if (product == null || customer == null) {
                    reject("Cannot commit to checkout without a customer or a product");
                }
                // TODO: replace with something that returns a paymentIntent instead
                const payment = await this.stripe.createPaymentMethod(
                    {
                        type: "card",
                        card: this.card,
                        billing_details: customer
                    }
                );
                if (payment.error !== undefined) {
                    reject(payment.error);
                }
                // absolute path is required because of dynamic document root
                const chargeResponse = await fetch(BASEURL + "/api/charge", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        payment_method_id: payment.paymentMethod.id,
                        product_hash: product.hash,
                        owner: customer,
                    })
                });
                switch (chargeResponse.status) {
                    case 200:
                        break;
                    case 500:
                        throw "server error";
                    default:
                        reject(await chargeResponse.json());
                }
                const response = await chargeResponse.json();
                if (response.success) {
                    resolve(response.message)
                }

                if (response.error) {
                    reject(response.error);
                }

                if (response.requires_action) {
                    // 3d secure authentication
                    const cardHandlerResponse = await this.stripe.handleCardAction(response.payment_intent_client_secret)
                    if (cardHandlerResponse.error) {
                        reject(cardHandlerResponse.error.message);
                    }

                    const chargeResponse = fetch("api/charge", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify({
                            payment_intent_id: cardHandlerResponse.paymentIntent.id,
                            product_hash: product.hash,
                            owner: customer
                        }),
                    });
                    const chargeResponseJSON = (await chargeResponse).json();

                    if (!(await chargeResponseJSON).success) {
                        reject((await chargeResponseJSON).message);
                    }

                    resolve((await chargeResponseJSON).message);
                }

            } catch (error) {
                reject(error);
            } finally {
                console.log("lock released");
                Store.chargeLock = false;
            }
        });
    }

    card_validation_handler(event) {

        if (event.error) {
            this.card_validation_error = true;
            if (!this.submitButton.classList.contains("btn_disabled")) {
                this.submitButton.classList.add("btn_disabled");
            }
            this.error_div.textContent = event.error.message;
            return;
        }
        this.error_div.textContent = '';
        this.card_validation_error = false;
        if (this.submitButton.classList.contains("btn_disabled")) {
            this.submitButton.classList.remove("btn_disabled");
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
        let sold = product.amount_sold || 0;
        let available = product.amount_available;
        let startTime = product.available_from ? 1e3 * product.available_from - this.serverOffset : false;
        let endTime = product.available_until ? 1e3 * product.available_until - this.serverOffset : false;

        // clone a product container block from <template>
        let t = document.querySelector("#store_dummy");
        let node = document.importNode(t.content, true);
        let productContainer = node.querySelector(".product_container");
        let bottom = node.querySelector(".bottom");
        productContainer.id = product_hash; // todo: change for .id to .hash
        node.querySelector(".store_header").textContent = header;
        node.querySelector(".store_description").innerHTML = description;
        node.querySelector(".store_price").textContent = this.formatCurrency(price);
        node.querySelector(".store_availability").textContent = available == null ? "Unlimited" : sold + " / " + available;
        node.querySelector("img").src = image;

        let openContainer = node.querySelector(".store_opensin");
        let timeContainer = node.querySelector(".store_timeleft");
        let locked = { startTime: false, soldout: false, timeout: false };

        if (startTime !== false && startTime > new Date().getTime()) {
            locked.startTime = true;

            let open = setInterval(function() {
                const timeLeft = startTime - new Date().getTime();
                if (timeLeft < 0) {
                    locked.startTime = false;
                    clearInterval(open);
                }
                openContainer.textContent = this.formatTime(timeLeft).bind(this);
            }, 250);

        } else if (endTime !== false) {
            node.querySelector(".store_countdown").style.display = "";
            let close = setInterval(function() {
                const timeLeft = (endTime - new Date().getTime());
                if (timeLeft < 0) {
                    locked.timeout = true;
                    clearInterval(close);
                }
                timeContainer.textContent = this.formatTime(timeLeft);
            }.bind(this), 250);
        }

        if (available > 0 && sold >= available) locked.soldout = true;
        let storeButton = node.querySelector(".store_button");
        let lastLock = {};

        setInterval(function() {
            if (locked == lastLock) return;
            lastLock = locked;
            if (locked.startTime || locked.soldout || locked.timeout) {
                storeButton.style.display = "none";
                bottom.querySelector(".store_countdown").style.display = "none";

                // hide all info labels
                bottom.querySelector(".wait").style.display = "none";
                bottom.querySelector(".soldout").style.display = "none";
                bottom.querySelector(".timeout").style.display = "none";

                // unhide correct label
                if (locked.startTime) {
                    bottom.querySelector(".wait").style.display = "";
                } else if (locked.soldout) {
                    bottom.querySelector(".soldout").style.display = "";
                } else if (locked.timeout) {
                    bottom.querySelector(".timeout").style.display = "";
                }
            } else {
                // hide all elements
                productContainer.classList.remove("locked");
                bottom.querySelector(".wait").style.display = "none";
                bottom.querySelector(".soldout").style.display = "none";
                bottom.querySelector(".timeout").style.display = "none";

            }
        }, 250);
        return node;
    }

    formatCurrency(price) {
        return (price / 100).toFixed(2) + ",-";
    }


    formatTime(time, lang = "en") {
        if (time < 0) return " i fortiden";
        let seconds = (time / 1000).toFixed(0);
        const weeks = Math.floor(seconds / (60 * 60 * 24 * 7));
        seconds %= 60 * 60 * 24 * 7;
        const days = Math.floor(seconds / (60 * 60 * 24));
        seconds %= 60 * 60 * 24;
        const hours = Math.floor(seconds / (60 * 60));
        seconds %= 60 * 60;
        const minutes = Math.floor(seconds / 60);
        seconds %= 60;

        const translations = {
            "no": {
                "week": "uke",
                "day": "dag",
                "week_plural": "uker",
                "day_plural": "dager",
            },
            "en": {
                "week": "week",
                "day": "day",
                "week_plural": "weeks",
                "day_plural": "days",
            }
        }
        const text = translations[lang];

        let r = "";
        if (weeks > 0) r += weeks + " " + (weeks == 1 ? text.week : text.week_plural) + " ";
        if (days > 0) r += days + " " + (days == 1 ? text.day : text.day_plural) + " ";
        r += (hours < 10 ? "0" : "") + hours + ":";
        r += (minutes < 10 ? "0" : "") + minutes + ":";
        r += (seconds < 10 ? "0" : "") + seconds;
        return r;
    }

}
