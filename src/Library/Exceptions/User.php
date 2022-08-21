<?php

declare(strict_types=1);

abstract class UserException extends \Exception
{
    protected int $httpCode;
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }
}
class CreateException extends UserException
{
    /** @var string $message */
    protected $message = "could not create user";
}

class UserNotFoundException extends UserException
{
    /** @var string $message */
    protected $message = "user not found";
    protected int $httpCode = Response::HTTP_NOT_FOUND;
}

class LoginRequiredException extends UserException
{
    /** @var string $message */
    protected $message = "unauthorized: please log in";
    protected int $httpCode = Response::HTTP_UNAUTHORIZED;
}
