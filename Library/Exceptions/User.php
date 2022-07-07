<?php

declare(strict_types=1);

class UserException extends \Exception
{
    
    /**
     * Throw when user creation fails. pass inn arguments in @param message to explain.
     * For instance "username already exists"
     *
     * @param string $message
     * @return void
     */
    public function CreateFailed(string $message)
    {
        return new static($message);
    }

    public static function NotFound(string $message = "User not found")
    {
        return new static($message);
    }

    public static function LoginRequired(string $message = "Login required"){
        return new static($message);
    }
}
