<?php

declare(strict_types=1);
abstract class ModelException extends \Exception
{
    /** @var string $message */
    protected $message;
    protected int $httpCode;
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }
}

class cinInvalidException extends ModelException{
    /** @var string $message */
    protected $message = "cin is not 8 digits";
}

