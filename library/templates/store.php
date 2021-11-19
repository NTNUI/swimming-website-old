<template id="store_dummy">
	<div class="store_item">
		<h3 class="store_header"></h3>
		<div>
			<p class="store_description"></p>
		</div>
		<div class="card">
			<img alt="Some image">
			<p class="store_price"><?php print $t->get_translation("pris") ?></p>
		</div>
		<span class="store_availability"></span>
		<div class="bottom">
				<button class="store_button"><?php print $t->get_translation("kjop"); ?></button>
				<button class="locked wait"><?php print $t->get_translation("opens"); ?> <span class="store_opensin"></span></button>
				<button class="locked soldout"><?php print $t->get_translation("soldout");  ?></button>
				<button class="locked timeout"><?php print $t->get_translation("timeout"); ?></button>
				<div class="store_countdown" style="display: none">
					<?php print $t->get_translation("closes_in"); ?>
					<span class="store_timeleft"></span>
				</div>
			</div>
	</div>
</template>

<div id="checkout_overlay" class="store_modal">
	<div>
		<div style="width:100%;">
			<h2><?php print $t->get_translation("overlay_header", "store"); ?></h2>
			- <b id="checkout_title"></b><br>
			<img id="checkout_img" alt="Image">
			<p id="checkout_description"></p>
		</div>
		<div style="width: 100%; float:left;">
			<h1><?php print $t->get_translation("overlay_checkout", "store"); ?></h1>
			<form id="payment-form" action="<?php print $t->get_url("api/order") ?>" method="POST">
				<input id="store_item_hash" name="store_item_hash" type="hidden" />
				<label for="navn"><?php print $t->get_translation("navn", "store") ?></label>
				
                <input id="checkout_name" id="checkout_name" name="navn" type="text" placeholder="Ola Nordmann" required />
				<label for="epost"><?php print $t->get_translation("epost", "store"); ?></label>
				
                <input id="checkout_email" name="epost" type="email" placeholder="me@example.com" required />
				<label for="phone"><?php print $t->get_translation("telefon", "store"); ?></label>
				
                <input id="checkout_phone" name="phone" type="number" placeholder="12345678" />
				<label for="kommentar"><?php print $t->get_translation("kommentar", "store"); ?></label>
				
                <textarea id="checkout_comment" name="kommentar"></textarea>

				<div class="form-row">
					<label for="card-element">
						<?php print $t->get_translation("pay_with_card_label", "store"); ?>
					</label>
					<div id="card-element">
						<!-- A Stripe Element will be inserted here. -->
					</div>

					<!-- Used to display form errors. -->
					<div id="card-errors" role="alert"></div>
				</div>
				<button onclick="hide_store(event)" class="locked" style="float: left; width: 45%"><?php print $t->get_translation("cancel", "store"); ?></button>
				<button type="submit" style="float: right; width: 45%"><?php print $t->get_translation("kjop", "store"); ?></button>
			</form>
		</div>
		<span class="close" onclick="hide_store()">&times;</span>
	</div>
</div>

<div id="loading_box" class="store_modal">
	<div>
		<h3 id="waitingHeader" class="hidden"><?php print $t->get_translation("waiting_message", "store"); ?></h3>
		<h3 id="completedHeader" class="t hidden"><?php print $t->get_translation("payment_complete", "store"); ?></h3>
		<h3 id="failedHeader" class="hidden t">error found:<span id="failedContent"></span></h3>
		<div id="waitingGlass" class="lds-hourglass hidden"></div>
		<span id="paymentComplete" class="hidden">&#x2713;
	</div>
	<span id="failedCross" class="hidden">&times;</span>
	<span class="close" onclick="hide_load()">&times;</span>
</div>
