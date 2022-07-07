<?php

declare(strict_types=1);

error_reporting(E_ALL & ~E_NOTICE);
// Don't output errors to standard output. That breaks json formatting in API
ini_set("display_errors", "0");
ini_set("max_execution_time", "5"); // seconds

# Load external libraries
require_once("vendor/autoload.php");

// Start session
session_save_path("sessions");
session_set_cookie_params(4 * 60 * 60);
ini_set("session.gc_maxlifetime", (string)(4 * 60 * 60));
ini_set("session.gc_probability", "1");
ini_set("session.gc_divisor", "100");
session_start();

// Load settings and environments
require_once("Library/Util/Settings.php");
$settings = load_settings("./settings/settings.json");

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

// Libraries
require_once("Library/Util/AccessControl.php");
require_once("Library/Util/Authenticator.php");
require_once("Library/Util/Db.php");
require_once("Library/Util/Log.php");
require_once("Library/Util/Request.php");
require_once("Library/Util/Translator.php");

// Check write permissions
test_settings();

// Get request
$language = argsURL("REQUEST", "lang") ?? $settings["defaults"]["language"];
$page = argsURL("REQUEST", "side") ?? $settings["defaults"]["landing-page"];
$action = argsURL("REQUEST", "action");
$user = argsURL("SESSION", "username");

// Translator
$t = new Translator($page, $language);
// Get access rules
$access_control = new AccessControl($user);
// handle the request
if (isValidURL($page)) {
	$page = ucfirst($page);
	$action = ucfirst($action);
	switch ($page) {
		case "api":

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
