<?php
global $t;
?>
<template id="store_dummy">
	<div class="product_container box">
		<span class="store_availability"></span>
		<div class="contents max-60">
			<h3 class="store_header"></h3>
			<p class="store_description"></p>
		</div>
		<div class="card">
			<img alt="Some image">
			<p class="store_price"><?php print $t->get_translation("pris") ?></p>
		</div>
		<div class="bottom">
			<button class="purchase_button store_button"><?php print $t->get_translation("kjop"); ?></button>
			<button class="purchase_button btn_disabled wait"><?php print $t->get_translation("opens"); ?> <span class="store_opensin"></span></button>
			<button class="purchase_button btn_disabled soldout"><?php print $t->get_translation("soldout");  ?></button>
			<button class="purchase_button btn_disabled timeout"><?php print $t->get_translation("timeout"); ?></button>
			<div class="store_countdown" style="display: none">
				<?php print $t->get_translation("closes_in"); ?>
				<span class="store_timeleft"></span>
			</div>
		</div>
	</div>
</template>

<div id="checkout_overlay" class="store_modal_background">
	<div class="modal_content box">
		<div class="box">
			<div class="contents max-60">
				<h2><?php print $t->get_translation("overlay_header", "store"); ?></h2>
				<p id="checkout_description"></p>
			</div>
			<div class="card">
				<img id="checkout_img" alt="Image">
				<p id="checkout_title"></p>
				<p id="checkout_price"></p>
			</div>
		</div>
		<div class="modal_content box">
			<h2><?php print $t->get_translation("overlay_checkout", "store"); ?></h2>
			<form id="payment-form">
				<input id="product_hash" name="product_hash" type="hidden" />
				<label for="name"><?php print $t->get_translation("navn", "store") ?></label>

				<input id="checkout_name" id="checkout_name" name="name" type="text" required />
				<label for="email"><?php print $t->get_translation("epost", "store"); ?></label>

				<input id="checkout_email" name="email" type="email" required />
				<label for="phone"><?php print $t->get_translation("telefon", "store"); ?></label>

				<input id="checkout_phone" name="phone" type="number" />
				<label for="comment"><?php print $t->get_translation("kommentar", "store"); ?></label>

				<textarea id="checkout_comment" name="comment"></textarea>

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
				<button class="locked" style="float: left; width: 45%"><?php print $t->get_translation("cancel", "store"); ?></button>
				<button type="submit" style="float: right; width: 45%"><?php print $t->get_translation("kjop", "store"); ?></button>
			</form>
		</div>
		<span class="close">&times;</span>
	</div>
</div>
<link rel="stylesheet" href="<?php global $settings; print($settings['baseurl']); ?>/css/store.css" type="text/css" />
<link rel="stylesheet" href="<?php global $settings; print($settings['baseurl']); ?>/css/modal.css" type="text/css" />
<script type="module" src="<?php global $settings; print($settings['baseurl']) ?>/js/modules/store.js"></script>