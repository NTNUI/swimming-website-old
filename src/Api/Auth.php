<?php

declare(strict_types=1);

namespace NTNUI;

use NTNUI\Swimming\Util\Response;
use NTNUI\Swimming\Util\Authenticator;
use NTNUI\Swimming\Exception\Api\ApiException;
use NTNUI\Swimming\Exception\Api\AuthenticationException;

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

            default => throw ApiException::endpointDoesNotExist(),
        },
        "POST" => match ($action) {
            // * POST /api/auth/login
            "login" => Authenticator::logInHandler($_POST["username"], $_POST["password"]),

            // * POST /api/auth/login
            "logout" => Authenticator::logOutHandler(),

            default => throw ApiException::endpointDoesNotExist(),
        },
        default => throw ApiException::methodNotAllowed(),
    };
    $response->code = empty($response->data) ? Response::HTTP_NOT_FOUND : Response::HTTP_OK;
} catch (AuthenticationException | ApiException $ex) {
    $response->code = $ex->getCode();
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
