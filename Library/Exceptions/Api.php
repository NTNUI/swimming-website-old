<?php


abstract class ApiException extends \Exception
{
    /** @var string $message */
    protected $message;
    protected int $httpCode;
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }
}

class InvalidRequestException extends ApiException
{
    /** @var string $message */
    protected $message = "invalid request";
    protected int $httpCode = HTTP_INVALID_REQUEST;
}

class EndpointDoesNotExist extends ApiException
{
    /** @var string $message */
    protected $message = "this endpoint does not exist";
    protected int $httpCode = HTTP_INVALID_REQUEST;
}

class MethodNotAllowedException extends ApiException
{
    /** @var string $message */
    protected $message = "method not allowed";
    protected int $httpCode = HTTP_METHOD_NOT_ALLOWED;
}

class NotImplementedException extends ApiException
{
    /** @var string $message */
    protected $message = "endpoint not implemented";
    protected int $httpCode = HTTP_NOT_IMPLEMENTED;
}
class RequestMethodNotSupported extends ApiException
{
    /** @var string $message */
    protected $message = "request method not supported";
    protected int $httpCode = HTTP_METHOD_NOT_ALLOWED;
}
