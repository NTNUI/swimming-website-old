<?php

declare(strict_types=1);

namespace NTNUI\Swimming\Api;

use NTNUI\Swimming\App\Response;
use NTNUI\Swimming\App\Models\User as UserModel;
use NTNUI\Swimming\Db;
use NTNUI\Swimming\Exception\Api\ApiException;
use NTNUI\Swimming\Exception\Api\AuthenticationException;
use NTNUI\Swimming\Interface\Endpoint;
use NTNUI\Swimming\Util\Authenticator;

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
        // if (!Authenticator::isLoggedIn()) {
        //     throw AuthenticationException::unauthorized();
        // }

        $userId = array_pop($args);

        $response->data = match ($requestMethod) {
            "GET" => match ($userId) {
                // * GET /api/user
                // TODO: authenticate
                NULL => UserModel::all()->toArray(),

                // * GET /api/user/{userId}
                (string)(int)$userId => UserModel::where("id", (int)$userId)->get()->toArray(),

                default => throw ApiException::endpointDoesNotExist(),
            },
            "POST" => match ($userId) {
                // * POST /api/user
                NULL => (function () {
                    UserModel::new_from_json(Response::getJsonInput());
                    return [];
                })(),

                default => throw ApiException::endpointDoesNotExist(),
            },
            "PATCH" => match ($userId) {
                // * PATCH /api/user/{userId}
                (string)(int)$userId => UserModel::where("id", (int)$userId)->update([
                    "name" => Response::getJsonInput()["name"],
                    "username" => Response::getJsonInput()["username"],
                    "password_hash" => password_hash(Response::getJsonInput()["password"], \PASSWORD_DEFAULT),
                ]) || [],

                default => throw ApiException::endpointDoesNotExist(),
            },
            "DELETE" => match ($userId) {
                // * DELETE /api/user/{userId}
                (string)(int)$userId => UserModel::where("id", (int)$userId)->delete() || [],

                default => throw ApiException::endpointDoesNotExist(),
            },

            default => throw ApiException::methodNotAllowed(),
        };
        $response->code = empty($response->data) ? Response::HTTP_NOT_FOUND : Response::HTTP_OK;
        return $response;
    }
}
