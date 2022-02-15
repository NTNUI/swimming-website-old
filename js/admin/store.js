"use strict";
import { display_modal } from "../modules/modal.js";
// TODO: add drag & drop support for image uploads
// TODO: move styles to css

function set_product_visibility(product_hash, visibility) {
    let data = {
        "request_type": "update_visibility",
        "params": {
            "product_hash": product_hash,
            "visibility": visibility
        }
    }
    fetch(BASEURL + "/api/store", {
        method: 'PATCH',
        body: JSON.stringify(data),
    }).catch((err) => {
        display_modal("Failure", err.json(), "Accept", "", "failure");
        console.error(err.json());
    });
}

function deliverHandler(event) {
    const order_id = event.target.id.substring("button-deliver-".length);
    const data = {
        "request_type": "update_delivered",
        "params": {
            "order_id": order_id,
            "order_status": "DELIVERED"
        }
    }
    fetch(BASEURL + "/api/store", {
        method: "PATCH",
        body: JSON.stringify(data)
    })
        .then(() => {
            event.target.disabled = true
        })
        .catch((response) => {
            display_modal("Failure", response.json(), "Accept", "", "failure");
            console.error(response.json());
        });
}

function submitNewProductHandler(event) {
    event.preventDefault();

    // Get content from form
    let form_data = new FormData(event.target);
    form_data.append('file', document.getElementById("form-image"));

    // Send request
    fetch(BASEURL + "/api/store", {
        method: 'POST',
        body: form_data
    }).then((response) => {
        if (!response.ok) {
            throw response.json();
        }
        display_modal("Success", "New product has been added to the store", "Accept", "", "success");
    }
    ).catch(async (response_promise) => {
        const response = await response_promise;
        if (response.error) {
            display_modal("Failure", response.message + "\n" + response.trace, "Accept", "", "failure");
        } else {
            console.error(response);
        }
    });
}

async function show_orders(data) {
    let orders_container = document.getElementById("order_list");
    orders_container.innerHTML = ""; // Clear orders from last selection

    Object.values(data).forEach(order => {
        // create a new row
        let row = document.getElementById("purchase-row").content.cloneNode(true);
        row.querySelector(".purchase-row-name").innerText = order.name;
        row.querySelector(".purchase-row-email").innerText = order.email;
        row.querySelector(".purchase-row-phone").innerText = order.phone;
        row.querySelector(".purchase-row-comment").innerText = order.comment;
        row.querySelector(".purchase-row-button-deliver").setAttribute("id", "button-deliver-" + order.id);
        row.querySelector(".purchase-row-button-deliver").addEventListener("click", deliverHandler);
        if (order.status == "DELIVERED") {
            row.querySelector(".purchase-row-button-deliver").disabled = true;
        }
        orders_container.appendChild(row);
    });
    orders_container.parentNode.removeAttribute("class", "hidden");
}

let dateEditor = function(cell, onRendered, success, cancel) {
    // cell - the cell component for the editable cell
    // onRendered - function to call when the editor has been rendered
    // success - function to call to pass the successfully updated value to Tabulator
    // cancel - function to call to abort the edit and return to a normal cell

    // create and style input
    const val = cell.getValue() * 1000 || new Date().getTime();
    let cellValue = moment(val).format("YYYY-MM-DD H:mm:ss");
    const input = document.createElement("input");

    input.setAttribute("type", "datetime-local");

    input.style.padding = "4px";
    input.style.width = "100%";
    input.style.boxSizing = "border-box";

    input.value = cellValue;

    onRendered(function() {
        input.focus();
        input.style.height = "100%";
    });

    function onChange() {
        if (input.value != cellValue) {
            if (input.value == "") success(false);
            success(moment(input.value, "YYYY-MM-DD H:mm:ss").unix());
        } else {
            cancel();
        }
    }

    input.addEventListener("change", onChange);

    // submit new value on enter
    input.addEventListener("keydown", function(e) {
        if (e.key == "Enter") {
            onChange();
        }

        if (e.key == "Escape") {
            cancel();
        }
    });

    return input;
};


/**
 * Update when a product is available for purchase
 * @param {*} product_data
 */
function update_availability(product_data) {
    fetch(BASEURL + "/api/store", {
        method: 'PATCH',
        body: JSON.stringify(product_data),
    })
        .then(async (response) => {
            if (!response.ok) {
                throw await response.json();
            }
        })
        .catch((err) => {
            console.error(err);
            if (typeof (err) === "object") {
                display_modal("Failure", err.message + "\n\n" + err.trace, "Accept", "", "failure");
                return;
            }
            display_modal("Failure", err, "Accept", "", "failure");
        });
}

/**
 * TODO: documentation
 * @param {array<product>} products 
 * @param {array} product_groups 
 * @returns 
 */
function createTableMatrix(products, product_groups) {
    // todo: add other properties as read only or something
    products.forEach(product => {
        product.link_no = BASEURL + "/store?product_hash=" + product.hash;
        product.link_en = BASEURL + "/en/store?product_hash=" + product.hash;
    });
    const table = new Tabulator("#products",
        {
        layout: "fitDataStretch",
        data: products,
        groupBy: "group_id",
        groupHeader: function(value, count, data, group) {
            return product_groups[value] + " (" + count + ")";
        },
        columns: [{
            title: "Name",
            field: "name",
            cellClick: () => {
                display_modal("Info", "Changing the name of a product is not yet supported", "Accept", "");
            }
        },
        {
            title: "Sold",
            field: "amount_sold"
        },
        {
            title: "Available",
            field: "amount_available",
            formatter: function(cell, formatterParams, onRendered) {
                return cell.getValue() === null ? "Unlimited" : cell.getValue();
            },
            cellClick: () => {
                display_modal("Info", "Changing the amount of available products is not yet supported", "Accept", "");
            }
        },
        {
            title: "Price",
            field: "price",
            editor: "number",
            editorParams: {
                min: 100,
                mask: "9999999"
            },
            cellEdited: (cell) => {
                const data = {
                    "request_type": "update_price",
                    "params": {
                        "product_hash": cell.getRow().getData().hash,
                        "price": cell.getValue()
                    }
                }
                fetch(BASEURL + "/api/store", {
                    method: 'PATCH',
                    body: JSON.stringify(data),
                })
                    .then(async (response) => {
                        if (!response.ok) {
                            throw await response.json();
                        }
                    })
                    .catch((err) => {
                        console.error(err);
                        if (typeof (err) === "object") {
                            display_modal("Failure", err.message + "\n\n" + err.trace, "Accept", "", "failure");
                            return;
                        }
                        display_modal("Failure", err, "Accept", "", "failure");
                    });

            }
        },
        {
            title: "Available from",
            field: "available_from",
            formatter: function(cell, formatterParams, onRendered) {
                if (cell.getValue() === null) return "Always";
                return new Date(cell.getValue() * 1000).toLocaleString('nb-no', { timezone: "Europe/Oslo" });
            },
            editor: dateEditor,
            cellEdited: function(cell) {
                const data = {
                    "request_type": "update_availability",
                    "params": {
                        "product_hash": cell.getRow().getData().hash,
                        "date_start": new Date(cell.getValue() * 1000).toLocaleString('nb-no', { timezone: "Europe/Oslo" })
                    }
                }
                update_availability(data);
            }
        },
        {
            title: "Available until",
            field: "available_until",
            formatter: function(cell, formatterParams, onRendered) {
                if (cell.getValue() === null) return "Always";
                return new Date(cell.getValue() * 1000).toLocaleString('nb-no', { timezone: "Europe/Oslo" });
            },
            editor: dateEditor,
            cellEdited: function(cell) {
                const data = {
                    "request_type": "update_availability",
                    "params": {
                        "product_hash": cell.getRow().getData().hash,
                        "date_end": new Date(cell.getValue() * 1000).toLocaleString('nb-no', { timezone: "Europe/Oslo" })
                    }
                }
                update_availability(data);
            }

        },
        {
            title: "Visible",
            field: "visibility",
            editor: true,
            formatter: "tickCross",
            sorter: "boolean",
            cellEdited: function(cell) {
                // send data to server
                const data = cell.getData();
                set_product_visibility(data.hash, data.visibility);
            },
        },
        {
            title: "Link NO",
            field: "link_no",
            formatter: (cell) => { return '<i class="fa fa-copy"></i>' },
            editor: false,
            cellClick: (e, cell) => {
                navigator.clipboard.writeText(cell.getValue());
            }
        },
        {
            title: "Link EN",
            field: "link_en",
            formatter: (cell) => { return '<i class="fa fa-copy"></i>' },
            editor: false,
            cellClick: (e, cell) => {
                navigator.clipboard.writeText(cell.getValue());
            }
        }
        ],
        rowDblClick: function(e, row) {
            const data = row.getData();
            fetch(BASEURL + "/api/store?request_type=get_orders&product_hash=" + data.hash)
                .then(res => res.json())
                .then((orders) => {
                    show_orders(orders);
                })
                .catch((err) => {
                    display_modal("Failure", err, "Accept", "", "failure");
                    console.err(err);
                });
        }
    });
    return table;
}

addLoadEvent(async () => {

    // TODO: add inside a try catch block
    const product_groups = await (await fetch(BASEURL + "/api/store?request_type=get_product_groups")).json();
    const products = await (await fetch(BASEURL + "/api/store?request_type=get_products")).json();
    createTableMatrix(products, product_groups);

    // fee calculator
    document.getElementById("price-input").addEventListener("change", function() {
        document.getElementById("price-output").innerHTML = (document.getElementById("price-input").value * 1.012 + 1.8);
    });
    // overload default form handler
    document.getElementById("form-add-product").addEventListener('submit', submitNewProductHandler);
});