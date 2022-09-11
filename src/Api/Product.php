<?php

declare(strict_types=1);

namespace NTNUI\Swimming\Api;

use NTNUI\Swimming\Exception\Api\ApiException;
use NTNUI\Swimming\Exception\Api\AuthenticationException;
use NTNUI\Swimming\Util;
use NTNUI\Swimming\Util\Authenticator;
use NTNUI\Swimming\Util\Endpoint;
use NTNUI\Swimming\Util\Response;

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


class Product implements Endpoint
{
    public static function run(string $requestMethod, array $args, array $request): Response
    {
        $response = new Response();

        $response->code = Response::HTTP_OK;
        $response->data = [
            "success" => true,
            "error" => false,
        ];

        $action = array_pop($args);
        $productHash = array_pop($args);
        if ($requestMethod !== "GET" && !Authenticator::isLoggedIn()) {
            throw AuthenticationException::unauthorized();
        }
        $response->data = match ($requestMethod) {
            "GET" => match ($productHash) {
                // * GET /api/product/
                NULL => Util\Product::getAllAsArray(),
    
                // * GET /api/product/{productHash}
                (string)$productHash => Util\Product::fromProductHash($productHash)->toArray(),
    
                default => throw ApiException::endpointDoesNotExist(),
            },
            "POST" => match ($productHash) {
                // * POST /api/product
                NULL => Util\Product::postHandler(json_decode(file_get_contents("php://input"), true, flags: JSON_THROW_ON_ERROR)),
    
                default => throw ApiException::endpointDoesNotExist(),
            },
            "PATCH" => match ($productHash) {
                // * PATCH /api/product/{productHash}
                (string)$productHash => Util\Product::fromProductHash($productHash)->patchHandler(json_decode(file_get_contents("php://input"), true, flags: JSON_THROW_ON_ERROR)),
    
                default => throw ApiException::endpointDoesNotExist(),
            },
            "DELETE" => match ($productHash) {
                // * DELETE /api/product/{productHash}
                (string)$productHash => Util\Product::fromProductHash($productHash)->deleteHandler(),
    
                default => throw ApiException::endpointDoesNotExist(),
            },
            default => throw ApiException::methodNotAllowed(),
        };
        $response->code = empty($response->data) ? Response::HTTP_NOT_FOUND : Response::HTTP_OK;
        return $response;
    }
}
