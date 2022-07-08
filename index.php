<?php

declare(strict_types=1);

error_reporting(E_ALL & ~E_NOTICE);
// Don't output errors to standard output. That breaks json formatting in API
ini_set("display_errors", "0");
ini_set("max_execution_time", "5"); // seconds

// Libraries
require_once("Library/Util/AccessControl.php");
require_once("Library/Util/Authenticator.php");
require_once("Library/Util/Db.php");
require_once("Library/Util/Log.php");
require_once("Library/Util/Request.php");
require_once("Library/Util/Settings.php");
require_once("Library/Util/Translator.php");
require_once("vendor/autoload.php");

// Load settings and environments
$settings = Settings::get_instance("./settings/settings.json");
$settings->test_settings();
$settings->init_session();

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$dotenv->required(
	[
		'STRIPE_SECRET_KEY',
		'STRIPE_PUBLISHABLE_KEY',
		'STRIPE_SIGNING_KEY',
		'GOOGLE_CAPTCHA_KEY',
		"DB_HOSTNAME",
		"DB_USERNAME",
		"DB_PASSWORD"
	]
)->notEmpty();


// Get request
$language = argsURL("REQUEST", "lang") ?? $settings->get_language();
$page = argsURL("REQUEST", "side") ?? $settings->get_landing_page();
$action = argsURL("REQUEST", "action") ?? "";
$user = argsURL("SESSION", "username") ?? "";

// Translator
$t = new Translator($page, $language);
// Get access rules
$access_control = new AccessControl($user);
// handle the request
$page = ucfirst($page);
$action = ucfirst($action);
if (isValidURL($page)) {
	switch ($page) {
		case "Api":

			// file does not exist
			if (!file_exists("Private/Api/$action.php")) {
				break;
			}

			require_once("Private/Api/$action.php");
			return;
		default:
			// file does not exist
			if (!file_exists("Public/$page.php")) {
				break;
			}

			// valid file, accept request
			require_once("Library/Templates/Header.php");
			require_once("Public/$page.php");
			require_once("Library/Templates/Footer.php");
			return;
	}
}

// Illegal request, page not found
require_once("Library/Templates/Header.php");
require_once("Library/Templates/NotFound.php");
require_once("Library/Templates/Footer.php");
return;
