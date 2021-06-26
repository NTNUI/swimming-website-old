<?php
function handle_error($ErrorMessage)
{ // not in use due to my poorly programming skillz #Pavel
	global $t;
	$message = $t->get_translation($ErrorMessage);
	echo "<script type='text/javascript'>alert('$message'); window.history.back(); </script>";
	print("<div class='box error'>$message</div>"); //Legacy noscript support
	return;
}

function isTri($string)
{
	if (strpos(strtolower($string), "triatlon") !== false) return true;
	if (strpos(strtolower($string), "ntf") !== false) return true;
	if (strpos(strtolower($string), "tri") !== false) return true;
	return 0;
}

// $licence_key = "NSFLicence2019";
$licence_key = $settings["defaults"]["licence_key"];

$t->load_translation("store");
include_once("library/util/store_helper.php");
$store = new StoreHelper($language);

$item = $store->get_item($licence_key);
$item["id"] = $item["api_id"];

// Get post data
$firstName 		= $_POST['fornavn'];
$lastName 		= $_POST['etternavn'];
$phoneNumber 	= $_POST['phoneNumber'];
$birthDate 		= $_POST['fodselsdato'];
$gender 		= $_POST['gender'];
$proficient 	= $_POST['dyktig'];
$voluntaryWork  = $_POST['dugnad'];
$zipCode 		= $_POST['zip'];
$adress 		= $_POST['adresse'];
$email 			= $_POST['email'];
$cardNumber		= $_POST['kortnummer'];
$comment 		= $_POST['beskjed'];
$filledOut 		= $_REQUEST['utfylt'];
$oldClub 		= $_POST['gammelKlubb'];
$triatlon 		= isTri($oldClub);

if ($voluntaryWork == "") {
	$dugnaf = FALSE;
}
if ($gender == "") {
	$gender = FALSE;
}

// Invalid dates
if (strtotime($birthDate) === false) {
	handle_error("error_fodselsdato");
	return;
}

// Convert dates to mysql supported format
$birthDate = date("Y-m-d", strtotime($birthDate));

if ($voluntaryWork !== "Yes") {
	handle_error("error_dugnad");
	return;
}

if (!ctype_digit($cardNumber)) {
	handle_error("cardnumber_not_number");
	return;
} elseif (strlen($cardNumber) > 9) {
	handle_error("cardnumber_too_long");
	return;
}

// ikke Svømmedyktig
if ($proficient !== "Yes") {
	handle_error("error_dyktig");
	return;
}

// Captia
$secret = $settings["captia_key"];

$token = $_POST['g-recaptcha-response'];
$url = "https://www.google.com/recaptcha/api/siteverify";
$url .= "?secret=$secret";
$url .= "&response=$token";

//Check captcha result with google
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$verify = curl_exec($ch);
$decoded = json_decode($verify);

if (!$decoded->success) {
	print("
	<div class='box green'>
		<h1>Recaptia feilet.</h1>
			<p>
				Prøv å fylle ut skjemaet på nytt.
				Hvis problemet vedvarer kontakt teknisk leder.
			</p>
	</div>
	");
	return;
}

if (!($firstName != "" && $_lastName != "" && $_birthDate != "" && $_gender != "" && $_proficient != ""
	&& $_voluntaryWork != "" && $_zipCode != "" && $_adress != "" && $_email != "" && $_cardNumber != "" && $_phoneNumber != "" && is_numeric($_cardNumber))) {
	//hvis noen fyller ut mennesketest men glemmer noen av de andre obligatoriske feltene
	handle_error("error_empty");
	return;
}

$conn = connect("medlem");

$query = $conn->prepare("SELECT epost FROM " . $settings["memberTable"] . " WHERE epost=?");
$query->bind_param("s", $_email);
$query->execute();
$_emailFound = $query->fetch();
//User found
if ($_emailFound) {

?>
	<div class='box'>
		<h1>Feil</h1>
		<p>
			<?php print $t->get_translation("error_already_in_database"); ?>
		</p>
	</div>

<?php
	$query->close();
	mysqli_close($conn);
	return;
} else { // email is not found in DB, user is getting registered


	$sql = "INSERT INTO " . $settings["memberTable"] . "(kjonn, fodselsdato, etternavn, fornavn, phoneNumber, adresse, epost,  kommentar ,kortnr, postnr, regdato, gammelKlubb, triatlon) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)";

	$append = $conn->prepare($sql);
	$append->bind_param("ssssssssiiss", $_gender, $_birthDate, $_lastName, $_firstName, $_phoneNumber, $_adress, $_email, $_comment, $_cardNumber, $_zipCode, $oldClub, $triatlon);
	if (!$append->execute()) {
		echo "Error: " . $sql . "<br>" . mysqli_error($conn);
		$append->close();
		$query->close();
		mysqli_close($conn);
		return;
	}

	$sendTo = $settings["emails"]["analyst"];
	$headers .= "Ny medlem registrert, logg på adminsiden for mer info";
	$from = $settings["emails"]["bot-general"];

	// Depends on the accountant, but many prefer not to get a mail for each new member
	if(false){
		// ADVARSEL: hvis Content-type: blir endret til text/html må variablene som går inn i $headers saniteres mot html injection #Pavel
		mail($sendTo, "NTNUI-Svømming: Nytt medlem", $headers, "From: $from\r\nContent-type: text/plain; charset=utf-8");
	}

?>
	<div class="green box">
		<h1><?php print $t->get_translation("header"); ?></h1>
		<p><?php print $t->get_translation("body"); ?></p>
	</div>
<?php

}

$append->close();
$query->close();
mysqli_close($conn);

?>