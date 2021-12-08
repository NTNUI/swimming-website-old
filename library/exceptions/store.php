<?php

class StoreException extends \Exception
{
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

    public static function OrderNotFound(){
        return new static("Order not found");
    }

}
