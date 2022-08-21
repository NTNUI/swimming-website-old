<?php

declare(strict_types=1);

abstract class MemberException extends \Exception
{
    protected int $httpCode;
    /** @var string $message */
    protected $message;

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }
}
class MemberNotFoundException extends MemberException
{
    protected int $httpCode = Response::HTTP_NOT_FOUND;
    /** @var string $message */
    protected $message = "member not found";
}
class MemberIsActiveException extends MemberException
{
    protected int $httpCode = Response::HTTP_NOT_FOUND;
    /** @var string $message */
    protected $message = "member has already an active membership";
}
class CinNotFoundException extends MemberException
{
    protected int $httpCode = Response::HTTP_NOT_FOUND;
    /** @var string $message */
    protected $message = "customer identification number not found";
}
