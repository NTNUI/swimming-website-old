<?php

declare(strict_types=1);

namespace NTNUI\Swimming\Exception\Api;

use Lukasoppermann\Httpstatus\Httpstatuscodes;

final class CinException extends \Exception implements Httpstatuscodes
{
    public static function cinInvalid(string $message = "customer identification number is invalid. Cin has to be 8 digits long", int $code = self::HTTP_BAD_REQUEST): self
    {
        return new self($message, $code);
    }
    public static function cinNotFound(string $message = "customer identification number is not found", int $code = self::HTTP_NOT_FOUND): self
    {
        return new self($message, $code);
    }
}
