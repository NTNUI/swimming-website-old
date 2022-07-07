<?php
declare(strict_types=1);

require_once("Library/Templates/Store.php");
require_once("Library/Templates/Content.php");
require_once("Library/Templates/Modal.php");
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

<script type="text/javascript">
	const REQUESTED_PRODUCT_HASH = "<?php isset($_REQUEST["product_hash"]) ? print $_REQUEST['product_hash'] : ""; ?>";
</script>
<script type="module" src="<?php print $settings['baseurl']?>/js/store.js"></script>
<link rel="stylesheet" href="<?php print $settings["baseurl"]?>/css/store.css" class="css">

<?php
// style_and_script(__FILE__);
?>