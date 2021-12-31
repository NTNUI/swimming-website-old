<?php

class AuthenticationException extends \Exception
{
    /**
     * Wrong credentials
     *
     * @param string $message
     * @return void
     */
    public static function WrongCredentials(string $message = "Wrong credentials")
    {
        return new static($message);
    }


}
