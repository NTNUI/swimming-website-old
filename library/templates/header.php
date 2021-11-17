<?php
global $t;
global $page;
global $settings;
$title = $t->get_translation("page_title");
if ($title == "") $title = $t->get_translation($page);
if ($title == "") $title = ucwords($page);
?>
<!DOCTYPE html>
<html>

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>NTNUI Svømming - <?php print $title; ?></title>
	<link rel="shortcut icon" href="<?php print($settings['baseurl']); ?>/img/icons/logo.ico">
	<link rel="stylesheet" href="<?php print($settings['baseurl']); ?>/css/style.css" type="text/css" />
	<link rel="stylesheet" href="<?php print($settings['baseurl']); ?>/css/menu.css" type="text/css" />
	<link rel="stylesheet" href="<?php print($settings['baseurl']); ?>/css/board.css" type="text/css" />
	<link rel="stylesheet" href="<?php print($settings['baseurl']); ?>/css/enrollment.css" type="text/css" />
	<link rel="stylesheet" href="<?php print($settings['baseurl']); ?>/css/forms.css" type="text/css" />
	<link rel="stylesheet" href="<?php print($settings['baseurl']); ?>/css/store.css" type="text/css" />
	<link rel="stylesheet" href="<?php print($settings['baseurl']); ?>/css/admin.css" type="text/css" />
	<link rel="stylesheet" href="<?php print($settings['baseurl']); ?>/css/smallscreen.css" type="text/css" media="screen and (max-width: 1080px)" />
	<!-- Stripe fraud stuff recommends this on all pages hmmm -->
	<script src="https://js.stripe.com/v3/"></script>
	<style>
		html {
			background-size: cover;
		}
	</style>
	<script>
		<?php
		if (isset($_SESSION['name'])) {
			print("const NAME_USER = '" . $_SESSION['name'] . "';");
		}
		?>

		const BASEURL = "<?php print $settings['baseurl']; ?>";
		const STRIPE_PUBLISHABLE_KEY = "<?php print $settings['stripe']['publishable_key']; ?>";

	</script>
	<script src="<?php print $base_url; ?>/js/base.js"></script>
</head>

<body class="body_style">
	<div id="menu_show">Menu</span><span class="right"><a href="<?php ($language != "no" ? "" : "/en") . "/$page"  ?>"><?php print($language != "no" ? "Norsk" : "English"); ?></a></span></div>
	<div id="menu_container">
		<div id="menu" class="menu">
			<?php include("library/templates/menu.php"); ?>
		</div>
	</div>
	<div class="content">