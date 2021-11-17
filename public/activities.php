<?php
function activity_entry($title, $image) {
	global $base_url, $t; ?>
	<div class="board box">
		<div>
			<h1><?php print $t->get_translation("${title}_header"); ?></h1>
			<p><?php print $t->get_translation("${title}_description"); ?></p>
		</div>
		
		<div class="card" style="width: 40%">
			<img src="<?php print "$base_url/img/activities/$image"?>" alt="<?php print $title ?>">
			<h4><?php print $t->get_translation("${title}_caption"); ?></h4>
		</div>
	</div>
<?php
}
?>

<div class="box">

<h1 class="center"><?php print $t->get_translation("mainHeader"); ?></h1>
  <p class="center">
    <?php print $t->get_translation("subHeader"); ?>
  </p>
</div>

<?php
activity_entry("stevner", "husebybadet_stevne.jpg");
activity_entry("koietur", "heinfjordstua_summer.jpg");
activity_entry("SL", "SLtrondheim.jpg");
activity_entry("sprinten", "NTNUI_sprinten.jpg");
activity_entry("leir", "treningsleir_2018.jpg");
activity_entry("fredagspils", "fredagspils.jpg");

