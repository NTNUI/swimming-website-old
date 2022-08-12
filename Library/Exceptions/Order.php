<?php

declare(strict_types=1);

abstract class OrderException extends \Exception
{
    /** @var string $message */
    protected $message;
    protected int $httpCode;

    public function getHttpCode():int{
        return $this->httpCode;
    }
}

class MaxOrdersExceededException extends OrderException
{
    /** @var string $message */
    protected $message = "this customer has reached upper limit of purchases for this product";
}

class MissingOrderDetailsException extends OrderException
{
    /** @var string $message */
    protected $message = "missing required order information";
}
class CustomerIsNotMemberException extends OrderException
{
    /** @var string $message */
    protected $message = "active membership is required for this purchase";
}
class OrderNotFoundException extends OrderException
{
    /** @var string $message */
    protected $message = "order not found";
    protected int $httpCode = Response::HTTP_NOT_FOUND;
}