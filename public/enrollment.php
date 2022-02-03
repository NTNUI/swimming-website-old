<?php
require_once("library/templates/content.php");
require_once("library/templates/modal.php");
require_once("library/templates/store.php");
require_once("library/util/enrollment.php");
require_once("library/util/store.php");

$store = new Store($language);
$tabindex = 0;

function print_textBox($title, $type, $name, $extra = "")
{
	global $tabindex, $t;
	++$tabindex;
?>
	<div class="box">
		<label><?php print $t->get_translation("${title}_label"); ?></label>
		<?php
		print("<input type='${type}' name='${name}'");
		++$tabindex;
		print(" ${extra} " . " tabindex='${tabindex}' " . " />"); ?>
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
	<div class="box">
		<label><?php print $t->get_translation("${title}_label") . " $extra"; ?></label>
		<?php
		++$tabindex;
		print("<select class='${title}' tabindex='${tabindex}' name='${name}'/>");
		print("<option value=''></option>");
		print("<option value='NTNUI Triatlon'>NTNUI Triatlon</option>");
		foreach ($options as $el) {
			print("<option value='$el'>$el</option>");
		}
		print("</select>"); ?>

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
	<div class="box">
		<label> <?php print $t->get_translation("${title}_label"); ?></label>
		<label class="radio_container"> <?php print $t->get_translation("${title}_opt1"); ?>
			<input type="radio" <?php print($extra . " tabindex='" . $tabindex . "' " . "name='" . "${input_name}" . "' " . "value= '" . "${opt1_value}" . "' ") ?>>
			<span class="check_mark"></span>
		</label>

		<?php ++$tabindex ?>
		<label class="radio_container"> <?php print $t->get_translation("${title}_opt2"); ?>
			<input type="radio" <?php print($extra . " tabindex='" . $tabindex . "' " . "name='" . "${input_name}" . "' " . "value= '" . "${opt2_value}" . "'") ?>>
			<span class="check_mark"></span>
		</label>

	</div>
<?php
}

function print_recaptcha()
{
	// print('<div class="enrollment box"><div style="display: inline-block;" class="g-recaptcha center" data-sitekey="6LdrnW8UAAAAAJa67cSTnwyho53uTJRJlwf9_B9W"></div></div>');
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
print_content_header(
	$t->get_translation("mainHeader"),
	$t->get_translation("mainBody")
);

// Start Content
if (!enrollment_is_active()) {
	print_content_block(
		$t->get_translation("registration_closed_header"),
		$t->get_translation("registration_closed_content"),
		"",
		""
	);
	return;
}
?>

<form id="enrollment_form" autocomplete="on">
	<?php
	print_textBox("name", "text", "name", "required, id='input_name'");
	print_radio("gender", "gender", "Male", "Female", "required");
	print_textBox("phone", "tel", "phone", "required");
	print_textBox("birthDate", "date", "birthDate", "required pattern='([12]\d|0?\d|3[01])-(1[0-2]|0?\d)-\d{4}'");
	print_textBox("zip", "number", "zip", "required min='1000' max='9999' ");
	print_textBox("address", "text", "address", "required");
	print_textBox("email", "email", "email", "required");
	$path = "assets/clubs.json";
	print_selectBox("licensee", "Licensee", json_decode(file_get_contents($path)), "<a style='text-decoration: none;'href='FAQ'><span class='emoji'>❓</span></a>");
	if ($settings["baseurl"] == "https://org.ntnu.no/svommer") {
		print_recaptcha();
	}
	print "<div class='box'><p>" . $t->get_translation("gdpr_notice") . "</p></div>";
	print('<input type="submit" disabled value="' . $t->get_translation("submit") . '">');
	?>
</form>
</div>

<script defer type="text/javascript">
	let license_product;
	addLoadEvent(async () => {
		license_product = await fetch(BASEURL + "/api/store?request_type=get_product&product_hash=" + "<?php global $settings; print $settings["license_product_hash"]; ?>").then(response => response.json());
		license_product.image = BASEURL + "/img/store/" + license_product.image;
	});
</script>
<!-- <script defer type="text/javascript" src="https://www.google.com/recaptcha/api.js"></script> -->
<script type='module' src='<?php print $settings["baseurl"];?>/js/enrollment.js'></script>
<?php 
// style_and_script(__FILE__);
?>