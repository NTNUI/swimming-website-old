function set_visibility(id, visibility) {
    getJSON(BASEURL + "/api/storeadmin?type=set_visibility&item_id=" + id + "&visibility=" + visibility, (error, data) => {
        if (error) {
            console.error("Something went wrong");
            console.error(data);
            return;
        }

    });
}

var dateEditor = function(cell, onRendered, success, cancel) {
    //cell - the cell component for the editable cell
    //onRendered - function to call when the editor has been rendered
    //success - function to call to pass the successfuly updated value to Tabulator
    //cancel - function to call to abort the edit and return to a normal cell

    //create and style input
    //
    const val = cell.getValue() * 1000 || new Date().getTime();
    var cellValue = moment(val).format("YYYY-MM-DD H:mm:ss"),
        input = document.createElement("input");

    input.setAttribute("type", "datetime");

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

    //submit new value on blur or change
    input.addEventListener("blur", onChange);

    //submit new value on enter
    input.addEventListener("keydown", function(e) {
        if (e.keyCode == 13) {
            onChange();
        }

        if (e.keyCode == 27) {
            cancel();
        }
    });

    return input;
};

function mark_delivered(item_id, id) {
    getJSON(BASEURL + "/api/storeadmin?type=mark_delivered&item_id=" + item_id + "&id=" + id, (error, data) => {
        if (error || data.success != true) {
            console.error(data);
            return;
        }
        document.getElementById("box-" + id).classList.add("green");
        document.getElementById("button-" + id).disabled = true;
        document.getElementById("button-" + id).innerText = "Satt som levert";
    });
}

//Create Date Editor

function createTableMatrix(store_data, groups) {

    return table = new Tabulator("#items", {
        layout: "fitDataStretch",
        data: store_data,
        groupBy: "group_id",
        groupHeader: function(value, count, data, group) {
            return groups[value] + "<span style='color:#d00; margin-left:10px;'>(" + count + ")</span>";
        },
        columns: [{
                title: "Tittel",
                field: "name"
            },
            {
                title: "Kjøpt",
                field: "amount_bought"
            },
            {
                title: "Tilgjengelig",
                field: "amount_available"
            },
            {
                title: "Pris",
                field: "price"
            },
            {
                title: "Tilgjengelig FRA",
                field: "available_from",
                formatter: function(cell, formatterParams, onRendered) {
                    if (cell.getValue() === false) return "Tidenes morgen";
                    return new Date(cell.getValue() * 1000).toLocaleString();
                },
                editor: dateEditor
            },
            {
                title: "Tilgjengelig TIL",
                field: "available_until",
                formatter: function(cell, formatterParams, onRendered) {
                    if (cell.getValue() === false) return "Verdens ende";
                    return new Date(cell.getValue() * 1000).toLocaleString();
                },
                editor: dateEditor
            },
            {
                title: "Synlig",
                field: "visibility",
                editor: true,
                formatter: "tickCross",
                sorter: "boolean",
                cellEdited: function(cell) {
                    // send data to server
                    const data = cell.getData();
                    set_visibility(data.api_id, data.visibility);
                },
            }
        ],
        rowDblClick: function(e, row) {
            const data = row.getData();
            getJSON(BASEURL + "/api/storeadmin?type=get_item&item_id=" + data.api_id, (error, data) => {
                console.log(data);
            });
            // window.location.assign(BASEURL + "/api/storeadmin?type=get_item&item_id=" + data.api_id);
        }
    });

}