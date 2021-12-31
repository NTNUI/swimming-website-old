<?php
global $t, $settings, $page;
$t->load_translation('menu');

function menu_entry($url, $name = '')
{
	global $t;
	if ($name == '') $name = $url;
	$link = $t->get_url($url);
	$title = $t->get_translation($name, 'menu');
	print("<li><a href=$link>$title</a></li>");
}
?>
<ul>
	<?php
	menu_entry('mainpage');
	menu_entry('practices');
	menu_entry('activities');
	menu_entry('enrollment');
	menu_entry('board');
	menu_entry('FAQ');
	menu_entry('store');
	?>
	<li id='lang_switch'>
		<a href='<?php print($settings["baseurl"] . ($language != 'no' ? '/' : '/en/') . $page); ?>'>
			<?php print $t->get_translation('switchLanguage', 'menu') ?>
		</a>
	</li>
	<li id="admin_menu_link" style="display:none;">
		<a href="<?php print($settings['baseurl']); ?>/admin">Admin</a>
	</li>
</ul>