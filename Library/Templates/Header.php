<?php

declare(strict_types=1);

global $t;
global $page;
$settings = Settings::get_instance();
$title = $t->get_translation("page_title");
if ($title == "") $title = $t->get_translation($page);
if ($title == "") $title = ucwords($page);
?>
<!DOCTYPE html>
<html>

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta property="og:title" content="NTNUI Svømming" />
	<meta name="description" content="NTNUI Svømming er en svømmeklubb for studenter ved universitetet NTNU i Trondheim." />
	<meta name="title" content="NTNUI Svømming" />
	<title>NTNUI Svømming - <?php print $title; ?></title>
	<link rel="shortcut icon" href="<?php print($settings->get_baseurl()); ?>/img/icons/logo.ico">
	<link rel="stylesheet" href="<?php print($settings->get_baseurl()); ?>/css/style.css" type="text/css" />
	<link rel="stylesheet" href="<?php print($settings->get_baseurl()); ?>/css/menu.css" type="text/css" />
	<link rel="stylesheet" href="<?php print($settings->get_baseurl()); ?>/css/content.css" type="text/css" />
	<link rel="stylesheet" href="<?php print($settings->get_baseurl()); ?>/css/inputs.css" type="text/css" />
	<link rel="stylesheet" href="<?php print($settings->get_baseurl()); ?>/css/admin.css" type="text/css" />
	<link rel="stylesheet" href="<?php print($settings->get_baseurl()); ?>/css/smallscreen.css" type="text/css" media="screen and (max-width: 1000px)" />
	<script><?php
		if (isset($_SESSION['name'])) {
			print("const NAME_USER = '" . $_SESSION['name'] . "';");

		}?>
		const BASEURL = "<?php print $settings->get_baseurl(); ?>";
		const STRIPE_PUBLISHABLE_KEY = "<?php print $_ENV['STRIPE_PUBLISHABLE_KEY']; ?>";
		const INVENTORY_URL = BASEURL + "<?php global $language; print '/api/inventory?language=' .$language; ?>";
		const SERVER_TIME_OFFSET = new Date().getTime() - <?php print time() ?> * 1000;
		const LANGUAGE = "<?php print $language ?>";

	</script>
	<script src="<?php print $settings->get_baseurl(); ?>/js/base.js"></script>
</head>

<body>
	<div id="mobile_menu_button">
		Menu
	</div>
	<div id="menu_container">
		<div id="menu" class="menu">
			<?php require_once("Library/Templates/Menu.php"); ?>
		</div>
	</div>
	<div class="content">