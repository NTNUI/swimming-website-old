<?php

declare(strict_types=1);

require_once("Library/Exceptions/Authentication.php");
require_once("Library/Util/Authenticator.php");
require_once("Library/Util/Api.php");

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
        "args" => $args,
    ];
}

$response->sendJson();
return;
