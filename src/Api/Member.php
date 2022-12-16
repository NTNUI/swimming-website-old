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

use Carbon\Carbon;
use NTNUI\Swimming\App\Response;
use NTNUI\Swimming\App\Models\Member as MemberModel;
use NTNUI\Swimming\Exception\Api\ApiException;
use NTNUI\Swimming\Interface\Endpoint;

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
        $action = array_pop($args);
        $response->data =  match ($requestMethod) {
            "GET" => match ($memberId) {
                // TODO: protect endpoint
                // * GET /api/member/
                NULL => MemberModel::all()->toArray(),

                // TODO: protect endpoint
                // * GET /api/member/{memberId}
                (string)(int)$memberId => MemberModel::where("id", (int)$memberId)->get()->toArray(),

                // TODO: protect endpoint
                // * GET /api/member/pending
                "pending" => MemberModel::where("approved_at", NULL)->get()->toArray(),
                "inactive" => MemberModel::where("approved_at", NULL)->get()->toArray(),

                // TODO: protect endpoint
                // * GET /api/member/active
                "active" => MemberModel::whereNotNull("approved_at")->get()->toArray(),
                "approved" => MemberModel::whereNotNull("approved_at")->get()->toArray(),

                default => throw ApiException::endpointDoesNotExist(),
            },
            "POST" => match ($memberId) {
                // * POST /api/member
                NULL => MemberModel::register(Response::getJsonInput()),

                (string)(int)$memberId => match ($action) {
                    // TODO: protect endpoint
                    // * POST /api/member/{memberId}/approve
                    "approve" => (function ($memberId) {
                        MemberModel::where("id", (int)$memberId)?->update(["approved_at" => Carbon::now()]);
                        return [];
                    })($memberId),

                    // TODO: protect endpoint
                    // * POST /api/member/{memberId}/licenseForwarded
                    "licenseForwarded" => (function ($memberId) {
                        MemberModel::where("id", (int)$memberId)?->update(["license_forwarded_at" => Carbon::now()]);
                        return [];
                    })($memberId),
                    default => throw ApiException::endpointDoesNotExist(),
                },

                default => throw ApiException::endpointDoesNotExist(),
            },
            "PATCH" => match ($memberId) {

                (string)(int)$memberId => match ($action) {
                    // TODO: protect endpoint
                    // * PATCH /api/member/{memberId}/volunteering/{bool}
                    "volunteering" => (function ($memberId, $args) {
                        match (array_pop($args)) {
                            "true" => MemberModel::where("id", (int)$memberId)?->update(["have_volunteered" => true]),
                            "1" => MemberModel::where("id", (int)$memberId)?->update(["have_volunteered" => true]),
                            "TRUE" => MemberModel::where("id", (int)$memberId)?->update(["have_volunteered" => true]),
                            "false" => MemberModel::where("id", (int)$memberId)?->update(["have_volunteered" => false]),
                            "FALSE" => MemberModel::where("id", (int)$memberId)?->update(["have_volunteered" => false]),
                            "0" => MemberModel::where("id", (int)$memberId)?->update(["have_volunteered" => false]),
                            default => throw ApiException::invalidRequest("missing argument bool [true/false]"),
                        };

                        return [];
                    })($memberId, $args),

                    // * PATCH /api/member/{memberId}/cin/{cin}
                    "cin" => (function ($memberId, $args) {
                        $cin = array_pop($args);
                        MemberModel::where("id", (int)$memberId)?->set_cin($cin);
                        return [];
                    })($memberId, $args),

                    default => throw ApiException::endpointDoesNotExist(),
                },
                default => throw ApiException::endpointDoesNotExist(),
            },
            default => throw ApiException::methodNotAllowed(),
        };
        return $response;
    }
}
