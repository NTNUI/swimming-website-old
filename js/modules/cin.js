"use strict";

/**
 * 
 * @returns json list containing member id and cin number of each user.
 */
async function get_not_payed_cin() {
    return await fetch(BASEURL + "api/cin", { headers: { "Accept": "Application/json" }, body: JSON.stringify("not_payed") }).then((data) => { data.json() });
}

async function get_members_without_valid_cin(){
    return await fetch(BASEURL + "api/cin", { headers: { "Accept": "Application/json" }, body: JSON.stringify("missing") }).then((data) => { data.json() });
}

/**
 * 
 * @param {array} member_ids
 */
function set_id_as_payed(member_ids) {
    fetch(BASEURL + "api/cin", {
        headers: {
            "Accept": "Application/json"
        },
        method: "PATCH",
        body: JSON.stringify(member_ids),

    })
}