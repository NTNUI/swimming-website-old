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
require_once("library/util/settings.php");
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
require_once("library/util/db.php");
require_once("library/util/translator.php");
require_once("library/util/access_control.php");
require_once("library/util/request.php");
require_once("library/util/log.php");
require_once("library/util/authenticator.php");

// Check write permissions
test_settings();

// Get request
$language = argsURL("REQUEST", "lang");
$page = argsURL("REQUEST", "side");
$action = argsURL("REQUEST", "action");
$user = argsURL("SESSION", "username");

// Defaults
if ($language == "") $language = $settings["defaults"]["language"];
if ($page == "") $page = $settings["defaults"]["landing-page"];

// Translator
$t = new Translator($page, $language);
// Get access rules
$access_control = new AccessControl($user);
// handle the request
if (isValidURL($page)) {
	switch ($page) {
		case "api":

			// file does not exist
			if (!file_exists("private/api/$action.php")) {
				break;
			}

			require_once("private/api/$action.php");
			return;
		default:
			// file does not exist
			if (!file_exists("public/$page.php")) {
				break;
			}

			// valid file, accept request
			require_once("library/templates/header.php");
			require_once("public/$page.php");
			require_once("library/templates/footer.php");
			return;
	}
}

// Illegal request, page not found
require_once("library/templates/header.php");
require_once("library/templates/not_found.php");
require_once("library/templates/footer.php");
return;
