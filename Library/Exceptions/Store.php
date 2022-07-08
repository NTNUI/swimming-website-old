<?php

declare(strict_types=1);

abstract class StoreException extends \Exception
{
}

class PriceErrorException extends StoreException
{
}
class ProductNotEnabledException extends StoreException
{
    protected $message = "This product cannot be purchased at this time";
}
class ProductSoldOutException extends StoreException
{
    protected $message = "Product is sold out";
}
class ProductNotFoundException extends StoreException
{
}
class ProductNotAvailableException extends StoreException
{
    protected $message = "Current product is not available";
}
class MissingCustomerDetailsException extends StoreException
{
}
class MissingOrderDetailsException extends StoreException
{
}
class MaxOrdersExceededException extends StoreException
{
    protected $message = "Max orders of this product exceeded";
}
class CustomerIsNotMemberException extends StoreException
{
    protected $message = "Active membership is required for this purchase";
}
class OrderNotFoundException extends StoreException
{
}

class AddProductFailedException extends StoreException
{
}

class ModifyProductException extends StoreException
{
}
class RemoveProductFailedException extends StoreException
{
}
