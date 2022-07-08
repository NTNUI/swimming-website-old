<?php

declare(strict_types=1);

global $t, $page;
$settings = Settings::get_instance();
$t->load_translation('menu');

function menu_entry($url, $key = '')
{
	global $t;
	if ($key == '') $key = $url;
	$link = $t->get_url($url);
	$title = $t->get_translation($key, 'menu');
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
	menu_entry('faq');
	menu_entry('store');
	?>
	<li id='lang_switch'>
		<a href='<?php print($settings->get_baseurl() . ($language != 'no' ? '/' : '/en/') . lcfirst($page)); ?>'>
			<?php print $t->get_translation('switchLanguage', 'menu') ?>
		</a>
	</li>
	<li id="admin_menu_link" style="display:none;">
		<a href="<?php print($settings->get_baseurl()); ?>/admin">Admin</a>
	</li>
</ul>