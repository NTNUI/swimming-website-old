<?php
declare(strict_types=1);

require_once("Library/Templates/Content.php");
global $t;
print_content_header(
	$t->get_translation("mainHeader"),
	$t->get_translation("subHeader")
);
?>

<div class="box">
	<div class="slideshow-container">
		<?php
		// Make image containers
		$files = glob('img/slideshow/*.jpg');
		foreach ($files as $file) {
		?>
			<div class="mySlides">
				<img src="<?php print Settings::get_instance()->get_baseurl() . "/" . $file; ?>">
				<div class="slideshow-caption"><?php print $t->get_translation("caption_" . basename($file)); ?></div>
			</div>
		<?php
		}
		?>
		<a class="prev" onclick="plusSlides(-1)">&#10094;</a>
		<a class="next" onclick="plusSlides(1)">&#10095;</a>
	</div>
	<p><?php print $t->get_translation("group_description"); ?></p>
</div>

<?php
style_and_script(__FILE__);
?>