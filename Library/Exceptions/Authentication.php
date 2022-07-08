<?php

declare(strict_types=1);

abstract class AuthenticationException extends \Exception
{
}

class InvalidCredentialsException extends AuthenticationException
{
}
