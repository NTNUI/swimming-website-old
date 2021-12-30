<?php
require_once("library/templates/content.php");
global $t;
$entries = [
	"swim_meets",
	"student_games",
	"swim_course",
	"cabin_trips",
	"referee_course",
	"training_camps"
];

print_content_header(
	$t->get_translation("mainHeader"),
	$t->get_translation("subHeader")
);
foreach ($entries as $key) {
	print_content_block(
		$t->get_translation($key . "_header"),
		$t->get_translation($key . "_description"),
		$t->get_translation($key . "_img_path"),
		$t->get_translation($key . "_caption"),
		"",
		""
	);
}
