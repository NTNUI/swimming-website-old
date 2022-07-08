<?php

declare(strict_types=1);

abstract class UserException extends \Exception
{
}
class CreateException extends UserException
{
}

class UserNotFoundException extends UserException
{
}

class LoginRequiredException extends UserException
{
}
