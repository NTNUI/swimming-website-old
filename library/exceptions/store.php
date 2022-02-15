<?php

class StoreException extends \Exception
{
    public static function PriceError($message = "Price is wrong. Contact admin")
    {
        return new static($message);
    }
    public static function ProductNotEnabled($message = "Product is disabled")
    {
        return new static($message);
    }
    public static function ProductSoldOut()
    {
        return new static("Product is sold out");
    }
    public static function ProductNotFound()
    {
        return new static("Product does not exists");
    }
    public static function ProductNotAvailable(string $message = "Product is not available")
    {
        return new static($message);
    }
    public static function MissingCustomerDetails(string $message = "Missing customer details")
    {
        return new static($message);
    }
    public static function MissingOrderDetails(string $message = "Missing order details")
    {
        return new static($message);
    }
    public static function MaxOrdersExceeded(string $message = "Customer cannot purchase more of this product")
    {
        return new static($message);
    }
    public static function CustomerIsNotMember($message = "This product can only be purchased by active members")
    {
        return new static($message);
    }
    public static function OrderNotFound()
    {
        return new static("Order not found");
    }

    public static function AddProductFailed(string $message = "Could not add a new product")
    {
        return new static($message);
    }

    public static function RemoveProductFailed(string $message = "Could not remove product")
    {
        return new static($message);
    }
}
