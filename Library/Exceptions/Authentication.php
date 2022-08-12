<?php

declare(strict_types=1);

require_once("Library/Util/Api.php");

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
    protected int $httpCode = HTTP_UNAUTHORIZED;

}

class InvalidCredentialsException extends AuthenticationException
{
    /** @var string $message */
    protected $message = "invalid credentials";
    protected int $httpCode = HTTP_UNAUTHORIZED;
}

class ForbiddenException extends AuthenticationException
{
    /** @var string $message */
    protected $message = "access denied";
    protected int $httpCode = HTTP_FORBIDDEN;
}

class UnauthorizedException extends AuthenticationException
{
    /** @var string $message */
    protected $message = "Unauthorized. Please log in";
    protected int $httpCode = HTTP_UNAUTHORIZED;
}
