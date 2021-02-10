<div class="box green">

<h1><?php print $t->get_translation("mainHeader"); ?></h1>
	<p><?php print $t->get_translation("subHeader"); ?></p>
</div>

<?php
function question_box($question, $extra = "") {
	global $t;
?>
	<div class="box">
		<a name="<?php print $question?>"></a>
		<h1><?php print $t->get_translation("${question}_question"); ?></h1>
		<?php print $extra; ?>
		<p><?php print $t->get_translation("${question}_answer"); ?></p>
	</div>
<?php
}

$map = '<div class="map">
	<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1783.9680953516508!2d10.400505700000002!3d63.4402936!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x466d3176369d456f%3A0xfb599e668a0d2092!2sPirbadet!5e0!3m2!1sno!2sno!4v1535030780364" width=100% height="400px" frameborder="0" style="border:0" allowfullscreen>
	</iframe>
</div> ';
question_box("join");
question_box("cost");
question_box("paymentinfo");
question_box("trial");
question_box("level");
question_box("mandatory");
question_box("where", $map);
question_box("paid_license");
question_box("other_club");
question_box("outside_training_hours");
question_box("email");
question_box("equipment");
?>
