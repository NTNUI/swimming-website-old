<?php

declare(strict_types=1);

namespace NTNUI\Swimming\Exception\Api;

use Lukasoppermann\Httpstatus\Httpstatuscodes;

final class MemberException extends \Exception implements Httpstatuscodes
{
    public static function memberNotFound(string $message = "member not found", int $code = self::HTTP_NOT_FOUND): self
    {
        return new self($message, $code);
    }
    public static function memberIsActive(string $message = "member has an active membership", int $code = self::HTTP_BAD_REQUEST): self
    {
        return new self($message, $code);
    }
    public static function personalInformationInvalid(string $message = "invalid personal information", int $code = self::HTTP_BAD_REQUEST): self
    {
        return new self($message, $code);
    }
}
