<?php

declare(strict_types=1);

require_once(__DIR__ . "/../Library/Exceptions/Authentication.php");
require_once(__DIR__ . "/../Library/Exceptions/Api.php");
require_once(__DIR__ . "/../Library/Exceptions/Member.php");
require_once(__DIR__ . "/../Library/Util/Authenticator.php");
require_once(__DIR__ . "/../Library/Util/Api.php");
require_once(__DIR__ . "/../Library/Util/Member.php");

/**
 * * GET /api/member
 * * GET /api/member/{memberId}
 * * GET /api/member/pending 
 * * GET /api/member/active 
 * 
 * * POST /api/member/
 * * POST /api/member/{memberId}/approve 
 * * POST /api/member/{memberId}/licenseForwarded
 *
 * * PATCH /api/member/{memberId}/volunteering/{bool}
 * * PATCH /api/member/{memberId}/cin/{cin}
 */


global $args;
$response = new Response();

try {
    $memberId = array_pop($args);
    $action = array_pop($args);
    $arg2 = array_pop($args);

    $response->data = match ($_SERVER["REQUEST_METHOD"]) {
        "GET" => match ($memberId) {
            // * GET /api/member/
            NULL => Member::getAllAsArray(),

            // * GET /api/member/{memberId}
            (string)(int)$memberId => Member::fromId((int)($memberId))->toArray(),

            // * GET /api/member/pending
            "pending" => Member::getAllInactiveAsArray(),

            // * GET /api/member/active
            "active" => Member::getAllActiveAsArray(),

            default => throw new EndpointDoesNotExist(),
        },
        "POST" => match ($memberId) {
            // * POST /api/member
            NULL => Member::enroll(Response::getJsonInput()),

            // * POST /api/member/{memberId}/approve
            (string)(int)$memberId . "/approve" => Member::fromId((int)$memberId)->approveEnrollment() ?? ["success" => true, "error" => false, "message" => "member approved successfully"],

            // * POST /api/member/{memberId}/licenseForwarded
            (string)(int)$memberId . "/licenseForwarded" => Member::fromId((int)$memberId)->setLicenseForwarded() ?? ["success" => true, "error" => false, "message" => "license forwarded has been set"],

            default => throw new EndpointDoesNotExist(),
        },
        "PATCH" => match ($memberId) {
            // * PATCH /api/member/{memberId}/volunteering/{bool}
            (string)(int)$memberId . "/volunteering/" => Member::fromId((int)$memberId)->patchHandler(Response::getJsonInput()),

            // * PATCH /api/member/{memberId}/cin/{cin}
            (string)(int)$memberId . "/cin/" => Member::fromId((int)$memberId)->patchHandler(Response::getJsonInput()),

            default => throw new EndpointDoesNotExist(),
        },
        default => throw new RequestMethodNotSupported(),
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
