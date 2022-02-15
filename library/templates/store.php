<?php
global $t;
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.15/css/intlTelInput.css" integrity="sha512-gxWow8Mo6q6pLa1XH/CcH8JyiSDEtiwJV78E+D+QP0EVasFs8wKXq16G8CLD4CJ2SnonHr4Lm/yY2fSI2+cbmw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.15/js/intlTelInput.min.js" integrity="sha512-L3moecKIMM1UtlzYZdiGlge2+bugLObEFLOFscaltlJ82y0my6mTUugiz6fQiSc5MaS7Ce0idFJzabEAl43XHg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script defer type="text/javascript" src="https://js.stripe.com/v3/"></script>

<template id="store_dummy">
	<div class="product_container box">
		<span class="store_availability"></span>
		<div class="contents max-60">
			<h3 class="store_header"></h3>
			<p class="store_description"></p>
		</div>
		<div class="card">
			<img alt="Some image">
			<p style="display: block;">
				<label><?php print($t->get_translation("pris", "store") . ": "); ?></label><label class="store_price"></label>
			</p>
			<p style="display: block;" class="store_price_member_label">
				<label><?php print($t->get_translation("price_member", "store") . ": "); ?></label><label class="store_price_member"></label>
			</p>
		</div>
		<div class="bottom">
			<button class="purchase_button store_button"><?php print $t->get_translation("kjop", "store"); ?></button>
			<button class="purchase_button btn_disabled wait"><?php print $t->get_translation("opens", "store"); ?> <span class="store_opensin"></span></button>
			<button class="purchase_button btn_disabled soldout"><?php print $t->get_translation("soldout", "store");  ?></button>
			<button class="purchase_button btn_disabled timeout"><?php print $t->get_translation("timeout", "store"); ?></button>
			<div class="store_countdown" style="display: none">
				<?php print $t->get_translation("closes_in", "store"); ?>
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

				<p>
					<label><?php print($t->get_translation("pris", "store") . ": "); ?></label>
					<label id="checkout_price"></label>
				</p>
				<p id="checkout_price_member_label">
					<label><?php print($t->get_translation("price_member", "store") . ": "); ?></label>
					<label id="checkout_price_member"></label>
				</p>

			</div>
		</div>
		<div class="modal_content box">
			<h2><?php print $t->get_translation("overlay_checkout", "store"); ?></h2>
			<form id="payment-form">
				<input id="product_hash" name="product_hash" type="hidden" />

				<label for="name"><?php print $t->get_translation("navn", "store") ?></label>
				<input id="checkout_name" id="checkout_name" name="name" type="text" required />

				<label for="email"><?php print $t->get_translation("epost", "store"); ?></label>
				<input id="checkout_email" name="email" type="email" />

				<label for="phone"><?php print $t->get_translation("telefon", "store"); ?></label>
				<input id="checkout_phone" name="phone" type="tel" />

				<label id="checkout_comment_label" for="checkout_comment"><?php print($t->get_translation("comment", "store")); ?></label>
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
				<button type="button" class="locked" style="float: left; width: 45%"><?php print $t->get_translation("cancel", "store"); ?></button>
				<button type="submit" style="float: right; width: 45%"><?php print $t->get_translation("kjop", "store"); ?></button>
			</form>
		</div>
		<span class="close">&times;</span>
	</div>
</div>
<link rel="stylesheet" href="<?php global $settings; print($settings['baseurl']); ?>/css/store.css" type="text/css" />
<link rel="stylesheet" href="<?php global $settings; print($settings['baseurl']); ?>/css/modal.css" type="text/css" />
<script type="module" src="<?php global $settings; print($settings['baseurl']) ?>/js/modules/store.js"></script>