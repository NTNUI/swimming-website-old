<?php
if (!$access_control->can_access("api", "gavekort")) {
	log::forbidden("Access denied", __FILE__, __LINE__);
}

$data = json_decode(file_get_contents("php://input"));
$preview = !isset($_GET["submit"]) or $_GET["submit"] != 1;
$subject = "Premiering fra NTNUI-svømming";

$html = "<h1>" . $data->name . ", du har vunnet et gavekort!</h1>" .
	"<p>Verdi: " . $data->amount . " kr<br>" .
	"Kode: <strong>" . $data->code . "</strong><br>" . 
	$data->extra . "</p>"; 
if ($preview) {
	print "To: <strong>" . $data->email . "</strong><br>";
	print "Subject: <strong>$subject</strong>";
	print $html;
} else {
	mail($data->email, $subject, $html, "Content-type: text/html; charset=utf-8"); 
}


