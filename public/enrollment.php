<?php

/* DESCRIPTION OF FILE:

This file is used for new member registration.

This file is supposed to:

1. Display fields for registration
2. Client-side validate the input fields
3. Pass values on to "enrollment_reception.php" for server-side input validation

*/
include_once("library/templates/store.php");
include_once("library/util/store_helper.php");
include_once("library/templates/modal.php");
$store = new StoreHelper($language);
?>

<script src='https://www.google.com/recaptcha/api.js'></script>

<?php
$tabindex = 0;

function print_textBox($title, $type, $name, $extra = "")
{
	global $tabindex, $t;
	++$tabindex;
?>
	<div class="enrollment">
		<div>
			<label><?php print $t->get_translation("${title}_label"); ?></label>
			<?php
			print("<input type='${type}' name='${name}' placeholder='" . $t->get_translation("${title}_placeHolder") . "'");
			++$tabindex;
			print(" ${extra} " . " tabindex='${tabindex}' " . " />"); ?>
		</div>
	</div>
<?php
}
?>

<?php
function print_selectBox(string $title, string $name, array $options, string $extra = "")
{
	global $tabindex, $t;
	++$tabindex;
?>
	<div class="enrollment">
		<div>
			<label><?php print $t->get_translation("${title}_label") . " $extra"; ?></label>
			<?php
			++$tabindex;
			print("<select  tabindex='${tabindex}' name='${name}' placeholder='" . $t->get_translation("${title}_placeHolder") . "'<select>");
			print("<option value=''></option>");
			print("<option value='NTNUI Triatlon'>NTNUI Triatlon</option>");
			foreach ($options as $el) {
				print("<option value='$el'>$el</option>");
			}
			print("</select>"); ?>
		</div>
	</div>
<?php
}
?>

<?php
function print_radio(string $title, string $input_name, string $opt1_value, string $opt2_value, string $extra = "")
{
	global $tabindex, $t;
	++$tabindex;
?>
	<div class="enrollment">
		<label> <?php print $t->get_translation("${title}_label"); ?>;</label>
		<label class="container"> <?php print $t->get_translation("${title}_opt1"); ?>
			<input type="radio" <?php print($extra . " tabindex='" . $tabindex . "' " . "name='" . "${input_name}" . "' " . "value= '" . "${opt1_value}" . "' ") ?>>
			<span class="checkmark"></span>
		</label>

		<?php ++$tabindex ?>
		<label class="container"> <?php print $t->get_translation("${title}_opt2"); ?>
			<input type="radio" <?php print($extra . " tabindex='" . $tabindex . "' " . "name='" . "${input_name}" . "' " . "value= '" . "${opt2_value}" . "'") ?>>
			<span class="checkmark"></span>
		</label>

	</div>
<?php
}

function print_checkBox($title, $input_name, $required = "")
{
	global $tabindex, $t;
	++$tabindex;
?>
	<div class="enrollment">
		<label> <?php print($t->get_translation("{$title}_label")) ?> </label>
		<label class="container"> <?php print($t->get_translation("{$title}_answer")) ?>
			<input type="checkbox" value="Yes" <?php print($required . " tabindex=' " . $tabindex . " ' " . "name='" . $input_name . "' ") ?>>
			<span class="checkmark_box tickkmark"></span>
		</label>
	</div>
<?php
}

function print_recaptia()
{
	print('<div class="enrollment"><div style="display: inline-block;" class="g-recaptcha center" data-sitekey="6LdrnW8UAAAAAJa67cSTnwyho53uTJRJlwf9_B9W"></div></div>');
}

function print_reset_and_submit_buttons()
{
	global $t;
	print('<input type="submit" value="' . $t->get_translation("submit") . '">');
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


<div class="box">
	<h1>
		<?php print $t->get_translation("mainHeader"); ?>
	</h1>
	<p><?php print $t->get_translation("mainBody"); ?></p>
</div>
<?php

// Start Content
// TODO: make work
$manual_close = true;
$close_date = $settings["registration_close"];
$close_date = strtotime("${close_date['date']} ${close_date['month']} this year");
$registration_open = ($close_date - strtotime("now") > 0) and !$manual_close;
if (!$registration_open) { ?>
	<div class="box">
		<h1><?php print $t->get_translation("registration_closed_header"); ?></h1>
		<p><?php print $t->get_translation("registration_closed_content"); ?></p>
	</div>
<?php
	return;
}
?>

<form id="enrollment_form" autocomplete="on">
	<?php
	print_textBox("name", "text", "name", "required, id='input_name'");
	print_radio("gender", "gender", "Male", "Female", "required");
	print_textBox("phoneNumber", "tel", "phoneNumber", "required");
	print_textBox("birthDate", "date", "birthDate", "required pattern='([12]\d|0?\d|3[01])-(1[0-2]|0?\d)-\d{4}'");
	print_textBox("zip", "number", "zip", "required min='1000' max='9999' ");
	print_textBox("address", "text", "address", "required");
	print_textBox("email", "email", "email", "required");
	print_selectBox("licensee", "Licensee", json_decode(file_get_contents("assets/clubs.json")), "<a style='text-decoration: none;'href='FAQ'>&#10067;</a>");
	if ($settings["baseurl"] == "https://org.ntnu.no/svommer") {
		print_recaptia();
	}
	print "<div class='box'><p>" . $t->get_translation("gdpr_notice") . "</p></div>";
	print_reset_and_submit_buttons();
	?>
</form>
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/14.0.6/css/intlTelInput.css" />
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/14.0.6/js/intlTelInput.js"></script>
<script type="text/javascript" src="<?php print "$base_url/js/store.js" ?>"></script>
<script type="text/javascript">
	// Offset from server time, show correct countdown for users
	const serverOffset = new Date().getTime() - <?php print time() ?> * 1000;
	const lang = "<?php print $language ?>";

	<?php $item = $store->get_item($settings["license_store_item_id"]); ?>
	let license_store_item = JSON.parse('<?php print(json_encode($item)); ?>');
	license_store_item.image = BASEURL + "/img/store/" + license_store_item.image;
</script>
<script type="text/javascript" src="<?php print "$base_url/js/enrollment.js" ?>"></script>