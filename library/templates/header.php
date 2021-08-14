<?php
global $t;
$title = $t->get_translation("page_title");
if ($title == "") $title = $t->get_translation($frm_side);
if ($title == "") $title = ucwords($frm_side);
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>NTNUI Svømming - <?php print $title; ?></title>
	<link rel="shortcut icon" href="<?php global $settings; print($settings['base_url']); ?>/img/icons/logo.ico">
	<?php /*
	<link href="https://fonts.googleapis.com/css?family=M+PLUS+Rounded+1c" rel="stylesheet"> 
	<link href="https://fonts.googleapis.com/css?family=Heebo&display=swap" rel="stylesheet">
	*/ ?>
	<link rel="stylesheet" href="<?php global $settings; print($settings['base_url']); ?>/css/style.css" type="text/css"/>
	<link rel="stylesheet" href="<?php global $settings; print($settings['base_url']); ?>/css/forms.css" type="text/css"/>
	<link rel="stylesheet" href="<?php global $settings; print($settings['base_url']); ?>/css/store.css" type="text/css"/>
	<link rel="stylesheet" href="<?php global $settings; print($settings['base_url']); ?>/css/smallscreen.css" type="text/css" media="screen and (max-width: 1080px)"/>
	<!-- Stripe fraud stuff recommends this on all pages hmmm -->
	<script src="https://js.stripe.com/v3/"></script>
	<style>
		html {
			background: url("<?php global $settings; print($settings['base_url']); ?>/img/background.jpg") no-repeat center center fixed;
			background-size: cover;
		}
	</style>
</head>

<body class="body_style">
	<div class="maintable">
		<div class="banner">
			<a href="<?php print $t->get_url(""); ?>"><img src="<?php global $settings; print($settings['base_url']); ?>/img/icons/logo.jpg"?>"></a>
		</div>
		<div id="menu_show">Menu</span><span class="right"><a href="<?php ($language != "no" ? "" : "/en") . "/$page"  ?>"><?php print ($language != "no" ? "Norsk" : "English"); ?></a></span></div>
		<div id="menu_container">
			<div id="menu" class="menu">
				<?php include("library/templates/menu.php"); ?>
			</div>
		</div>
		<div class="textboks">
	
