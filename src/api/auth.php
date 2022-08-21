<?php

declare(strict_types=1);

require_once(__DIR__ . "/../Library/Exceptions/Authentication.php");
require_once(__DIR__ . "/../Library/Util/Authenticator.php");
require_once(__DIR__ . "/../Library/Util/Api.php");

global $args;

/**
 * * GET /api/auth
 * 
 * * POST /api/login
 * * POST /api/logout
 */


$response = new Response();
try {
    $response->code = Response::HTTP_OK;
    $response->data = [
        "success" => true,
        "error" => false,
    ];
    $action = array_pop($args);

    $response->data = match ($_SERVER["REQUEST_METHOD"]) {
        "GET" => match ($action) {
            // * GET /api/auth
            NULL => [
                "isLoggedIn" => Authenticator::isLoggedIn(),
                "username" => Authenticator::getUsername(),
                "name" => Authenticator::getName(),
            ],

            default => throw new EndpointDoesNotExist()
        },
        "POST" => match ($action) {
            // * POST /api/auth/login
            "login" => Authenticator::logInHandler($_POST["username"], $_POST["password"]),

            // * POST /api/auth/login
            "logout" => Authenticator::logOutHandler(),

            default => throw new EndpointDoesNotExist(),
        },
        default => throw new MethodNotAllowedException(),
    };
    $response->code = empty($response->data) ? Response::HTTP_NOT_FOUND : Response::HTTP_OK;
} catch (AuthenticationException | ApiException $ex) {
    $response->code = $ex->getHttpCode();
    $response->data = [
        "success" => false,
        "error" => true,
        "message" => $ex->getMessage(),
        "args", $args,
    ];
} catch (\Throwable $ex) {
    $response->code = Response::HTTP_INTERNAL_SERVER_ERROR;
    $response->data = [
        "success" => false,
        "error" => true,
        "message" => "internal server error",
    ];
    if (boolval(filter_var($_ENV["DEBUG"], FILTER_VALIDATE_BOOLEAN))) {
        $response->data["message"] = $ex->getMessage();
        $response->data["code"] = $ex->getCode();
        $response->data["file"] = $ex->getFile();
        $response->data["line"] = $ex->getLine();
        $response->data["args"] = $args;
        $response->data["backtrace"] = $ex->getTrace();
    }
}

$response->sendJson();
return;
