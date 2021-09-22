<?php

/* DESCRIPTION OF FILE:

This file is used for new member registration.

This file is supposed to:

1. Display fields for registration
2. Client-side validate the inputfields
3. Pass values on to "enrollment_reception.php" for server-side input validation

*/

?>

<script src='https://www.google.com/recaptcha/api.js'></script>

<?php
/* Local settings */
$manual_close = false;
$close_date = $settings["registration_close"];
$close_date = strtotime("${close_date['date']} ${close_date['month']} this year");
$registatin_open = $close_date - strtotime("now") > 0 and !$manual_close;
$tabindex = 0;

function print_textBox($title, $type, $name, $extra = "")
{
	global $tabindex, $t;
	++$tabindex;
?>
	<div class="enrollment">
		<div>
			<label><?php print $t->get_translation("name_label"); ?></label>
			<input name="name" required type="text" placeholder='<?php $t->get_translation("name_placeHolder") ?>' />
		</div>
	</div>
<?php
}
?>

<?php
function print_radio($title, $input_name, $opt1_value, $opt2_value, $required = "")
{
	//example : print_radio("Kjønn","sex", "Mann", "Kvinne", "Male", "Female", "required")
	global $tabindex, $t;
	++$tabindex;
?>
	<div class="enrollment">
		<label> <?php print $t->get_translation("gender_label"); ?></label>

		<label class="container"> <?php print $t->get_translation("gender_opt1"); ?>
			<input name="gender" type="radio" required value="male" />
			<span class="checkmark"></span>
		</label>

		<label class="container"> <?php print $t->get_translation("gender_opt2"); ?>
			<input name="gender" type="radio" required value="female" />
			<span class="checkmark"></span>
		</label>

	<!-- Phone number -->
	<div class="enrollment">
		<div>
			<label><?php print $t->get_translation("phoneNumber_label"); ?></label>
			<input name="phoneNumber" required type="tel" placeholder='<?php $t->get_translation("phoneNumber_placeHolder") ?>' />
		</div>
	</div>
<?php
}

function print_checkBox($title, $input_name, $required = "")
{
	global $tabindex, $t;
	++$tabindex;
?>
	<div class="enrollment">
		<div>
			<label><?php print $t->get_translation("birthDate_label"); ?></label>
			<input name="birthDate" required pattern="([12]\d|0?\d|3[01])-(1[0-2]|0?\d)-\d{4}" type="text" placeholder='<?php $t->get_translation("birthDate_placeHolder") ?>' />
		</div>
	</div>

	<!-- Zip code -->
	<div class="enrollment">
		<div>
			<label><?php print $t->get_translation("zip_label"); ?></label>
			<input name="zip" required type="number" min="1000" max="9999" placeholder='<?php $t->get_translation("zip_placeHolder") ?>' />
		</div>
	</div>

	<!-- Address -->
	<div class="enrollment">
		<div>
			<label><?php print $t->get_translation("address_label"); ?></label>
			<input name="address" required type="text" placeholder='<?php $t->get_translation("address_placeHolder") ?>' />
		</div>
	</div>

	<!-- Email address -->
	<div class="enrollment">
		<div>
			<label><?php print $t->get_translation("email_label"); ?></label>
			<input name="email" required type="email" placeholder='<?php $t->get_translation("email_placeHolder") ?>' />
		</div>
	</div>

	<!-- NTNUI membership number -->
	<div class="enrollment">
		<div>
			<label><?php print $t->get_translation("memberNumber_label"); ?></label>
			<input name="memberNumber" required min="100000" max="999999" type="number" placeholder='<?php $t->get_translation("memberNumber_placeHolder") ?>' />
		</div>
	</div>

	<!-- Membership in other clubs -->
	<div class="enrollment">
		<div>
			<label><?php print $t->get_translation("memberNumber_label"); ?></label>
			<input name="existingLicence" required min="100000" max="999999" type="number" placeholder='<?php $t->get_translation("memberNumber_placeHolder") ?>' />
		</div>
	</div>

	<!-- Terms and services -->
	<div class="enrollment">
		<label> <?php print($t->get_translation("terms_label")) ?> </label>
		<label class="container"> <?php print($t->get_translation("terms_answer")) ?>
			<input name="terms_input" type="checkbox" value="Yes" />
			<span class="checkmark_box tickkmark"></span>
		</label>
	</div>
<?php
}

function print_textArea()
{
	global $tabindex, $t;
	++$tabindex;
	$title = "comments";
?>
	<div class="enrollment">
		<label>
			<?php print($t->get_translation("{$title}_header")) ?>
		</label>

		<label>
			<?php print($t->get_translation("{$title}_content")) ?>
		</label>
		<textarea type="textarea" name="beskjed" rows="5" cols="95" style="width: 100%; border: 1px solid #ccc; padding: 14px 20px; margin: 8px 0; border-radius: 4px;"></textarea>
	</div>

<?php
}
?>
<?php
function print_recaptia()
{
	print('<div class="enrollment" style=" text-align: center;"><div style="display: inline-block;" class="g-recaptcha center" data-sitekey="6LdrnW8UAAAAAJa67cSTnwyho53uTJRJlwf9_B9W"></div></div>');
}

function print_reset_and_submit_buttons()
{
	global $t;
	print('<input name="utfylt" type="submit" value="' . $t->get_translation("submit") . '" style="float: right;">');
	print('<input type="reset" value="' . $t->get_translation("clear") . '">');
}
function print_infoBox($key = "")
{
	global $t;
	print("<small>" . $t->get_translation($key) . "</small>");
}

?>

<?php
// Functions END //

// Web page content below: //
?>

<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/14.0.6/css/intlTelInput.css" />
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/14.0.6/js/intlTelInput.js"></script>

	<!-- Submit -->
	<input name="submit" style="float: right;" type="submit" value="<?php $t->get_translation("submit") ?> " />
	<input type="reset" value="<?php $t->get_translation("clear"); ?>" />

<?php

// Start Content

if (!$registatin_open) {?>
	<div class="box">
			<h1><?php print $t->get_translation("registration_closed_header"); ?></h1>
			<p><?php print $t->get_translation("registration_closed_content"); ?></p>
		</div>
	<?php
	return;
}
?>
	
	<form action="enrollment_reception" method="post">
		<?php
		print_textBox("firstName", "text", "fornavn", "required");
		print_textBox("lastName", "text", "etternavn", "required");
		print_radio("gender", "gender", "Male", "Female", "required");
		print_textBox("phoneNumber", "tel", "phoneNumber", "required");
		print_textBox("birthDate", "date", "fodselsdato", "required pattern='([12]\d|0?\d|3[01])-(1[0-2]|0?\d)-\d{4}'");
		print_infoBox("birthDate_not_supported");
		print_textBox("zip", "nuber", "zip", "required min='1000' max='9999' ");
		print_textBox("adress", "text", "adresse", "required");
		print_textBox("email", "email", "email", "required");
		print_textBox("NTNUImemberNumber", "number", "kortnummer", "required");
		print_checkBox("canSwim", "dyktig", "required");
		print_checkBox("acceptVoulentaryWork", "dugnad", "required");
		print_textBox("oldClub", "text", "gammelKlubb");
		print_textArea();
		print_recaptia();
		print "<div class='box'>" . $t->get_translation("gdpr_notice") . "</div>";
		print_reset_and_submit_buttons();
		?>

	</form>
<script type="text/javascript">
	var input = document.querySelector("input[name=phoneNumber]");
	var itl = window.intlTelInput(input, {
		initialCountry: "no",
		separateDialCode: true,
		utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/14.0.6/js/utils.js"
	});
</script>