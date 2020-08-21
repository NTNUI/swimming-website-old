<?php
//kode for å ta vare på eller eventuelt starte sessions
session_save_path("sessions");
session_set_cookie_params(4*60*60);
ini_set("session.gc_maxlifetime", 4*60*60);
ini_set("session.gc_probability", 1);
ini_set("session.gc_divisor", 100);
session_start();

// put this shit inside a function or class
$settings_raw = file_get_contents('./settings/live.json');
if(!$settings_raw){
    print("error reading settings file");
}

$settings = json_decode($settings_raw,true);
if($settings == ""){
	print("empty settings file");
}


//Libraries

include_once("vendor/autoload.php");
include_once("library/util/db.php");
include_once("library/util/translation.php");
include_once("library/util/access_control.php");

$translations_dir = "translations";

// $base_url = "https://org.ntnu.no/svommer"; // old #pavel
$base_url = $settings["hosting"]["baseurl"];


//Get request
$language = $_REQUEST["lang"];
$frm_side = $_REQUEST["side"];

//Defaults
/*if ($language == "") $language = "no";
if ($frm_side == "") $frm_side = "mainpage"; */

if ($language == "") $language = $settings["defaults"]["language"];
if ($frm_side == "") $frm_side = $settings["defaults"]["landing-page"];




function getPage($request = "mainpage", $language = "no") {
	$side = "${request}_$language.php";
	if (file_exists($side)) return $side;
	//Fall back to norwegian
	$side = "${request}_no.php";
	if (file_exists($side)) return $side;
	//Fall back to no language
	$side = "${request}.php";
	return $side; //If this doesent exists, we let the thing below handle that
}

//Legacy filenames
$side = getPage($frm_side, $language);

//Translator
$t = new Translator($frm_side, $language);

//Deprecated, use $t->get_translation()
function getTranslation($key) {
	global $t;
	return $t->get_translation($key);
}

//Get access rules
$access_control = new AccessControl($_SESSION["user"]);
error_reporting(E_ALL);
if ($frm_side == "api") {
	$action = $_REQUEST['action'];
	if (!preg_match("#\.\./#",$action)
		AND preg_match("#^[-a-z0-9_.]+$#i", $action)
		AND file_exists("api/$action.php")) {
	    include("api/$action.php");
	}
} else {

	include("library/templates/header.php");
	//inkluderer side

	if (!preg_match("#\.\./#",$side)
		AND preg_match("#^[-a-z0-9_.]+$#i",$side)
		AND file_exists("$side")) {
	    include("$side");
	} else {
	  print '<h2 class="box error">Ugyldig side</h2>';
	}

	include("library/templates/footer.php");
}
