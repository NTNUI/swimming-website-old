<?php
require_once("library/templates/content.php");
global $t;
print_content_header(
	$t->get_translation("mainHeader"),
	$t->get_translation("subHeader")
);

$entries = [
	"join",
	"cost",
	"paymentinfo",
	"trial",
	"level",
	"mandatory",
	"paid_license",
	"other_club",
	"outside_training_hours",
	"equipment"
];
foreach($entries as $key){
	print_content_block(
		$t->get_translation($key . "_question"),
		$t->get_translation($key . "_answer"),
		"",
		""
	);
}
style_and_script(__FILE__);
