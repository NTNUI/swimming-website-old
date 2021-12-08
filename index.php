<?php
// require strict type checking
declare(strict_types=1);
error_reporting(E_ALL & ~E_NOTICE);
// Don't output errors to standard output. That breaks json formatting in API
ini_set("display_errors", "0");
ini_set("max_execution_time", "5"); // seconds

// Start session
session_save_path("sessions");
session_set_cookie_params(4 * 60 * 60);
ini_set("session.gc_maxlifetime", (string)(4 * 60 * 60));
ini_set("session.gc_probability", "1");
ini_set("session.gc_divisor", "100");

// Load settings
include_once("library/util/settings.php");
$settings = load_settings("./settings/settings.json");

// Libraries
include_once("vendor/autoload.php");
include_once("library/util/db.php");
include_once("library/util/translator.php");
include_once("library/util/access_control.php");
include_once("library/util/request.php");
include_once("library/util/log.php");
include_once("library/util/authenticator.php");

// Check write permissions
test_settings();

// Get request
$language = argsURL("REQUEST", "lang");
$page = argsURL("REQUEST", "side");
$action = argsURL("REQUEST", "action");
$user = argsURL("SESSION", "username");

// Defaults
$base_url = $settings["baseurl"]; // deprecated: expand variable locally
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

			include("private/api/$action.php");
			return;
		default:
			// file does not exist
			if (!file_exists("public/$page.php")) {
				break;
			}

			// valid file, accept request
			include("library/templates/header.php");
			include("public/$page.php");
			include("library/templates/footer.php");
			return;
	}
}

// Illegal request, page not found
include("library/templates/header.php");
include("library/templates/not_found.php");
include("library/templates/footer.php");
return;
