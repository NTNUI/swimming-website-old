<?php
include_once("library/templates/store.php");
include_once("library/templates/content.php");
include_once("library/templates/modal.php");
global $t;
print_content_header(
	$t->get_translation("mainHeader"),
	$t->get_translation("subHeader")
);

?>
<div class="box" id="storeEmpty">
	<h2>
		<?php print $t->get_translation("store_empty_header"); ?>
	</h2>
	<p>
		<?php print $t->get_translation("store_empty_content"); ?>
	</p>
</div>

<div id="store_container"></div>

<script type="module">
	"use strict";
	import Store from "./js/modules/store.js";
	import {
		display_modal
	} from "./js/modules/modal.js";

	const store = new Store(STRIPE_PUBLISHABLE_KEY, SERVER_TIME_OFFSET);
	store.init(BASEURL + "/api/inventory").then(async () => {
		const inventory = await store.inventory;

		if (inventory.length) {
			document.querySelector("#storeEmpty").classList.add("hidden");
		}
		for (let i = 0; i < inventory.length; ++i) {
			// Create a product entry
			let productEntry = store.createProductEntry(inventory[i]);

			let purchaseButtonHandler = () => {
				// on buy click display checkout modal
				store.checkout(inventory[i])
					.then((order) => {
						// on purchase click, display loading modal and send charge request to server
						display_modal("Loading", "Attempting to empty your bank account", "", "", "wait");
						store.charge(order.product, order.customer)
							.then(async (serverResponse) => {
								if (typeof(serverResponse) === "object") {
									serverResponse = serverResponse.message;
								}
								// wait until user closes the dialog, then hide the checkout modal
								await display_modal("Success", serverResponse, "Accept", "", "success");
								Store.hide_checkout_modal();
							})
							.catch((error) => {
								if (typeof(error == "object")) {
									error = error.message;
								}
								display_modal("Failure", error, "Accept", "", "failure");
							});
					});
			}
			productEntry.querySelector(".store_button").addEventListener("click", purchaseButtonHandler);
			// append product entry to DOM
			document.querySelector("#store_container").appendChild(productEntry);
		}
		<?php
		if (isset($_REQUEST['product_hash'])) {
		?>
			// if some product is requested, display the purchase modal right away.
			// TODO: refactor with code above.
			const requested_product = inventory.find(product => product.hash == "<?php print $_REQUEST['product_hash'] ?>");
			if (requested_product) {
				store.checkout(requested_product)
					.then((order) => {
						// on purchase click, display loading modal and send charge request to server
						display_modal("Loading", "Attempting to empty your bank account", "", "", "wait");
						store.charge(order.product, order.customer).then(async (serverResponse) => {
							if (typeof(serverResponse == "object")) {
								serverResponse = serverResponse.message;
							}
							await display_modal("Success", serverResponse, "Accept", "", "success");
							Store.hide_checkout_modal();
						}).catch((error) => {
							if (typeof(error == "object")) {
								error = error.message;
							}
							display_modal("Failure", error, "Accept", "", "failure");
						});
					});
			}
		<?php
		}
		?>
	});
</script>
<script src="https://js.stripe.com/v3/"></script>
<?php 
style_and_script(__FILE__);
?>
