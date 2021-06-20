<?php
error_reporting(E_STRICT | E_ALL);
// Start session
session_save_path("sessions");
session_set_cookie_params(4 * 60 * 60);
ini_set("session.gc_maxlifetime", 4 * 60 * 60);
ini_set("session.gc_probability", 1);
ini_set("session.gc_divisor", 100);
session_start();

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

// Get request
$language = argsURL("REQUEST", "lang");
$frm_side = argsURL("REQUEST", "side");
$action = argsURL("REQUEST", "action");
$user = argsURL("SESSION", "user");

// Defaults
$base_url = $settings["baseurl"];
if ($language == "") $language = $settings["defaults"]["language"];
if ($frm_side == "") $frm_side = $settings["defaults"]["landing-page"];
$side = "$frm_side.php";

//Translator
$t = new Translator($frm_side, $language);

//Get access rules
$access_control = new AccessControl($user);

// handle the request
if (isValidURL($frm_side)) {
	switch ($frm_side) {
		case "api":

			// file does not exist
			if (!file_exists("private/api/$action.php")) {
				break;
			}

			include("private/api/$action.php");
			return;
		default:
			// file does not exist
			if (!file_exists("public/$side")) {
				break;
			}

			// valid file, accept request
			include("library/templates/header.php");
			include("public/$side");
			include("library/templates/footer.php");
			return;
	}
}

// Illegal request, page not found
include("library/templates/header.php");
include("library/templates/not_found.php");
include("library/templates/footer.php");
return;
