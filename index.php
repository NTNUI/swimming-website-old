<?php
error_reporting(E_STRICT | E_ALL);

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
$language = NULL;
$frm_side = NULL;
$action = NULL;
$user = NULL;

if (isset($_REQUEST["lang"])) {
	$language = $_REQUEST["lang"];
}
if (isset($_REQUEST["side"])) {
	$frm_side = $_REQUEST["side"];
}
if (isset($_REQUEST["action"])) {
	$action = $_REQUEST["action"];
}
if (isset($_SESSION["user"])) {
	$user = $_SESSION["user"];
}



// Defaults
if ($language == "") $language = $settings["defaults"]["language"];
if ($frm_side == "") $frm_side = $settings["defaults"]["landing-page"];

$side = "$frm_side.php";

//Translator
$translations_dir = "translations";
$t = new Translator($frm_side, $language);

//Get access rules
$access_control = new AccessControl($user);


// handle the request

// block fucker that tries shit
if (!isValidURL($frm_side)) {
	include("library/templates/header.php");
	include("library/templates/not_found.php");
	include("library/templates/footer.php");
	return;
}

switch ($frm_side) {
	case "api":

		// file does not exist
		if (!file_exists("private/api/$action.php")) {
			// TODO: Redirect to some default error page
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
include("library/templates/header.php");
include("library/templates/not_found.php");
include("library/templates/footer.php");
return;
