<?php
function handle_error($ErrorMessage){ // not in use due to my poorly programming skillz #Pavel
	global $t;
	$message = $t->get_translation($ErrorMessage);
	echo "<script type='text/javascript'>alert('$message'); window.history.back(); </script>";
	print ("<div class='box error'>$message</div>"); //Legacy noscript support
	return;
}

function isTri($string) {
	if (strpos(strtolower($string), "triatlon") !== false) return true;
	if (strpos(strtolower($string), "ntf") !== false) return true;
	if (strpos(strtolower($string), "tri") !== false) return true;
	return 0;
}
try {

// $debugging = false;
$debugging = $settings["hosting"]["debugging"];

// $licence_key = "NSFLicence2019";
$licence_key = $settings["defaults"]["licence_key"];

$t->load_translation("store");
include_once("library/util/store_helper_v2.php");
$store = new StoreHelper($language);
error_reporting($settings["hosting"]["errorReporting"]);

$item = $store->get_item($licence_key);
$item["id"] = $item["api_id"];

// Get post data

$UNSAFE_firstName 		= $_POST['fornavn'];
$UNSAFE_lastName 			= $_POST['etternavn'];
$UNSAFE_phoneNumber 	= $_POST['phoneNumber'];
$UNSAFE_birthDate 		= $_POST['fodselsdato'];
$UNSAFE_gender 				= $_POST['gender'];
$UNSAFE_proficient 		= $_POST['dyktig'];
$UNSAFE_voluntaryWork = $_POST['dugnad'];
$UNSAFE_zipCode 			= $_POST['zip'];
$UNSAFE_adress 				= $_POST['adresse'];
$UNSAFE_email 				= $_POST['email'];
$UNSAFE_cardNumber 		= $_POST['kortnummer'];
$UNSAFE_comment 			= $_POST['beskjed'];
$UNSAFE_filledOut 		= $_REQUEST['utfylt'];
$oldClub = $_POST['gammelKlubb'];
$triatlon = isTri($oldClub);
// convert POST data into safe values - has to be done #Pavel

if($UNSAFE_voluntaryWork == ""){$dugnaf = FALSE;}
if($UNSAFE_gender ==""){$UNSAFE_gender = FALSE;}
// if($subscription == ""){$subscription = FALSE;} // skrur av siden det er ikke implementert enda #Pavel
// if($oldClub=="") {$oldClub = FALSE;} // skrur av siden det er ikke implementert enda #Pavel

if($debugging){
print("<div class='box green'>
	<h1>POST DATA:</h1>
		<p>
			fornavn: ".$UNSAFE_firstName." <br>
			etternavn: ".$UNSAFE_lastName." <br>
			telefonnummer: ".$UNSAFE_phoneNumber." <br>
			fødsalsdato: ".$UNSAFE_birthDate."<br>
			kjønn: ".$UNSAFE_gender."<br>
			svømmedyktig: ".$UNSAFE_proficient."<br>
			dugnad: ".$UNSAFE_voluntaryWork."<br>
			postnummer: ".$UNSAFE_zipCode."<br>
			adresse: ".$UNSAFE_adress."<br>
			epost: ".$UNSAFE_email."<br>
			medlemsnummer NTNUI: ".$UNSAFE_cardNumber."<br>
			harLisens: ".$hasLicense."<br>
			Beskjed: ".$UNSAFE_comment."<br>
			Utfyollt: ".$UNSAFE_filledOut."<br>
			Gammel klubb: $oldClub <br>
			Triatlon? $triatlon <br>

		</p>
</div>");
}




/* fra debugging midlertidig tatt ut #Pavel
nyhetsbrev: ".$subscription."<br>
Skole: ".$skole."<br>
gammelKlubb: ".$oldClub."<br>
*/

//Invalid dates
if (strtotime($UNSAFE_birthDate) === false ) {
	handle_error("error_fodselsdato");
	return;
}
//Convert dates to mysql supported format
$UNSAFE_birthDate = date("Y-m-d", strtotime($UNSAFE_birthDate));

// feilfinning:
if(0){ // $skole === "Annet" && $UNSAFE_comment !== ""
	handle_error("error_skole");
	return;
}

if(0){ // $skole === "BI" && $UNSAFE_comment !== ""
	handle_error("error_skole");
	return;
}

// Aksepterer ikke dugnadsarbeid ;
if ($UNSAFE_voluntaryWork !== "Yes"){
	handle_error("error_dugnad");
	return;
}

if(!ctype_digit($UNSAFE_cardNumber)){

	handle_error("cardnumber_not_number");
	return;
} elseif (strlen($UNSAFE_cardNumber) > 9) {
	handle_error("cardnumber_too_long");
	return;
}

// ikke Svømmedyktig
if ($UNSAFE_proficient !== "Yes"){
	handle_error("error_dyktig");
	return;
}

if(0){ // $hasLicense == FALSE && $UNSAFE_comment !=0
	//har lisens i en annen svømmeklubb men ikke oppgitt det i komentarfeltet
	$ErrorMessage = "Du har sagt at du har lisens i en annen svømmeklubb, men
	kommentarfeltet er tomt. Vennligst oppgi hvilken klubb som har betalt lisensen din i kommentarfeltet.";
	echo "<script type='text/javascript'>alert('$ErrorMessage');</script>";
	echo "<script type='text/javascript'>history.go(-1);</script>";
}

//Captcha test
// $secret = "6LdrnW8UAAAAAIDoJVmceXJ_DxkmlqHMMwF3r4I1";
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

// debugging variable dump
if($debugging) {
	print("<br> <h1>Variable Dump</h1><p>"); var_dump($decoded); print("</p>");
	if($UNSAFE_firstName != "")		{ print("<br>true fornavn <br>");	}
	if($UNSAFE_lastName != "")	{ print("true Etternavn <br>");		}
	if($UNSAFE_birthDate != ""){ print("true fodselsdag <br>");	}
	if($UNSAFE_gender != "")			{ print("true sex <br>");					}
	if($UNSAFE_proficient != "")			{ print("true dyktig <br>");			}
	if($UNSAFE_voluntaryWork != "")			{ print("true dugnad <br>");			}
	if($UNSAFE_zipCode != "")				{ print("true zip <br>");					}
	if($UNSAFE_adress != "")		{ print("true adresse <br>");			}
	if($UNSAFE_email != "")			{ print("true email <br>");				}
	if($UNSAFE_cardNumber != "")	{ print("true kortnummer <br>");	}
	if($skole != "")			{ print("true skole <br>");				}
}

} catch (Exception $e) {
	var_dump($e);
}


if (!$decoded->success && !$debugging) {
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

if (!($UNSAFE_firstName != "" && $UNSAFE_lastName != "" && $UNSAFE_birthDate != "" && $UNSAFE_gender != "" && $UNSAFE_proficient != ""
&& $UNSAFE_voluntaryWork != "" && $UNSAFE_zipCode != "" && $UNSAFE_adress != "" && $UNSAFE_email != "" && $UNSAFE_cardNumber != "" && $UNSAFE_phoneNumber != "" && is_numeric($UNSAFE_cardNumber)))
{
	//hvis noen fyller ut mennesketest men glemmer noen av de andre obligatoriske feltene
	handle_error("error_empty");
	return;
}

$conn = connect("member");
if (!$conn) {
	die("Connection failed: " . mysqli_connect_error());
}

$query = $conn->prepare("SELECT epost FROM " . $settings["memberTable"] . " WHERE epost=?");
$query->bind_param("s", $UNSAFE_email);
$query->execute();
$UNSAFE_emailFound = $query->fetch();
//User found
if ($UNSAFE_emailFound) {

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

	if(1){
		$sql = "INSERT INTO " . $settings["memberTable"] . "(kjonn, fodselsdato, etternavn, fornavn, phoneNumber, adresse, epost,  kommentar ,kortnr, postnr, regdato, gammelKlubb, triatlon) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)";
	}else {
		$sql = "INSERT INTO medlem_2019(kjonn, fodselsdato, etternavn, fornavn, phoneNumber, adresse, epost,  kommentar ,kortnr, postnr, regdato, gammelKlubb, triatlon) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)";
	}



	$append = $conn->prepare($sql);
	$append->bind_param("ssssssssiiss", $UNSAFE_gender, $UNSAFE_birthDate, $UNSAFE_lastName, $UNSAFE_firstName, $UNSAFE_phoneNumber, $UNSAFE_adress, $UNSAFE_email, $UNSAFE_comment, $UNSAFE_cardNumber, $UNSAFE_zipCode, $oldClub, $triatlon);
	if (!$append->execute()) {
		echo "Error: " . $sql . "<br>" . mysqli_error($conn);
		$append->close();
		$query->close();
		mysqli_close($conn);
		return;
	}

	//sende mail til kasserer
	if (1) {
		$sendTo = $settings["emails"]["analyst"];
	} else {
		$sendTo = "svomming-kasserer@ntnui.no";
	}

	$headers .= "Ny medlem registrert, logg på adminsiden for mer info";


	if (1) {
		$from = $settings["emails"]["bot-general"];
	} else {
		$from = "NTNUI_Svommin_Artificial_Intelligence@ntnui.no";
	}

	// Depends on the accountant, but many prefer not to get a mail for each new member
//	mail($sendTo, "NTNUI-Svømming: Nytt medlem", $headers, "From: $from\r\nContent-type: text/plain; charset=utf-8"); // ADVARSEL: hvis Content-type: blir endret til text/html må variablene som går inn i $headers saniteres mot html injection #Pavel

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
