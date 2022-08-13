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

class cinCreateException extends ModelException{
    /** @var string $message */
    protected $message = "cannot create cin";
}

