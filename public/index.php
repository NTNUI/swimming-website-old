<?php

declare(strict_types=1);

error_reporting(E_ALL);

// Don't output errors to standard output. That breaks json formatting in API
ini_set("display_errors", "0");
ini_set("max_execution_time", "5"); // seconds
// Libraries
require_once(__DIR__ . "/../vendor/autoload.php");
require_once(__DIR__ . "/../Library/Util/Api.php");
require_once(__DIR__ . "/../Library/Util/Db.php");
require_once(__DIR__ . "/../Library/Util/Log.php");
require_once(__DIR__ . "/../Library/Util/Request.php");
require_once(__DIR__ . "/../Library/Util/Settings.php");

// Load settings and environments
$settings = Settings::getInstance(__DIR__ . "/../settings/settings.json");
$settings->testSettings();
$settings->initSession();

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->load();
$dotenv->required(
	[
		'STRIPE_SECRET_KEY',
		'STRIPE_PUBLISHABLE_KEY',
		'STRIPE_SIGNING_KEY',
		"DB_HOSTNAME",
		"DB_USERNAME",
		"DB_PASSWORD",
		"DB_DATABASE",
	]
)->notEmpty();

$args = array_filter(array_reverse(explode("/", $_SERVER["REQUEST_URI"])));

$service = array_pop($args);
$file = array_pop($args);

if ($service !== NULL) {
	if ($file === NULL) {
		$response = new Response();
		$response->code = Response::HTTP_NOT_FOUND;
		$response->data = [
			"error" => true,
			"success" => false,
			"message" => "please select a valid endpoint",
			"valid endpoints" => [
				str_replace(".php", "", str_replace("../api/", "", glob("../api/*.php")))
			],
		];
		$response->sendJson();
		return;
	}


	$questionMarkPosService = strpos($service, "?");
	if ($questionMarkPosService !== false) {
		$service = substr($service, 0, $questionMarkPosService);
	}
	$questionMarkPosFile = strpos($file, "?");
	if ($questionMarkPosFile !== false) {
		$file = substr($file, 0, $questionMarkPosFile);
	}
	if ($service === "api") {
		if (!file_exists("../api/$file.php")) {
			$response = new Response();
			$response->code = Response::HTTP_NOT_FOUND;
			$response->data = [
				"error" => true,
				"success" => false,
				"message" => "endpoint not found",
				"args" => $args,
				"service" => $service,
				"file" => "$file.php",
			];
			$response->sendJson();
			return;
		}
		require_once(__DIR__ . "/../api/$file.php");
		return;
	}
}

#echo file_get_contents("index.html");
return;
