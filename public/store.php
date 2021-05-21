<div class="box">
	<h2>
		<?php print $t->get_translation("header"); ?>
	</h2>
</div>


<?php
//$t->page = "store";
if (isset($_REQUEST["error"])) { ?>
	<div class="error box">
		<h1><?php print $t->get_translation("error_header"); ?></h1>
		<p><strong><?php print $_REQUEST["error"]; ?></strong><br>
			<a href="mailto:svomming-styret@ntnui.no"><?php print $t->get_translation("contact"); ?></a>
		</p>
	</div>
	<script type="text/javascript">
		alert("<?php print $t->get_translation("error_header") ?> Stripe: <?php print $_REQUEST["error"]; ?>");
	</script>
<?php } ?>
<div id="store_container"></div>
<div id="loading_box" class="modal">
	<div>
		<h1 id="waitingHeader" class="hidden"><?php print $t->get_translation("waiting_message"); ?></h1>
		<h1 id="completedHeader" class="t hidden"><?php print $t->get_translation("payment_complete"); ?></h1>
		<h1 id="failedHeader" class="hidden t">error found:<span id="failedContent"></span></h1>
		<div id="waitingGlass" class="lds-hourglass hidden"></div>
		<span id="paymentComplete" class="hidden">&#x2713;
	</div>
	<span id="failedCross" class="hidden">&times;</span>
	<span class="close" onclick="hide_load()">&times;</span>
</div>
</div>
<div id="checkout_overlay" class="modal">
	<div>
		<div style="width:100%;">
			<h2><?php print $t->get_translation("overlay_header"); ?></h2>
			- <b id="checkout_title"></b><br>
			<img id="checkout_img" alt="Image">
			<p id="checkout_description"></p>
		</div>
		<div style="width: 100%; float:left;">
			<h1><?php print $t->get_translation("overlay_checkout"); ?></h1>
			<form id="payment-form" action="<?php print $t->get_url("api/order") ?>" method="POST">
				<input id="checkout_id" name="id" type="hidden" />
				<label for="navn"><?php print $t->get_translation("navn") ?></label>
				<input id="checkout_name" name="navn" type="text" placeholder="Ola Nordmann" required />
				<label for="epost"><?php print $t->get_translation("epost"); ?></label>
				<input name="epost" type="email" placeholder="me@example.com" required />
				<label for="phone"><?php print $t->get_translation("telefon"); ?></label>
				<input name="phone" type="number" placeholder="12345678" />
				<label for="kommentar"><?php print $t->get_translation("kommentar"); ?></label>
				<textarea name="kommentar"></textarea>

				<div class="form-row">
					<label for="card-element">
						<?php print $t->get_translation("pay_with_card_label"); ?>
					</label>
					<div id="card-element">
						<!-- A Stripe Element will be inserted here. -->
					</div>

					<!-- Used to display form errors. -->
					<div id="card-errors" role="alert"></div>
				</div>
				<button type="submit" style="float: left; width: 45%"><?php print $t->get_translation("kjop"); ?></button>
				<button onclick="hide_store(event)" class="locked" style="float: right; width: 45%"><?php print $t->get_translation("cancel"); ?></button>
			</form>
		</div>
		<span class="close" onclick="hide_store()">&times;</span>
	</div>
</div>
<template id="store_dummy">
	<div class="store_item">
		<h1 class="store_header"></h1>
		<div class="card">
			<img alt="Some image">
			<?php print $t->get_translation("pris") ?>: <b class="store_price"></b>
		</div>
		<div>
			<p class="store_description"></p>
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
		<span class="store_availability"></span>
	</div>
</template>

<script type="text/javascript" src="<?php print "$base_url/js/store.js" ?>"></script>
<script type="text/javascript">
	var url = "<?php print "$base_url/api/storelist?lang=$language" ?>";
	var orderUrl = "<?php print "$base_url/api/order" ?>";
	var returnUrl = "<?php print "$base_url/checkout" ?>";
	//Offset from server time
	//Such that the clients can show the right countdown times
	var serverOffset = new Date().getTime() - <?php print time() ?> * 1000;
	var lang = "<?php print $language ?>";
	//Hent ting fra server
	<?php if (isset($_REQUEST["item_id"])) {
		include_once("library/util/store_helper.php");
		$store = new StoreHelper($language);
		$item = $store->get_item($_REQUEST["item_id"]);
		if ($item === false) {
			print "alert('Item id not found');";
			print "window.location.href = 'store';";
		} else {
			$item["id"] = $item["api_id"];
			$item["image"] = "$base_url/img/store/" . $item["image"];
			print "appendItem(" . json_encode($item) . ")";
		}
	} else {
		print "getItems();";
	} ?>
</script>