<?php
session_start();
if (!$access_control->can_access("api", "memberregister")) {
	header("HTTP/1.0 403 Forbidden");
	die("You do not have access to this page");
}

function sendEmail($emailAdress){

	$subject = "NTNUI Svomming: Medlemskap godkjent / Membership approved";
	$from = "svomming-kasserer@ntnui.no";

	// norwegian
	$headers .= "Dette er en automatisk e-post sendt av NTNUI-Svømmegruppas medlemssystem. <br>";
	$headers .= "Din innmelding er registrert og godkjent i vår database.<br>";
	$headers .= "Du kan gå til skranken i Pirbadet og hente ut ditt adgangskort nå. Husk at resepsjonen stenger 30 min før badet stenger for publikum (20:30). Sjekk ut Pirbadet sine <a href='https://pirbadet.no'>nettsider</a>.<br>";
	$headers .= "<strong>PGA COVID-19 MÅ ALLE REGISTRERE SEG FØR TRENING. MER INFORMASJON I FACEBOOKGRUPPEN https://www.facebook.com/groups/2250060697</strong><br>";
	$headers .= "<br>";

	// english
	$headers .= "This is an automatic e-mail sent from the membership system in NTNUI Swimming group. <br>";
	$headers .= "Your enrollment is registered and approved in our database.<br>";
	$headers .= "You can get your new access card at Pirbadet now. Remeber that the reception closes 30 minutes before practice (20:30). Check the opening hours for Pirbadet <a href='https://pirbadet.no/en'>here</a>. <br>";
	$headers .= "<strong>DUE TO THE COVID-19 SITUATION EVERYONE HAS TO SIGN UP BEFORE EACH PRACTICE. MORE INFORMATION IN OUR FACEBOOK GROUP https://www.facebook.com/groups/2250060697</strong><br>";
	$headers .= "<br>";
	$headers .= "Med vennlig hilsen / Best regards <br>";
	$headers .= "NTNUI Svømming";

	// send mail
	mail($emailAdress, $subject, $headers, "From: $from\r\nContent-type: text/html; charset=utf-8");

}


// Create connection
$conn = connect("member");
// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

//Register user
if (!isset($_REQUEST['id'])) {
	header("HTTP/1.0 400 Bad request");
	die("You need to supply an id to register");
}

$id = $_REQUEST['id'];

$sql = "UPDATE ". $settings["memberTable"] . " SET kontrolldato=NOW() WHERE id=?";
$query = $conn->prepare($sql);
$query->bind_param("i", $id);

if (!$query->execute()) {
	header("HTTP/1.0 500 Internal Server Error");
	die("SQL Error");
}
$query->close();

$access_control->log("api/memberregister", "approve", $id);

$sql = "SELECT fornavn, etternavn, epost FROM ". $settings["memberTable"] . " WHERE id=?";
$query = $conn->prepare($sql);
$query->bind_param("i", $id);
$query->execute();
$query->bind_result($fornavn, $etternavn, $epost);
if (!$query->fetch()) {
	header("HTTP/1.0 500 Internal Server Error");
	die("SQL Error");
}

sendEmail($epost);

header("Content-Type: application/json");
print(json_encode(array(
	"fornavn" => $fornavn,
	"etternavn" => $etternavn,
	"epost" => $epost,
	"success" => true)
));
$query->close();
$conn->close();

