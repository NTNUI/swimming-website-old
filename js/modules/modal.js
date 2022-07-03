"use strict";

/**
 * Display a modal that returns a promise. Promise is resolved with whatever option user clicks on in the modal.
 * @param {string} title of the modal
 * @param {string} message content of the modal
 * @param {string} btn_title_accept title on the accept button
 * @param {string} btn_title_decline title on the decline button.
 * @note if @p btn_title_decline is set to empty string then decline button will not be constructed
 * @note modal forces user to click on a button. Click events outside the modal will not be handled
 * @returns a promise which is resolved when user clicks on a button.
 * @example
 * display_modal("Warning", "What you are about to do is not a good idea", "Yolo", "Cancel")
 * .then((button)=>{
 *     console.log("user clicked on " + button); // Yolo | Cancel
 * });
 */
export async function display_modal(title, message, btn_title_accept = "Accept", btn_title_decline = "Decline", graphic = "") {
    // remove all modals
    document.querySelectorAll(".modal_background").forEach((modal) => { modal.parentNode.removeChild(modal) });
    // resolve promise when accept is clicked. reject when decline is clicked.
    return new Promise((resolve) => {
        // create a new modal
        let temp = document.getElementById("modal_template");
        let clone = temp.content.cloneNode(true);
        clone.querySelector(".modal_header").innerText = title;
        clone.querySelector(".modal_message").innerText = message;

        // create an accept button if exists
        if (btn_title_accept) {
            const button = clone.querySelector(".modal_accept_button");
            button.style.display = "inline-block";
            button.innerText = btn_title_accept;
            button.addEventListener("click", async (e) => {
                // Delete modal and resolve the promise
                await e;
                const overlay = e.srcElement.parentNode.parentNode.parentNode;
                overlay.parentNode.removeChild(overlay);
                resolve(btn_title_accept);
            });
        }

        // create decline button if decline text is set
        if (btn_title_decline) {
            const button = clone.querySelector(".modal_decline_button");
            button.style.display = "inline-block";
            button.innerText = btn_title_decline;
            button.addEventListener("click", async (e) => {
                // Delete modal and reject the promise
                await e;
                const overlay = e.srcElement.parentNode.parentNode.parentNode;
                overlay.parentNode.removeChild(overlay);
                resolve(btn_title_decline);
            });
        }
        const status_span = clone.querySelector(".status-graphic");
        switch (graphic) {
            case "wait":
                status_span.classList.add("lds-hourglass");
                break;
            case "failure":
                status_span.classList.add("modal_failure");
                status_span.innerHTML = "&times;";
                break;
            case "success":
                status_span.classList.add("modal_success");
                status_span.innerHTML = "&#x2713;";
                break;


            default:
                break;

        }
        document.body.appendChild(clone);
    });
}
