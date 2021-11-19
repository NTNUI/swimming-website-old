<?php
global $t, $settings, $page;
$t->load_translation('menu');
function menu_item(string $url, string $name = '')
{
	global $t;
	if ($name == '') $name = $url;
	$link = $t->get_url($url);
	$title = $t->get_translation($name, 'menu');
	print("<li class='menu-item'><a href=$link>$title</a></li>");
}
?>
<ul>
	<?php
	menu_item('mainpage');
	menu_item('practices');
	menu_item('activities');
	menu_item('enrollment');
	menu_item('board');
	menu_item('FAQ');
	menu_item('store');
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