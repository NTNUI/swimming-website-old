<?php

declare(strict_types=1);

require_once("Library/Util/Users.php");

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

global $args;
$response = new Response();
try {

	if (!Authenticator::isLoggedIn()) {
		throw new UnauthorizedException();
	}

	$userId = array_pop($args);

	$response->data = match ($_SERVER["REQUEST_METHOD"]) {
		"GET" => match ($userId) {
			// * GET /api/user
			NULL => Authenticator::protect(fn () => User::getAllAsArray()),

			// * GET /api/user/{userId}
			(string)(int)$userId => Authenticator::protect(fn () => User::fromId((int)$userId))->toArray(),

			default => throw new EndpointDoesNotExist(),
		},
		"POST" => match ($userId) {
			// * POST /api/user
			NULL => Authenticator::protect(fn () => User::postHandler(Response::getJsonInput())),

			default => throw new EndpointDoesNotExist(),
		},
		"PATCH" => match ($userId) {
			// * PATCH /api/user/{userId}
			(string)(int)$userId => Authenticator::protect(fn () => User::fromId((int)$userId)->patchHandler(Response::getJsonInput())),

			default => throw new EndpointDoesNotExist(),
		},
			// "DELETE" => match ($userId) {
			// // * DELETE /api/user/{userId}
			// (string)(int)$userId => Authenticator::protect(fn () => User::fromId((int)$userId)->deleteHandler()),
			//
			// 	default => throw new EndpointDoesNotExist(),
			// },

		default => throw new MethodNotAllowedException(),
	};

	$response->code = empty($response->data) ? Response::HTTP_NOT_FOUND : Response::HTTP_OK;
} catch (ApiException $ex) {
	$response->code = Response::HTTP_BAD_REQUEST;
	$response->data = [
		"success" => false,
		"error" => true,
		"message" => $ex->getMessage(),
	];
} catch (AuthenticationException $ex) {
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
		"message" => "internal server error",
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
