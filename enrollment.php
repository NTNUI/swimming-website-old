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
$registatin_open = true;//false;
$tabindex = 0;

function print_textBox($title, $type, $name, $extra = "") {
	// EXAMPLE: print_textBox("firstName", "text", "fornavn", "required");
	global $base_url, $t;
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
function print_radio($title, $input_name, $opt1_value, $opt2_value, $required = ""){
	//example : print_radio("Kjønn","sex", "Mann", "Kvinne", "Male", "Female", "required")
global $base_url, $t;
++$tabindex;
?>
<div class="enrollment">
	<label> <?php print $t->get_translation("${title}_label"); ?></label>
	<label class="container"> <?php print $t->get_translation("${title}_opt1"); ?>
	<input type="radio" <?php print($required . " tabindex=' " . $tabindex . " ' " . "name='" . "${input_name}" . "' ". "value= '" . "${opt1_value}" . "' ") ?>>
		<span class="checkmark"></span>
	</label>

<?php ++$tabindex ?>
	<label class="container"> <?php print $t->get_translation("${title}_opt2"); ?>
	<input type="radio" <?php print($required . "tabindex=' " . $tabindex . " ' " . "name='" . "${input_name}" . "' ". "value= '" . "${opt2_value}" . "'") ?>>
		<span class="checkmark"></span>
	</label>

</div>
<?php
}

 function print_checkBox($title, $input_name, $required = ""){
 	//example : print_checkBox("email", "nyhetsbrev", "Ja, jeg ønsker nyhetsbrev")
 global $base_url, $t;
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

  function print_textArea(){
    global $base_url, $t;
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
function print_recaptia(){
	print('<div class="enrollment" style=" text-align: center;"><div style="display: inline-block;" class="g-recaptcha center" data-sitekey="6LdrnW8UAAAAAJa67cSTnwyho53uTJRJlwf9_B9W"></div></div>');
}

function print_reset_and_submit_buttons(){
  global $t;
	print('<input name="utfylt" type="submit" value="' . $t->get_translation("submit") . '" style="float: right;">');
	print('<input type="reset" value="' . $t->get_translation("clear") . '">');
}
function print_infoBox($key = ""){
	global $t;
	print("<small>" . $t->get_translation($key) . "</small>");
}

		?>

<?php
	// Functions END //

	// Web page contetn below: //
 ?>

<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/14.0.6/css/intlTelInput.css"/>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/14.0.6/js/intlTelInput.js"></script>

<div class="box green">
  <h1 class="center">
    <?php print $t->get_translation("mainHeader"); ?>
  </h1>
  <p><?php print $t->get_translation("mainBody"); ?></p>
</div>

<?php

// Start Content

if($registatin_open){	// check if registration is open
	// Registration from is open
?>

	<form action="enrollment_reception" method="post">

<?php
	// input fields
	print_textBox("firstName", "text", "fornavn", "required");
	print_textBox("lastName", "text", "etternavn", "required");
  print_radio("gender","gender", "Male", "Female", "required");
	print_textBox("phoneNumber", "tel", "phoneNumber", "required");
	print_textBox("birthDate", "date", "fodselsdato", "required pattern='([12]\d|0?\d|3[01])-(1[0-2]|0?\d)-\d{4}'");
	print_infoBox("birthDate_not_supported");
	print_textBox("zip", "nuber", "zip", "required min='1000' max='9999' ");
	print_textBox("adress", "text", "adresse", "required");
	print_textBox("email", "email", "email", "required");
	// print_checkBox("newsletter", "nyhetsbrev");
        print_textBox("NTNUImemberNumber", "number", "kortnummer", "required");
	print_checkBox("canSwim", "dyktig", "required");
	print_checkBox("acceptVoulentaryWork", "dugnad", "required");
	//print_textBox("NSFlisens", "date", "lisensdato", "required value ='null' pattern='([12]\d|0?\d|3[01])-(1[0-2]|0?\d)-\d{4}'");
  // print_checkBox("hasLicence", "harLisens");
  	print_textBox("oldClub", "text", "gammelKlubb");
	print_textArea();
	print_recaptia();
	print "<div class='box'>" . $t->get_translation("gdpr_notice") . "</div>";
	print_reset_and_submit_buttons();
	?>

	</form>
  <?php
}else{ // Registration is closed (usually during christmas times due to accountant issues)
	?>
  <div class="box">
    <h1>Medlemsregistrering er midlertidig stengt</h1>
    <p>
      Medlemsregistrering er dessverre midlertidig stengt. Følg med på <a href="https://www.facebook.com/NTNUISvomming" target="_blank">facebooksiden</a> vår. Vi annonserer der når registreringen åpner igjen.
    </p>
  </div>

  <div class="box">
	   <h1>Member registratrion is temporarily closed</h1>
     <p>
       Member registration is temporarily closed. Follow us on our <a href="https://www.facebook.com/NTNUISvomming" target="_blank">facebook page</a>. We'll announce when the registration opens up again. Stay tuned!
     </p>
  </div>
	<?php
}?>

<script type="text/javascript">
  var input = document.querySelector("input[name=phoneNumber]");
  var itl = window.intlTelInput(input, {
	initialCountry: "no",
	separateDialCode: true,
	utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/14.0.6/js/utils.js" });
</script>
