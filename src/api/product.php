<?php

declare(strict_types=1);

require_once("Library/Exceptions/Authentication.php");
require_once("Library/Exceptions/Api.php");
require_once("Library/Util/Authenticator.php");
require_once("Library/Util/Api.php");
require_once("Library/Util/Product.php");


/**
 * * GET /api/product
 * * GET /api/product/{productHash} 
 * 
 * * POST /api/product
 * 
 * * PATCH /api/product/{productHash}
 * 
 * * DELETE /api/product/{productHash}
 */


global $args;
$response = new Response();
try {
    $productHash = array_pop($args);
    if (!array_key_exists("REQUEST_METHOD", $_SERVER)) {
        throw new \Exception("request method does not exists");
    }
    $requestMethod = $_SERVER["REQUEST_METHOD"];
    if ($requestMethod !== "GET" && !Authenticator::isLoggedIn()) {
        throw new UnauthorizedException();
    }
    $response->data = match ($requestMethod) {
        "GET" => match ($productHash) {
            // * GET /api/product/
            NULL => Product::getAllAsArray(),

            // * GET /api/product/{productHash}
            (string)$productHash => Product::fromProductHash($productHash)->toArray(),

            default => throw new EndpointDoesNotExist(),
        },
        "POST" => match ($productHash) {
            // * POST /api/product
            NULL => Product::postHandler(json_decode(file_get_contents("php://input"), true, flags: JSON_THROW_ON_ERROR)),

            default => throw new EndpointDoesNotExist(),
        },
        "PATCH" => match ($productHash) {
            // * PATCH /api/product/{productHash}
            (string)$productHash => Product::fromProductHash($productHash)->patchHandler(json_decode(file_get_contents("php://input"), true, flags: JSON_THROW_ON_ERROR)),

            default => throw new EndpointDoesNotExist(),
        },
        "DELETE" => match ($productHash) {
            // * DELETE /api/product/{productHash}
            (string)$productHash => Product::fromProductHash($productHash)->deleteHandler(),

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
    ];
} catch (\Throwable $ex) {
    Log::message("Unexpected error");
    $response->code = Response::HTTP_INTERNAL_SERVER_ERROR;
    $response->data = [
        "success" => false,
        "error" => true,
        "message" => "internal server error"
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
