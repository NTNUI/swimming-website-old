<?php

declare(strict_types=1);

namespace NTNUI\Swimming\Exception\Api;

use Lukasoppermann\Httpstatus\Httpstatuscodes;

final class ApiException extends \Exception implements Httpstatuscodes
{
    public static function patchNotAllowed(string $message = "patch request not allowed", int $code = self::HTTP_METHOD_NOT_ALLOWED): self
    {
        return new static($message, $code);
    }
    public static function invalidRequest(string $message = "invalid request", int $code = self::HTTP_BAD_REQUEST): self
    {
        return new static($message, $code);
    }
    public static function notImplemented(string $message = "this feature is not implemented yet", int $code = self::HTTP_NOT_IMPLEMENTED): self
    {
        return new static($message, $code);
    }
    public static function endpointDoesNotExist(string $message = "endpoint does not exist", int $code = self::HTTP_BAD_REQUEST): self
    {
        return new static($message, $code);
    }
    public static function methodNotAllowed(string $message = "method not allowed", int $code = self::HTTP_METHOD_NOT_ALLOWED): self
    {
        return new static($message, $code);
    }
}
