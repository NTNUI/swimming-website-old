"use strict";

// update all fields
function set_visibility(id, visibility) {
    let data = {
        "request_type": "update_visibility",
        "params": {
            "item_id": id,
            "visibility": visibility
        }
    }
    fetch(BASEURL + "/api/storeadmin", {
        method: 'PATCH',
        body: JSON.stringify(data),
    }).catch((err) => {
        alert("Something went wrong, check the console.");
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
    fetch(BASEURL + "/api/storeadmin", {
            method: "PATCH",
            body: JSON.stringify(data)
        })
        .then(() => {
            event.target.disabled = true
        })
        .catch((response) => {
            alert("Something went wrong. Check the console.");
            console.error(response.json());
        });
}

// New item submission
function submitNewStoreItemHandler(event) {
    event.preventDefault();

    // Get content from form
    let form_data = new FormData(event.target);
    form_data.append('file', document.getElementById("form-image"));
    // // Send request
    fetch(BASEURL + "/api/storeadmin", {
        method: 'POST',
        body: form_data
    }).then(
        alert("New item has been added to the store")
    ).catch((response => {
        console.error(response.json());
        alert("Could not add item to the store, check the console");
    }));
}

async function show_purchases(data) {
    let purchases_container = document.getElementById("purchases_list");
    purchases_container.innerHTML = ""; // Clear purchases from last selection

    Object.values(data).forEach(purchase => {
        // create a new row
        let row = document.getElementById("purchase-row").content.cloneNode(true);
        row.querySelector(".purchase-row-name").innerText = purchase.name;
        row.querySelector(".purchase-row-email").innerText = purchase.email;
        row.querySelector(".purchase-row-phone").innerText = purchase.phone;
        row.querySelector(".purchase-row-comment").innerText = purchase.comment;
        row.querySelector(".purchase-row-button-deliver").setAttribute("id", "button-deliver-" + purchase.id);
        row.querySelector(".purchase-row-button-deliver").setAttribute("onclick", "deliverHandler(event)");
        if (purchase.status == "DELIVERED") {
            row.querySelector(".purchase-row-button-deliver").disabled = true;
        }
        purchases_container.appendChild(row);
    });
    purchases_container.parentNode.removeAttribute("class", "hidden");
}

var dateEditor = function(cell, onRendered, success, cancel) {
    // cell - the cell component for the editable cell
    // onRendered - function to call when the editor has been rendered
    // success - function to call to pass the successfuly updated value to Tabulator
    // cancel - function to call to abort the edit and return to a normal cell

    // create and style input
    const val = cell.getValue() * 1000 || new Date().getTime();
    var cellValue = moment(val).format("YYYY-MM-DD H:mm:ss"),
        input = document.createElement("input");

    input.setAttribute("type", "datetime");

    input.style.padding = "4px"; // TODO: move to css
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

    //submit new value on blur or change
    input.addEventListener("blur", onChange);

    //submit new value on enter
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

// Create Date Editor
function createTableMatrix(store_items, store_groups) {
    let table = new Tabulator("#items", {
        layout: "fitDataStretch",
        data: store_items,
        groupBy: "group_id",
        groupHeader: function(value, count, data, group) {
            return store_groups[value] + " (" + count + ")";
        },
        columns: [{
                title: "Name",
                field: "name"
            },
            {
                title: "Sold",
                field: "amount_bought"
            },
            {
                title: "Available",
                field: "amount_available"
            },
            {
                title: "Price",
                field: "price",
                editor: "number",
                editorParams: {
                    min: 100,
                    mask: "9999999"
                }
                // TODO: add handler for price modification
            },
            {
                title: "Available from",
                field: "available_from",
                formatter: function(cell, formatterParams, onRendered) {
                    if (cell.getValue() === false) return "Always";
                    return new Date(cell.getValue() * 1000).toLocaleString();
                },
                editor: dateEditor
                    // TODO: add handler for date modification
            },
            {
                title: "Available until",
                field: "available_until",
                formatter: function(cell, formatterParams, onRendered) {
                    if (cell.getValue() === false) return "Always";
                    return new Date(cell.getValue() * 1000).toLocaleString();
                },
                editor: dateEditor
                    // TODO: add handler for date modification

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
                    set_visibility(data.item_hash, data.visibility);
                },
            }
        ],
        rowDblClick: function(e, row) {
            const data = row.getData();
            fetch(BASEURL + "/api/storeadmin?request_type=get_store_item&store_item_id=" + data.item_hash)
                .then(res => res.json())
                .then(purchases => show_purchases(purchases))
                .catch((err) => {
                    alert("Something went wrong, check the console.");
                    console.err(err);
                });
        }
    });
    return table;
}

addLoadEvent(() => {
    // TODO: Ok, this is super ugly. However to fix this you need to learn async / await in js.
    // I'm a person that spends 12h trying to make it work rather than spending 2h learning that stuff.
    // So i guess I'll let it be for now and hope this never becomes a problem in the future.

    // The core issue here is that function createTableMatrix require two values, but
    // the promises are not fulfilled at the time it is executed.
    fetch(BASEURL + "/api/storeadmin?request_type=get_store_groups")
        .then(result => result.json())
        .then((store_item_groups) => (
            fetch(BASEURL + "/api/storeadmin?request_type=get_store_items")
            .then(response => response.json())
            .then(store_items => createTableMatrix(store_items, store_item_groups))
            .catch((response) => {
                console.error(response);
                alert("Something went wrong, check the console");
            })))
        .catch((data) => {
            console.error(data.json());
            alert("Something went wrong, check the console");
        });

    // fee calculator
    document.getElementById("price-input").addEventListener("change", function() {
        document.getElementById("price-output").innerHTML = (document.getElementById("price-input").value * 1.012 + 1.8);
    });
    // overload default form handler
    document.getElementById("form-add-store-item").addEventListener('submit', submitNewStoreItemHandler);
});