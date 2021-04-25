<?php
// start session
session_save_path("sessions");
session_set_cookie_params(4 * 60 * 60);
ini_set("session.gc_maxlifetime", 4 * 60 * 60);
ini_set("session.gc_probability", 1);
ini_set("session.gc_divisor", 100);
session_start();

// put this shit inside a function or class
$settings_raw = file_get_contents('./settings/settings.json');
if (!$settings_raw) {
	print("error reading settings file");
}

$settings = json_decode($settings_raw, true);
if ($settings == "") {
	print("empty settings file");
}

//Libraries
include_once("vendor/autoload.php");
include_once("library/util/db.php");
include_once("library/util/translation.php");
include_once("library/util/access_control.php");
include_once("library/util/request.php");
include_once("library/util/log.php");

$base_url = $settings["hosting"]["baseurl"];

//Get request
$language = $_REQUEST["lang"];
$frm_side = $_REQUEST["side"];
$action = $_REQUEST["action"];

// Defaults
if ($language == "") $language = $settings["defaults"]["language"];
if ($frm_side == "") $frm_side = $settings["defaults"]["landing-page"];

$side = "$frm_side.php";

//Translator
$translations_dir = "translations";
$t = new Translator($frm_side, $language);

//Get access rules
$access_control = new AccessControl($_SESSION["user"]);


// handle the request

// block fucker that tries shit
if (!isValidURL($frm_side)) {
	printIllegalRequest();
	return;
}

switch ($frm_side) {
	case "api":

		// file does not exist
		if (!file_exists("private/api/$action.php")) {
			// TODO: wrong request or something like that.
			print("Api: does not exists");
			return;
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

// Illegal request
printIllegalRequest();
return;
