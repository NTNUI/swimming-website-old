<?php
require_once("library/templates/content.php");
global $t;
print_content_header(
	$t->get_translation("mainHeader"),
	$t->get_translation("subHeader")
);
$entries = [
	"practices",
	"laneSections"
];
foreach($entries as $key){
	print_content_block(
		$t->get_translation($key . "_header"),
		$t->get_translation($key . "_description"),
		"",
		""
	);
}

style_and_script(__FILE__);

