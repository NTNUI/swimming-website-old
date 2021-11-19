<?php
include_once("library/templates/store.php");
?>
<div class="box">
	<h1>
		<?php print $t->get_translation("header"); ?>
	</h1>
</div>

<div class="box hidden" id="storeEmpty">
	<h2>
		<?php print $t->get_translation("store_empty_header"); ?>
	</h2>
	<p>
		<?php print $t->get_translation("store_empty_content"); ?>
	</p>
</div>

<div id="store_container"></div>

<script type="text/javascript" src="<?php print "$base_url/js/store.js" ?>"></script>

<script type="text/javascript">
	
	//Offset from server time
	//Such that the clients can show the right countdown times
	var serverOffset = new Date().getTime() - <?php print time() ?> * 1000;
	var lang = "<?php print $language ?>";
	//Hent ting fra server
	<?php
	if (isset($_REQUEST["item_id"])) {
		include_once("library/util/store_helper.php");
		$store = new StoreHelper($language);
		$item = $store->get_item($_REQUEST["item_id"]);
		if ($item === false) {
			print "alert('Item id not found');";
			print "window.location.href = 'store';";
		} else {
			$item["id"] = $item["item_hash"];
			$item["image"] = "$base_url/img/store/" . $item["image"];
			print "appendItem(" . json_encode($item) . ")";
		}
	} else {
	?>
		if (getItems()) {
			document.getElementById("storeEmpty").classList.remove("hidden");
		};
	<?php
	}
	?>
</script>