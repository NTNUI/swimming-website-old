<?php

declare(strict_types=1);

namespace NTNUI\Swimming\Exception\Api;

use Lukasoppermann\Httpstatus\Httpstatuscodes;

final class ProductException extends \Exception implements Httpstatuscodes
{
    public static function priceError(string $message = "cannot create a product with this price", int $code = self::HTTP_BAD_REQUEST): self
    {
        return new self($message, $code);
    }
    public static function productSoldOut(string $message = "product is sold out", int $code = self::HTTP_BAD_REQUEST): self
    {
        return new self($message, $code);
    }
    public static function productNotFound(string $message = "product not found", int $code = self::HTTP_BAD_REQUEST): self
    {
        return new self($message, $code);
    }
    public static function productNotAvailable(string $message = "this product cannot be purchased at this time", int $code = self::HTTP_BAD_REQUEST): self
    {
        return new self($message, $code);
    }
    public static function missingCustomerDetails(string $message = "customer details missing", int $code = self::HTTP_BAD_REQUEST): self
    {
        return new self($message, $code);
    }
    public static function addProductFailed(string $message = "failed to add product to store", int $code = self::HTTP_BAD_REQUEST): self
    {
        return new self($message, $code);
    }
    public static function modifyProduct(string $message = "failed to modify product", int $code = self::HTTP_BAD_REQUEST): self
    {
        return new self($message, $code);
    }
    public static function removeProductFailed(string $message = "failed to remove product from store", int $code = self::HTTP_BAD_REQUEST): self
    {
        return new self($message, $code);
    }
    public static function missingProductInformation(string $message = "missing product information", int $code = self::HTTP_BAD_REQUEST): self
    {
        return new self($message, $code);
    }
    public static function membershipRequired(string $message = "membership is required for this purchase", int $code = self::HTTP_BAD_REQUEST): self
    {
        return new self($message, $code);
    }
}
