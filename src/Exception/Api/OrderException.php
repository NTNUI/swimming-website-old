<?php

declare(strict_types=1);

namespace NTNUI\Swimming\Exception\Api;

use Lukasoppermann\Httpstatus\Httpstatuscodes;

final class OrderException extends \Exception implements Httpstatuscodes
{
    public static function maxOrdersExceeded(string $message ="max order exceeded for this customer", int $code = self::HTTP_BAD_REQUEST): self
    {
        return new self($message, $code);
    }

    public static function missingOrderDetails(string $message = "missing order details", int $code = self::HTTP_BAD_REQUEST): self
    {
        return new self($message, $code);
    }

    public static function orderNotFound(string $message = "order not found", int $code = self::HTTP_NOT_FOUND): self
    {
        return new self($message, $code);
    }
}
