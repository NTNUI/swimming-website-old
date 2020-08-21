<?php

// Libs
include_once("library/util/store_helper.php");

$store = new StoreHelper($language);


//Input data
$source = $_REQUEST['source'];
$charge = $_REQUEST['charge'];
$client_secret = $_REQUEST["client_secret"];
?>
<div id="source_pending" class="box source">
	<h1>Vennligst vent mens vi behandler din betaling</h1>
</div>
<div id="source_chargeable" class="source">
	<div id="charge_pending" class="box charge">
		<h1>Din ordre er motatt, vennligst vent mens vi behandler betalingen</h1>
	</div>
	<div id="charge_succeeded">
		<div class="green box charge">
			<h1>Ditt kjøp ble gjennomført, en bekreftelse vil snart bli sendt via epost</h1>

		</div>
		<?php
		include_once("welcome.php"); // Temporary solution
		 ?>
	</div>
	<div id="charge_failed" class="error box charge">
		<h1>Betalingen din har feilet</h1>
	</div>
</div>
<div id="source_failed" class="box error source">
	<h1>Noe gikk galt under behandligen av betalingen</h1>
</div>

<script type="text/javascript" src="<?php print "$base_url/js/checkout.js"?>"></script>
<script type="text/javascript">
var status_url = "<?php print "$base_url/api/order_status" ?>";
var SOURCE_ID = "<?php print $source ?>";
var CLIENT_SECRET = "<?php print $client_secret ?>";

<?php
if ($charge == "") {
	print "pollForSourceStatus();";
} else {
	print "pollForOrderStatus();";
}?>
</script>
