<?php
if (!$access_control->can_access("api", "memberregister")) {
	log::message("Info: Access denied for " . Authenticator::get_username(), __FILE__, __LINE__);
	log::forbidden("Access denied", __FILE__, __LINE__);
}

function sendEmail($emailAdress)
{

	$subject = "NTNUI Svomming: Medlemskap godkjent / Membership approved";
	$from = "svomming-kasserer@ntnui.no";

	// norwegian
	$headers = "Dette er en automatisk e-post sendt av NTNUI-Svømmegruppas medlemssystem. <br>";
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
{
	$db = new DB("member");
	// Get id
	if (!isset($_REQUEST['id'])) {
		header("HTTP/1.0 400 Bad request");
		die("You need to supply an id to register");
	}

	$id = $_REQUEST['id'];

	// Register user
	$sql = "UPDATE member SET approved_date=NOW() WHERE id=?";
	$db->prepare($sql);
	$db->bind_param("i", $id);

	$db->execute();
}

// Send approval email to member
{
	// log action
	$access_control->log("api/memberregister", "approve", $id);

	// send confirmation email to user
	$db = new DB("member");
	$sql = "SELECT first_name, surname, email FROM member WHERE id=?";
	$db->prepare($sql);
	$db->bind_param("i", $id);
	$db->execute();
	$db->stmt->bind_result($first_name, $surname, $email);
	if (!$db->fetch()) {
		header("HTTP/1.0 500 Internal Server Error");
		die("SQL Error");
	}

	sendEmail($email);
}

// Return success to admin client
http_response_code(200);
log::message("Info: Approving $first_name $surname manually", __FILE__, __LINE__);
print(json_encode(
	[
		"first_name" => $first_name,
		"surname" => $surname,
		"email" => $email,
		"success" => true
	]
));
