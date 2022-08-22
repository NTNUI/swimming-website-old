<?php

declare(strict_types=1);

namespace NTNUI\Swimming\Exception\Api;

use Lukasoppermann\Httpstatus\Httpstatuscodes;

final class EnrollmentException extends \Exception implements Httpstatuscodes
{
    public static function closed(string $message = "enrolment is currently closed", int $code = self::HTTP_METHOD_NOT_ALLOWED): self
    {
        return new static($message, $code);
    }
}

