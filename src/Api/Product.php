<?php

declare(strict_types=1);

namespace NTNUI\Swimming\Api;

use Webmozart\Assert\Assert;
use NTNUI\Swimming\Util\Product;
use NTNUI\Swimming\Util\Response;
use NTNUI\Swimming\Util\Authenticator;
use NTNUI\Swimming\Exception\Api\ApiException;
use NTNUI\Swimming\Exception\Api\ProductException;
use NTNUI\Swimming\Exception\Api\AuthenticationException;

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
    Assert::keyExists($_SERVER, "REQUEST_METHOD");
    $requestMethod = $_SERVER["REQUEST_METHOD"];
    if ($requestMethod !== "GET" && !Authenticator::isLoggedIn()) {
        throw AuthenticationException::unauthorized();
    }
    $response->data = match ($requestMethod) {
        "GET" => match ($productHash) {
            // * GET /api/product/
            NULL => Product::getAllAsArray(),

            // * GET /api/product/{productHash}
            (string)$productHash => Product::fromProductHash($productHash)->toArray(),

            default => throw ApiException::endpointDoesNotExist(),
        },
        "POST" => match ($productHash) {
            // * POST /api/product
            NULL => Product::postHandler(json_decode(file_get_contents("php://input"), true, flags: JSON_THROW_ON_ERROR)),

            default => throw ApiException::endpointDoesNotExist(),
        },
        "PATCH" => match ($productHash) {
            // * PATCH /api/product/{productHash}
            (string)$productHash => Product::fromProductHash($productHash)->patchHandler(json_decode(file_get_contents("php://input"), true, flags: JSON_THROW_ON_ERROR)),

            default => throw ApiException::endpointDoesNotExist(),
        },
        "DELETE" => match ($productHash) {
            // * DELETE /api/product/{productHash}
            (string)$productHash => Product::fromProductHash($productHash)->deleteHandler(),

            default => throw ApiException::endpointDoesNotExist(),
        },
        default => throw ApiException::methodNotAllowed(),
    };

    $response->code = empty($response->data) ? Response::HTTP_NOT_FOUND : Response::HTTP_OK;
} catch (AuthenticationException | ProductException | ApiException $ex) {
    $response->code = $ex->getCode();
    $response->data = [
        "success" => false,
        "error" => true,
        "message" => $ex->getMessage(),
    ];
} catch (\Throwable $ex) {
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
