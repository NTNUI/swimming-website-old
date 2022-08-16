<?php

declare(strict_types=1);

require_once(__DIR__ . "/Api.php");

abstract class AuthenticationException extends \Exception
{
    /** @var string $message */
    protected $message;
    protected int $httpCode;
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }
}

class MissingCredentialsException extends AuthenticationException
{
    /** @var string $message */
    protected $message = "missing credentials";
    protected int $httpCode = Response::HTTP_UNAUTHORIZED;

}

class InvalidCredentialsException extends AuthenticationException
{
    /** @var string $message */
    protected $message = "invalid credentials";
    protected int $httpCode = Response::HTTP_UNAUTHORIZED;
}

class ForbiddenException extends AuthenticationException
{
    /** @var string $message */
    protected $message = "access denied";
    protected int $httpCode = Response::HTTP_FORBIDDEN;
}

class UnauthorizedException extends AuthenticationException
{
    /** @var string $message */
    protected $message = "Unauthorized. Please log in";
    protected int $httpCode = Response::HTTP_UNAUTHORIZED;
}
