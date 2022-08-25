<?php

declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";

use Dotenv\Dotenv;
use NTNUI\Swimming\Util\Response;
use NTNUI\Swimming\Util\Settings;

error_reporting(E_ALL);
ini_set("display_errors", "0");
ini_set("max_execution_time", "5"); // seconds

$dotenv = Dotenv::createImmutable(__DIR__ . "/..");
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
$settings = Settings::getInstance(__DIR__ . "/../settings.json");
$settings->testSettings();
$settings->initSession();
$args = array_filter(array_reverse(explode("/", $_SERVER["REQUEST_URI"])));

$page = array_pop($args);

if ($page !== "api") {
    echo file_get_contents(__DIR__ . "/../public/index.html");
    return;
}

// handle api request

$service = array_pop($args);
$validEndpoints = str_replace(".php", "", str_replace(__DIR__ . "/Api/", "", glob(__DIR__ . "/Api/*.php")));
// $service might contain get arguments like /api/service?foo=bar&hello=world


// what if $service does not exists? eg GET /api/
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
require_once __DIR__ . "/api/$service.php";
return;
