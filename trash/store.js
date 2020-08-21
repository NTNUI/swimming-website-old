function appendItem(item) {
	let id = item.id;
	let header = item.name;
	let description = item.description;
	let image = item.image;
	let price = item.price;
	let bought = item.amount_bought || 0;
	let max = item.amount_available;
	let startTime = item.available_from ? 1e3 * item.available_from - serverOffset : false;
	let endTime = item.available_until ? 1e3 * item.available_until - serverOffset : false;

	var t = document.querySelector("#store_dummy");
	let node = document.importNode(t.content, true);
	let itemContainer = node.querySelector(".store_item");
	let bottom = node.querySelector(".bottom");
	itemContainer.id = id;
	node.querySelector(".store_header").textContent = header;
	node.querySelector(".store_description").innerHTML = description;
	node.querySelector(".store_price").textContent = formatCurrency(price);
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
			openContainer.textContent = formatTime(timeLeft);
		}, 250);
	}
	if (endTime !== false) {
		console.log(endTime);
		let close = setInterval(function () { 
			let timeLeft = (endTime - new Date().getTime());	
			if (timeLeft < 0) {
				locked.timeout = true;
				clearInterval(close);
			}
			timeContainer.textContent = formatTime(timeLeft);
		}, 250);
	} 

	if (max > 0 && bought >= max) locked.soldout = true;
	let storeButton = node.querySelector(".store_button");
	let lastLock = {};
	setInterval(function () {
		if (locked == lastLock) return;
		lastLock = locked;
		if (locked.startTime || locked.soldout || locked.timeout) {
			itemContainer.classList.add("locked");
			storeButton.style.display = "none";
			bottom.querySelector(".store_countdown").style.display = "none";
			if (locked.startTime) {
				bottom.querySelector(".wait").style.display = "";
				bottom.querySelector(".soldout").style.display = "none";
				bottom.querySelector(".timeout").style.display = "none";
			} else if (locked.soldout) {
				bottom.querySelector(".wait").style.display = "none";
				bottom.querySelector(".soldout").style.display = "";
				bottom.querySelector(".timeout").style.display = "none";
			} else if (locked.timeout) {
				bottom.querySelector(".wait").style.display = "none";
				bottom.querySelector(".soldout").style.display = "none";
				bottom.querySelector(".timeout").style.display = "";
			}
		} else { 
			itemContainer.classList.remove("locked");
			bottom.querySelector(".store_countdown").style.display = "none";
			bottom.querySelector(".wait").style.display = "none";
			bottom.querySelector(".soldout").style.display = "none";
			bottom.querySelector(".timeout").style.display = "none";

		}
	}, 250);

	node.querySelector(".store_button").addEventListener("click", function (e) {
		display_store(item);
	});

	document.getElementById("store_container").appendChild(node);
}

var displayedItem;
function display_store(item) {
	let id = item.id;
	let title = item.name;
	let description = item.description;
	let img = item.image;

	displayedItem = item;

	var overlay = document.querySelector("#checkout_overlay");
	overlay.style.display = "block";
	overlay.querySelector("#checkout_title").textContent = title;
	overlay.querySelector("#checkout_id").value = id;
	overlay.querySelector("#checkout_description").innerHTML = description;
	overlay.querySelector("#checkout_img").src = img;
}

function hide_store() {
	document.querySelector("#checkout_overlay").style.display = "none";
}

function formatCurrency(money) {
	return (money/100).toFixed(2) + ",-";
}

function formatTime(time) {
	if (time < 0) return " i fortiden";
	let seconds = (time / 1000).toFixed(0);
	let weeks = Math.floor(seconds / (60*60*24*7));
	seconds %= 60*60*24*7;
	let days = Math.floor(seconds / (60*60*24));
	seconds %= 60*60*24;
	let hours = Math.floor(seconds / (60*60));
	seconds %= 60*60;
	let minutes = Math.floor(seconds / 60);
	seconds %= 60;

	let r = "";
	const translations = {
		"no": {
			"week": "uke",
			"day": "dag",
			"week_plural": "uker",
			"day_plural": "dager",
		}, "en": {
			"week": "week",
			"day": "day",
			"week_plural": "weeks",
			"day_plural": "days",
		}
	}
	const text = translations[lang];

	if (weeks > 0) r += weeks + " " + (weeks == 1 ? text.week : text.week_plural) + " ";
	if (days > 0) r+= days + " " + (days == 1 ? text.day : text.day_plural) + " ";
	r += (hours < 10 ? "0" : "") + hours + ":";
	r += (minutes < 10 ? "0" : "") + minutes + ":";
	r += (seconds < 10 ? "0" : "") + seconds;

	return r;
}

function getItems() {
	fetch(url, {})
	.then((data) => data.json())
	.then((json) => {
		// Clear container
		document.getElementById("store_container").innerHTML = "";
		// Add all store items
		for (var i in json) {
			let item = json[i];
			appendItem(item);
		}
	});
}


// Create a Stripe client.
var stripe = Stripe('pk_live_0cuW9KUctZXbUAAyGBSf04uk');
//var stripe = Stripe('pk_test_JkjQkK0cGEWUQ0NUWh6LytP8');


// Create an instance of Elements.
var elements = stripe.elements();

//  Custom styling can be passed to options when creating an Element.
//  (Note that this demo uses a wider set of styles than the guide below.)
var style = {
	base: {
		color: '#32325d',
		lineHeight: '18px',
		fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
		fontSmoothing: 'antialiased',
		fontSize: '16px',
		'::placeholder': {
			color: '#aab7c4'
		}
	},
	invalid: {
		color: '#fa755a',
		iconColor: '#fa755a'
	}
};

// Create an instance of the card Element.
var card = elements.create('card', {style: style});

// Add an instance of the card Element into the `card-element` <div>.
card.mount('#card-element');

// Handle real-time validation errors from the card Element.
card.addEventListener('change', function(event) {
	var displayError = document.getElementById('card-errors');
	if (event.error) {
		displayError.textContent = event.error.message;
	} else {
		displayError.textContent = '';
	}
});

// Handle form submission.
var form = document.getElementById('payment-form');
form.addEventListener('submit', function(event) {
	event.preventDefault();	
	var extra = {
		"amount": displayedItem.price,
		"currency": "nok",
		"owner": { 
			"name": document.querySelector("input[name=navn]").value,
			"email": document.querySelector("input[name=epost]").value
		},
		"metadata": {
			"item_id": displayedItem.id
		}
	}
	let phone = document.querySelector("input[name=phone]").value;
	if (phone != "") extra.owner.phone = phone;
	stripe.createSource(card, extra).then(function(result) {
		if (result.error) {
			// Inform the user if there was an error.
			var errorElement = document.getElementById('card-errors');
			errorElement.textContent = result.error.message;
		} else {
			//3D secure is not required, proceed with payment 
			stripeSourceHandler(result.source);	
		}
	});
});


function stripeSourceHandler(source) {
	// Insert the source ID into the form so it gets submitted to the server
	var form = document.getElementById('payment-form');
	var hiddenInput = document.createElement('input');
	hiddenInput.setAttribute('type', 'hidden');
	hiddenInput.setAttribute('name', 'source');
	hiddenInput.setAttribute('value', source.id);
	form.appendChild(hiddenInput);

	// Submit the form
	form.submit();
}
