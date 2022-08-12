<?php

declare(strict_types=1);

error_reporting(E_ALL);

// Don't output errors to standard output. That breaks json formatting in API
ini_set("display_errors", "0");
ini_set("max_execution_time", "5"); // seconds
// Libraries
require_once("Library/Util/Api.php");
require_once("Library/Util/Db.php");
require_once("Library/Util/Log.php");
require_once("Library/Util/Request.php");
require_once("Library/Util/Settings.php");
require_once("vendor/autoload.php");

// Load settings and environments
$settings = Settings::getInstance("./settings/settings.json");
$settings->testSettings();
$settings->initSession();

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$dotenv->required(
	[
		'STRIPE_SECRET_KEY',
		'STRIPE_PUBLISHABLE_KEY',
		'STRIPE_SIGNING_KEY',
		"DB_HOSTNAME",
		"DB_USERNAME",
		"DB_PASSWORD"
	]
)->notEmpty();

$args = array_filter(array_reverse(explode("/", $_SERVER["REQUEST_URI"])));
$service = array_pop($args);

if ($page === "api") {
	// strip get arguments
	$questionMarkPos = strpos($service, "?");
	$file = "";
	if ($questionMarkPos !== false) {
		// get argument present, extract path before GET arguments
		$file = ucfirst(substr($service, 0, $questionMarkPos));
	} else {
		$file = ucfirst($service);
	}
	if (!file_exists("Private/Api/$file.php")) {
		$response = new Response();
		$response->code = Response::HTTP_NOT_FOUND;
		$response->data = [
			"error" => true,
			"success" => false,
			"message" => "endpoint not found",
			"args" => $args,
			"service" => $service,
			"file" => $file,
		];
		$response->sendJson();
		return;
	}
	return;
}
// somehow redirect to public
echo "public.html will be loaded here";
return;