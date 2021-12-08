"use strict";
export default class Store {

    /**
     * 
     * @param {*} publishable_key Stripe publishable API key
     * @param {*} successCallback Callback when form purchase is completed. One message argument of type string is passed
     * @param {*} failureCallback Callback when form purchase fails. One message argument of type string is passed
     * @param {*} server_time_offset Difference between client time and server time.
     * @param {*} lang language. Accepted values are {"no", "en"}
     */
    static lock = false;
    constructor(publishable_key, server_time_offset = 0, lang = "en") {
        this.server_time_offset = server_time_offset;
        this.displayed_product = "";
        this.lang = lang;
        this.inventory = [];
        this.error_div = document.querySelector('#card-errors');
        this.form = document.querySelector('#payment-form');

        this.stripe = Stripe(publishable_key);
        this.elements = this.stripe.elements();
        this.card = this.elements.create('card');
        this.card.mount("#card-element");
        this.card.addEventListener('change', this.card_validation_handler.bind(this));

        this.overlay = document.querySelector("#checkout_overlay");
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
     * @param {object | null} customer if set then modal will have customer info being read only 
     * @returns a Promise for an Order object. Gets resolved when user commits to a purchase or rejected when user cancels or quits.
     */
    checkout(product, customer) {
        if (Store.lock) return;
        Store.lock = true;
        return new Promise((resolve, reject) => {
            // Create checkout modal
            this.displayed_product = product;
            this.overlay.style.display = "block";
            this.overlay.querySelector("#checkout_title").textContent = product.name;
            this.overlay.querySelector("#product_hash").value = product.product_hash;
            this.overlay.querySelector("#checkout_description").innerHTML = product.description;
            this.overlay.querySelector("#checkout_img").src = product.image;

            if (customer != null) {
                // lock user from editing personal info
                this.overlay.querySelector("#checkout_name").value = customer.name;
                this.overlay.querySelector("#checkout_email").value = customer.email;
                this.overlay.querySelector("#checkout_phone").value = customer.phone;
                this.overlay.querySelector("#checkout_name").disabled = true;
                this.overlay.querySelector("#checkout_email").disabled = true;
                this.overlay.querySelector("#checkout_phone").disabled = true;
                this.overlay.querySelector("#checkout_comment").style.display = "none";
            }
            // attach event listeners
            // reject promise if user cancels 
            this.overlay.querySelector("span.close").addEventListener("click", () => {
                this.overlay.style.display = "none";
                Store.lock = false;
                document.querySelector("#checkout_overlay").style.display = "none";
                reject();
            });
            this.overlay.querySelector("button.locked").addEventListener("click", () => {
                this.overlay.style.display = "none";
                Store.lock = false;
                document.querySelector("#checkout_overlay").style.display = "none";
                reject();
            });
            this.form.addEventListener('submit', (event) => {
                event.preventDefault();
                const overlay = document.querySelector("#checkout_overlay");
                let customer = {};
                customer.name = overlay.querySelector("#checkout_name").value;
                customer.email = overlay.querySelector("#checkout_email").value;
                customer.phone = overlay.querySelector("#checkout_phone").value;
                document.querySelector("#checkout_overlay").style.display = "none";
                resolve({ product: product, customer: customer });
            });
        });
    }

    /**
     * Charge a customer for a product 
     * @param {*} product 
     * @param {*} customer 
     * @returns a promise that get fulfilled when successful request from server is received.
     */
    charge(product, customer) {
        if (product == null || customer == null) {
            throw "Cannot commit to checkout without a customer or a product";
        }
        return new Promise(async (resolve, reject) => {
            try {
                // TODO: replace with something that returns a paymentIntent instead
                const payment = await this.stripe.createPaymentMethod(
                    {
                        type: "card",
                        card: this.card,
                        billing_details: customer
                    }
                );
                const chargeResponse = await fetch("api/charge", {
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
                        throw await chargeResponse.json();
                }
                resolve(await chargeResponse.json());
            } catch (error) {
                reject(error);
            } finally {
                Store.lock = false;
            }
        });
    }

    card_validation_handler(event) {
        if (event.error) {
            this.error_div.textContent = event.error.message;
            return;
        }
        this.error_div.textContent = '';
    }

    /**
     * @deprecated
     */
    static hide_checkout_modal() {
        document.querySelector("#checkout_overlay").style.display = "none";
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
        let bought = product.amount_bought || 0;
        let max = product.amount_available;
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
        node.querySelector(".store_availability").textContent = max == null ? "Unlimited" : bought + " / " + max;
        node.querySelector("img").src = image;

        let openContainer = node.querySelector(".store_opensin");
        let timeContainer = node.querySelector(".store_timeleft");
        let locked = { startTime: false, soldout: false, timeout: false };

        if (startTime !== false && startTime > new Date().getTime()) {
            locked.startTime = true;

            let open = setInterval(function() {
                let timeLeft = startTime - new Date().getTime();
                if (timeLeft < 0) {
                    locked.startTime = false;
                    clearInterval(open);
                }
                openContainer.textContent = this.formatTime(timeLeft).bind(this);
            }, 250);

        } else if (endTime !== false) {
            node.querySelector(".store_countdown").style.display = "";
            let close = setInterval(function() {
                let timeLeft = (endTime - new Date().getTime());
                if (timeLeft < 0) {
                    locked.timeout = true;
                    clearInterval(close);
                }
                timeContainer.textContent = this.formatTime(timeLeft);
            }.bind(this), 250);
        }

        if (max > 0 && bought >= max) locked.soldout = true;
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
