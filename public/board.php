<?php
include_once("library/templates/content.php");
global $t;
print_content_header(
	$t->get_translation("mainHeader"),
	$t->get_translation("subHeader")
);
$entries = [
	"leder",
	"nestleder",
	"kasserer",
	"dommeransvarlig",
	"teknisk",
	"trener",
	"arrangement",
	"styremedlem",
	"senior_styremedlem",
	"medlemsansvarlig",
	"pransvarlig"
];
foreach ($entries as $key) {
	print_content_block(
		$t->get_translation($key),
		$t->get_translation($key . "_description"),
		$t->get_translation($key . "_img_path"),
		$t->get_translation($key . "_name"),
		$t->get_translation($key . "_email"),
		"img-" . $key
	);
}
style_and_script(__FILE__);
