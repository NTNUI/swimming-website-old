<?php
require_once("library/templates/store.php");
require_once("library/templates/content.php");
require_once("library/templates/modal.php");
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

	const requested_product_hash = "<?php isset($_REQUEST["product_hash"]) ? print $_REQUEST['product_hash'] : ""; ?>";

	function handle_order(store, order) {
		// on purchase click, display loading modal and send charge request to server
		display_modal("Loading", "Attempting to empty your bank account", "", "", "wait");
		store.charge(order.product, order.customer)
			.then(async (serverResponse) => {
				if (typeof(serverResponse === "object")) {
					serverResponse = serverResponse.message;
				}
				await display_modal("Success", serverResponse, "Accept", "", "success");
			}).catch((error) => {
				if (typeof(error === "object")) {
					error = error.message;
				}
				display_modal("Failure", error, "Accept", "", "failure");
			});
	}

	const store = new Store(STRIPE_PUBLISHABLE_KEY, SERVER_TIME_OFFSET);
	store.init(BASEURL + "/api/inventory").then(async () => {
		const inventory = await store.inventory;
		const requested_product = inventory.find(product => product.hash === requested_product_hash);

		if (inventory.length) {
			document.querySelector("#storeEmpty").classList.add("hidden");
		}
		for (let i = 0; i < inventory.length; ++i) {
			// don't display hidden elements
			if (!inventory[i].visibility) {
				continue;
			}
			// Create a product entry
			const productEntry = store.createProductEntry(inventory[i]);
			
			const purchaseButtonHandler = () => {
				// display checkout modal on purchase click
				store.checkout(inventory[i])
				.then((order) => {
					handle_order(store, order);
				});
			}
			productEntry.querySelector(".store_button").addEventListener("click", purchaseButtonHandler);
			// append product entry to DOM
			document.querySelector("#store_container").appendChild(productEntry);
		}

		// if some product is requested, display the purchase modal right away.
		if (requested_product) {
			store.checkout(requested_product)
				.then((order) => {
					handle_order(store, order);
				});
		}
	});
</script>
<script src="https://js.stripe.com/v3/"></script>
<?php
style_and_script(__FILE__);
?>