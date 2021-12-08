<?php
include_once("library/templates/content.php");
global $t;
print_content_header(
	$t->get_translation("mainHeader"),
	$t->get_translation("subHeader")
);
?>


<div class="box">
	
	<div class="slideshow-container">
		<?php
		//Make image containers
		$image_count = 9;
		for ($i = 1; $i <= $image_count; $i++) { ?>
			<div class="mySlides">
				<img src="<?php print $settings["baseurl"] . "/img/slideshow/bilde_$i.jpg"; ?>">
				<div class="slideshow-caption"><?php print $t->get_translation("caption_$i"); ?></div>
			</div>
			<?php		}
		?>
		<a class="prev" onclick="plusSlides(-1)">&#10094;</a>
		<a class="next" onclick="plusSlides(1)">&#10095;</a>
	</div>
	
	<p><?php print $t->get_translation("group_description"); ?></p>
	
</div>

<?php 
style_and_script(__FILE__);
?>