<?php
function equipment_entry($title) {
	global $base_url, $t ?>
	<div class="box">
		<h1><?php print $t->get_translation("${title}_header"); ?></h1>
		<p><?php print $t->get_translation("${title}_content"); ?></p>
	</div>
<?php
}
?>
<div class="box green">
	<h1><?php print $t->get_translation("mainHeader"); ?></h1>
	<p><?php print $t->get_translation("subHeader"); ?></p>
	<p><?php print $t->get_translation("links"); ?></p>
	<p><?php print $t->get_translation("contact"); ?></p>
</div>
<?php 
equipment_entry("briller"); 
equipment_entry("badebukse");
equipment_entry("badehette");
equipment_entry("plate");
equipment_entry("paddles");
equipment_entry("vannflaske");
equipment_entry("fotter");
equipment_entry("snorkel");
equipment_entry("pullbuoy");


