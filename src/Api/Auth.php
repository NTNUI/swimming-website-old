<?php

declare(strict_types=1);

namespace NTNUI\Swimming\Api;

use NTNUI\Swimming\App\Response;
use NTNUI\Swimming\Exception\Api\ApiException;
use NTNUI\Swimming\Interface\Endpoint;
use NTNUI\Swimming\Util\Authenticator;

/**
 * * GET /api/auth
 *
 * * POST /api/login
 * * POST /api/logout
 */

class Auth implements Endpoint
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

        $response->data = match ($requestMethod) {
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
                "login" => Authenticator::logInHandler($request["username"], $request["password"]),

                // * POST /api/auth/login
                "logout" => Authenticator::logOutHandler(),

                default => throw ApiException::endpointDoesNotExist(),
            },
            default => throw ApiException::methodNotAllowed(),
        };
        $response->code = empty($response->data) ? Response::HTTP_NOT_FOUND : Response::HTTP_OK;
        return $response;
    }
}
