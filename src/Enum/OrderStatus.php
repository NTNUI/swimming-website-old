<?php

declare(strict_types=1);

namespace NTNUI\Swimming\Enum;

enum OrderStatus
{
    case PLACED;
    case FINALIZED;
    case DELIVERED;
    case FAILED;
    case REFUNDED;

    public static function fromString(string $orderStatus): self
    {
        $orderStatus = strtolower($orderStatus);
        return match ($orderStatus) {
            "placed" => self::PLACED,
            "finalized"  => self::FINALIZED,
            "delivered"  => self::DELIVERED,
            "failed"  => self::FAILED,
            "refunded"  => self::REFUNDED,
            default => throw new \Exception("order status cannot be created"),
        };
    }
    public function toString(): string
    {
        return match ($this) {
            self::PLACED => "PLACED",
            self::FINALIZED => "FINALIZED",
            self::DELIVERED => "DELIVERED",
            self::FAILED => "FAILED",
            self::REFUNDED => "REFUNDED",
        };
    }
};
