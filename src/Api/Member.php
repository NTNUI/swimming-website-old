<?php

declare(strict_types=1);

namespace NTNUI\Swimming\Api;

/**
 * * GET /api/member
 * * GET /api/member/{memberId}
 * * GET /api/member/pending
 * * GET /api/member/active
 *
 * * POST /api/member/ UNPROTECTED
 * * POST /api/member/{memberId}/approve
 * * POST /api/member/{memberId}/licenseForwarded
 *
 * * PATCH /api/member/{memberId}/volunteering/{bool}
 * * PATCH /api/member/{memberId}/cin/{cin}
 */

use NTNUI\Swimming\Exception\Api\ApiException;
use NTNUI\Swimming\Util;
use NTNUI\Swimming\Util\Authenticator as Auth;
use NTNUI\Swimming\Util\Endpoint;
use NTNUI\Swimming\Util\Response;

class Member implements Endpoint
{
    public static function run(string $requestMethod, array $args, array $request): Response
    {
        $response = new Response();

        $response->code = Response::HTTP_OK;
        $response->data = [
            "success" => true,
            "error" => false,
        ];

        $memberId = array_pop($args);
        $response->data =  match ($requestMethod) {
            "GET" => match ($memberId) {
                // * GET /api/member/
                NULL => Auth::protect(
                    protectedFunction: fn () => Util\Member::getAllAsArray()
                ),

                // * GET /api/member/{memberId}
                (string)(int)$memberId => Auth::protect(
                    protectedFunction: fn () => Util\Member::fromId((int)$memberId)
                )->toArray(),

                // * GET /api/member/pending
                "pending" => Auth::protect(
                    protectedFunction: fn () => Util\Member::getAllInactiveAsArray()
                ),

                // * GET /api/member/active
                "active" => Auth::protect(
                    protectedFunction: fn () => Util\Member::getAllActiveAsArray()
                ),

                default => throw ApiException::endpointDoesNotExist(),
            },
            "POST" => match ($memberId) {
                // * POST /api/member
                NULL => Util\Member::enroll(Response::getJsonInput()),

                // * POST /api/member/{memberId}/approve
                (string)(int)$memberId . "/approve" => Auth::protect(
                    protectedFunction: fn () => Util\Member::enrollmentApproveHandler((int)$memberId)
                ),

                // * POST /api/member/{memberId}/licenseForwarded
                (string)(int)$memberId . "/licenseForwarded" => Auth::protect(
                    protectedFunction: fn () => Util\Member::licenseHandler((int)$memberId)
                ),

                default => throw ApiException::endpointDoesNotExist(),
            },
            "PATCH" => match ($memberId) {
                // * PATCH /api/member/{memberId}/volunteering/{bool}
                (string)(int)$memberId . "/volunteering/" => Auth::protect(
                    protectedFunction: fn () => Util\Member::fromId((int)$memberId)->patchHandler(Response::getJsonInput())
                ),

                // * PATCH /api/member/{memberId}/cin/{cin}
                (string)(int)$memberId . "/cin/" => Auth::protect(
                    protectedFunction: fn () => Util\Member::fromId((int)$memberId)->patchHandler(Response::getJsonInput())
                ),

                default => throw ApiException::endpointDoesNotExist(),
            },
            default => throw ApiException::methodNotAllowed(),
        };
        $response->code = empty($response->data) ? Response::HTTP_NOT_FOUND : Response::HTTP_OK;
        return $response;
    }
}
