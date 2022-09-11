<?php

declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";

use Dotenv\Dotenv;
use Maknz\Slack\Client;
use NTNUI\Swimming\Util\Router;
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
        "SLACK_ENABLE",
        "SLACK_WEBHOOK_URL",
        "SLACK_CHANNEL_CRASH",
        "SLACK_CHANNEL_STATUS",
        "SLACK_USERNAME",
    ]
)->notEmpty();

// Load settings and environments
$settings = Settings::getInstance(__DIR__ . "/../settings.json");
// uncomment following line to test configuration.
// $settings->testSettings();
$settings->initSession();

$router = new Router(
    requestMethod: $_SERVER["REQUEST_METHOD"],
    request: $_REQUEST,
    pathIndexHtml: __DIR__ . "/../public/index.html",
    slack: new Client([$_ENV["SLACK_WEBHOOK_URL"]], [
        "username" => $_ENV["SLACK_USERNAME"],
        "channel" => $_ENV["SLACK_CHANNEL_STATUS"],
        "link_names" => true
    ]),
);

// Note: this function always returns index.html unless request uri is pointing to the API then it always returns a valid json object.
$router->run($_SERVER["REQUEST_URI"])->sendJson();
return;
