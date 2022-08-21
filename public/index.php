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
		"BASE_URL",
		"LICENSE_PRODUCT_HASH",
	]
)->notEmpty();

// Load settings and environments
$settings = Settings::getInstance(__DIR__ . "/../settings/settings.json");
$settings->testSettings();
$settings->initSession();
$args = array_filter(array_reverse(explode("/", $_SERVER["REQUEST_URI"])));

$page = array_pop($args);

if ($page === "api") {
	$service = array_pop($args);
	$validEndpoints = str_replace(".php", "", str_replace(__DIR__ . "/../api/", "", glob(__DIR__ . "/../api/*.php")));
	// $service might contain get arguments like /api/service?foo=bar&hello=world

	$questionMarkPos = strpos($service, "?");
	if ($questionMarkPos !== false) {
		$service = substr($service, 0, $questionMarkPos);
	} // we don't need to parse get arguments since they are already available through $_GET

	if (!in_array($service, $validEndpoints)) {
		$response = new Response();
		$response->code = Response::HTTP_NOT_FOUND;
		$response->data = [
			"error" => true,
			"success" => false,
			"message" => "please select a valid endpoint",
			"currentEndpoint" => $service,
			"validEndpoints" => $validEndpoints,
		];
		$response->sendJson();
		return;
	}
	require_once(__DIR__ . "/../api/$service.php");
	return;
}
// echo "didn't work :/";
echo file_get_contents("index.html");
return;
