<?php

// $sendTo = "svomming-leder@ntnui.no";
$sendTo = $settings["emails"]["leader"];

function printMessage($type){ // NOT FINNISHED YET #PAVEL
	switch ($type) {
		case 'Sucess':
			print("<div class='box'> Message has been sent</div>"); // needs to be linked to translations
			break;
		case 'Failure':
			print("<div class='box'> Message has net been sent. Try again or contact techical department for help</div>");
			break;
	}
}

if (isset($_POST["button"])) {
	$cat = $_POST["kategori"];
	$kom = $_POST["kommentar"];
	if ($cat == "") $cat = "Annet";
	if ($kom != "") {
		if (mail($sendTo, "[NTNUI-Svømming] Feedback: $cat",
			"Ny melding sendt via feedback-siden " . date("d/m/Y H:i:s") . ": \n\n$kom\n\n--\nIkke svar på denne meldingen",
			"From: svomming-web@list.stud.ntnu.no;\r\nContent-Type: text/plain; charset=UTF-8")) {

			echo "Message sent";
		} else {
			echo "Noe gikk galt";
		}
		return;
	}
}

 ?>

 <div class="box">

 <h1><?php print $t->get_translation("header"); ?></h1>
   <p><?php print $t->get_translation("sendTo") . $sendTo; ?></p>

 </div>

<div class="box">

  <form class="" method="post">
  <label for="kategori"><?php print $t->get_translation("kategori_label"); ?></label>
    <input type="text" name="kategori" placeholder="Annet">
    <label for="kommentar"><?php print $t->get_translation("kommentar_label"); ?></label>
    <textarea type="text" name="kommentar" placeholder="Din melding" required="true"></textarea>
    <button type="submit" name="button" cols="40" rows="10">Submit</button>
  </form>

</div>
