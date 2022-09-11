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
 * * GET /api/user
 * * GET /api/user/{userId}
 *
 * * POST /api/user
 *
 * * PATCH /api/user/{userId}
 *
 * // * DELETE /api/user/{userId}
 */

class User implements Endpoint
{
    public static function run(string $requestMethod, array $args, array $request): Response
    {
        $response = new Response();

        $response->code = Response::HTTP_OK;
        $response->data = [
            "success" => true,
            "error" => false,
        ];
        if (!Authenticator::isLoggedIn()) {
            throw AuthenticationException::unauthorized();
        }

        $userId = array_pop($args);

        $response->data = match ($requestMethod) {
            "GET" => match ($userId) {
                // * GET /api/user
                NULL => Authenticator::protect(fn () => Util\User::getAllAsArray()),
    
                // * GET /api/user/{userId}
                (string)(int)$userId => Authenticator::protect(fn () => Util\User::fromId((int)$userId))->toArray(),
    
                default => throw ApiException::endpointDoesNotExist(),
            },
            "POST" => match ($userId) {
                // * POST /api/user
                NULL => Authenticator::protect(fn () => Util\User::postHandler(Response::getJsonInput())),
    
                default => throw ApiException::endpointDoesNotExist(),
            },
            "PATCH" => match ($userId) {
                // * PATCH /api/user/{userId}
                (string)(int)$userId => Authenticator::protect(fn () => Util\User::fromId((int)$userId)->patchHandler(Response::getJsonInput())),
    
                default => throw ApiException::endpointDoesNotExist(),
            },
            // "DELETE" => match ($userId) {
            // // * DELETE /api/user/{userId}
            // (string)(int)$userId => Authenticator::protect(fn () => Util\User::fromId((int)$userId)->deleteHandler()),
                //
            // 	default => throw ApiException::endpointDoesNotExist(),
            // },
    
            default => throw ApiException::methodNotAllowed(),
        };
        $response->code = empty($response->data) ? Response::HTTP_NOT_FOUND : Response::HTTP_OK;
        return $response;
    }
}
