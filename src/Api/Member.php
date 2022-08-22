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
use NTNUI\Swimming\Exception\Api\AuthenticationException;
use NTNUI\Swimming\Util\Authenticator as Auth;
use NTNUI\Swimming\Util\Member;
use NTNUI\Swimming\Util\Response;

global $args;
$response = new Response();

try {
    $memberId = array_pop($args);
    $action = array_pop($args);
    $arg2 = array_pop($args);

    $response->data = match ($_SERVER["REQUEST_METHOD"]) {
        "GET" => match ($memberId) {
            // * GET /api/member/
            NULL => Auth::protect(
                protectedFunction: fn () => Member::getAllAsArray()
            ),

            // * GET /api/member/{memberId}
            (string)(int)$memberId => Auth::protect(
                protectedFunction: fn () => Member::fromId((int)$memberId)
            )->toArray(),

            // * GET /api/member/pending
            "pending" => Auth::protect(
                protectedFunction: fn () => Member::getAllInactiveAsArray()
            ),

            // * GET /api/member/active
            "active" => Auth::protect(
                protectedFunction: fn () => Member::getAllActiveAsArray()
            ),

            default => throw ApiException::endpointDoesNotExist(),
        },
        "POST" => match ($memberId) {
            // * POST /api/member
            NULL => Member::enroll(Response::getJsonInput()),

            // * POST /api/member/{memberId}/approve
            (string)(int)$memberId . "/approve" => Auth::protect(
                protectedFunction: fn () => Member::enrollmentApproveHandler((int)$memberId)
            ),

            // * POST /api/member/{memberId}/licenseForwarded
            (string)(int)$memberId . "/licenseForwarded" => Auth::protect(
                protectedFunction: fn () => Member::licenseHandler((int)$memberId)
            ),

            default => throw ApiException::endpointDoesNotExist(),
        },
        "PATCH" => match ($memberId) {
            // * PATCH /api/member/{memberId}/volunteering/{bool}
            (string)(int)$memberId . "/volunteering/" => Auth::protect(
                protectedFunction: fn () => Member::fromId((int)$memberId)->patchHandler(Response::getJsonInput())
            ),

            // * PATCH /api/member/{memberId}/cin/{cin}
            (string)(int)$memberId . "/cin/" => Auth::protect(
                protectedFunction: fn () => Member::fromId((int)$memberId)->patchHandler(Response::getJsonInput())
            ),

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
    ];
    if (boolval(filter_var($_ENV["DEBUG"], FILTER_VALIDATE_BOOLEAN))) {
        $response->data["message"] = $ex->getMessage();
        $response->data["code"] = $ex->getCode();
        $response->data["file"] = $ex->getFile();
        $response->data["line"] = $ex->getLine();
        $response->data["args"] = $args;
        $response->data["backtrace"] = $ex->getTrace();
    }
} catch (\Throwable $ex) {
    // TODO: import some logging solution
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
        $response->data["exception_class"] = get_class($ex);
        $response->data["backtrace"] = $ex->getTrace();
    }
}

$response->sendJson();
return;
