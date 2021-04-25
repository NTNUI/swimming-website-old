var stripe = Stripe('pk_live_51DJlYeDrXat4oW2LvJSKiPpXlULKiXn2BiMgV8WtEsQ3FGmw0JiMfqWEptkjb70quqdCkojzfgQEdQwDFU6EelIo005aPGDyTZ');

// After some amount of time, we should stop trying to resolve the order synchronously:
var MAX_POLL_COUNT = 10;
var pollCount = 0;

function pollForSourceStatus() {
	stripe.retrieveSource({id: SOURCE_ID, client_secret: CLIENT_SECRET}).then(function(result) {
		var source = result.source;
		document.querySelectorAll(".source").forEach((item) => {
			item.style.display = "none";
		});
		document.querySelector("#source_" + (source.status == "consumed" ? "chargeable" : source.status) ).style.display = "block";
		if (source.status === 'chargeable' || source.status === "consumed") {
			// Make a request to your server to charge the Source.
			// Depending on the Charge status, show your customer the relevant message.
			pollCount = 0;
			pollForOrderStatus();
		} else if (source.status === 'pending' && pollCount < MAX_POLL_COUNT) {
			// Try again in a second, if the Source is still `pending`:
			pollCount += 1;
			setTimeout(pollForSourceStatus, 1000);
		} else {
			// Depending on the Source status, show your customer the relevant message.
		}
	});
}
function pollForOrderStatus() {
	document.querySelector("#source_chargeable").style.display = "block";
	fetch(status_url + "?source=" + SOURCE_ID)
	.then((data) => data.text())
	.then((charge) => {
		document.querySelectorAll(".charge").forEach((item) => {
			item.style.display = "none";
		});
		var disp = "pending";
		if (charge == "FINALIZED" || charge == "DELIVERED") disp="succeeded";
		else if (charge == "FAILED") disp = "failed";
		document.querySelector("#charge_" + disp).style.display = "block";

		if (disp == "pending" && pollCount++ < MAX_POLL_COUNT) setTimeout(pollForOrderStatus, 1000);
	});
}


