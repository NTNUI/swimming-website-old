<?php

declare(strict_types=1);

namespace NTNUI\Swimming\Exception\Api;

use Lukasoppermann\Httpstatus\Httpstatuscodes;

final class AuthenticationException extends \Exception implements Httpstatuscodes
{
    public static function missingCredentials(string $message = "missing login credentials", int $code = self::HTTP_BAD_REQUEST): self
    {
        return new static($message, $code);
    }

    public static function invalidCredentials(string $message = "invalid credentials", int $code = self::HTTP_BAD_REQUEST): self
    {
        return new static($message, $code);
    }

    public static function unauthorized(string $message = "login required", int $code = self::HTTP_UNAUTHORIZED): self
    {
        return new static($message, $code);
    }
}
