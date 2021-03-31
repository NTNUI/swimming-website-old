<?php
// start session
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


$base_url = $settings["hosting"]["baseurl"];


//Get request
$language = $_REQUEST["lang"];
$frm_side = $_REQUEST["side"];

// Defaults
if ($language == "") $language = $settings["defaults"]["language"];
if ($frm_side == "") $frm_side = $settings["defaults"]["landing-page"];

$side = "$frm_side.php";

//Translator
$translations_dir = "translations";
$t = new Translator($frm_side, $language);

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
		AND file_exists("public/$side")) {
	    include("public/$side");
	} else {
	  print '<h2 class="box error">Ugyldig side</h2>';
	}

	include("library/templates/footer.php");
}
