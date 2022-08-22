<?php

declare(strict_types=1);

namespace NTNUI\Swimming\Exception\Api;

use Lukasoppermann\Httpstatus\Httpstatuscodes;

final class UserException extends \Exception implements Httpstatuscodes
{
    public static function failedToCreateUser(string $message = "failed to create a new user", int $code = self::HTTP_BAD_REQUEST): self
    {
        return new self($message, $code);
    }
    public static function userNotFound(string $message = "user not found", int $code = self::HTTP_NOT_FOUND): self
    {
        return new self($message, $code);
    }
    public static function userModifyException(string $message = "cannot modify user", int $code = self::HTTP_BAD_REQUEST): self
    {
        return new self($message, $code);
    }
}
