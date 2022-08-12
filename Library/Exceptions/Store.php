<?php

declare(strict_types=1);

abstract class StoreException extends \Exception
{
    protected int $httpCode;
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }
}

class PriceErrorException extends StoreException
{
    /** @var string $message */
    protected $message = "price is not correct";
}
class ProductNotEnabledException extends StoreException
{
    /** @var string $message */
    protected $message = "this product cannot be purchased at this time";
}
class ProductSoldOutException extends StoreException
{
    /** @var string $message */
    protected $message = "product is sold out";
}
class ProductNotFoundException extends StoreException
{
    /** @var string $message */
    protected $message = "product not found";
    protected int $httpCode = Response::HTTP_NOT_FOUND;
}
class ProductNotAvailableException extends StoreException
{
    /** @var string $message */
    protected $message = "current product is not available";
}
class MissingCustomerDetailsException extends StoreException
{
    /** @var string $message */
    protected $message = "missing required customer information";
}


class AddProductFailedException extends StoreException
{
    /** @var string $message */
    protected $message = "failed to add product";
}

class ModifyProductException extends StoreException
{
    /** @var string $message */
    protected $message = "failed to modify product";
}
class RemoveProductFailedException extends StoreException
{
    /** @var string $message */
    protected $message = "failed to remove product";
}
