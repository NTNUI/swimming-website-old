<?php
session_start();
if (!$access_control->can_access("api", "gavekort")) {
	header("HTTP/1.0 403 Forbidden");
	die("You do not have access to this page");
}

$data = json_decode(file_get_contents("php://input"));
$preview = !isset($_GET["submit"]) or $_GET["submit"] != 1;
$from = "svomming-stevne@ntnui.no";
$subject = "Premiering fra NTNUI-svømming";

$html = "<h1>" . $data->name . ", du har vunnet et gavekort!</h1>" .
	"<p>Verdi: " . $data->amount . " kr<br>" .
	"Kode: <strong>" . $data->code . "</strong><br>" . 
	$data->extra . "</p>" .
	"<small>Denne eposten ble generert av NTNUI-svømming, ta kontakt med <a href='mailto:$from'>$from</a> om noe er galt</small>";
 
if ($preview) {
	print "From: <strong>$from</strong><br>";
	print "To: <strong>" . $data->email . "</strong><br>";
	print "Subject: <strong>$subject</strong>";
	print $html;
} else {
	mail($data->email, $subject, $html, "From: $from\r\nContent-type: text/html; charset=utf-8"); 
	$access_control->log("api/gavekort", "send " . $data->amount, $data->email);
}

