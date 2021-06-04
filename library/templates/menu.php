<?php $t->load_translation("menu");
function menu_item($url, $name = "")
{
	global $t, $frm_side;
	if ($name == "") $name = $url;
	$link = $t->get_url($url);
	$title = $t->get_translation($name, "menu");
	$style = "";
	if ($frm_side == $url) $style = " class='selected'";
	print "<li><a href=\"$link\"$style>$title</a></li>\n";
}
?>
<ul>
	<?php
	menu_item("mainpage");
	menu_item("practices");
	menu_item("activities");
	menu_item("enrollment");
	menu_item("isMember");
	menu_item("board");
	menu_item("FAQ");
	menu_item("store");
	?>
	<li id="lang_switch">
		<a href="<?php print($base_url . ($language != "no" ? "" : "/en") . "/$frm_side"); ?>">
			<?php print $t->get_translation("switchLanguage", "menu") ?>
		</a>
	</li>
</ul>