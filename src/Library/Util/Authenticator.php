<?php

declare(strict_types=1);

require_once(__DIR__ . "/../Exceptions/User.php");
require_once(__DIR__ . "/../Exceptions/Authentication.php");
require_once(__DIR__ . "/User.php");

class Authenticator
{
    #region read

    static public function isLoggedIn(): bool
    {
        if (!isset($_SESSION)) {
            return false;
        }
        if (!array_key_exists("username", $_SESSION)) {
            return false;
        }
        return true;
    }

    static public function getUsername(): ?string
    {
        if (!Authenticator::isLoggedIn()) {
            return NULL;
        }
        return $_SESSION["username"];
    }

    static public function getName(): ?string
    {
        if (!Authenticator::isLoggedIn()) {
            return NULL;
        }
        return User::fromUsername($_SESSION["username"])->name;
    }

    #endregion

    #region write

    /**
     * logInHandler
     *
     * @param string $username
     * @param string $password
     * @throws InvalidCredentialsException
     * @return array{
     * success:true,
     * error:false,
     * message:string,
     * username:string,
     * name:string
     * }|array{
     * error:true,
     * success:false,
     * message:string
     * }
     */
    public static function logInHandler(string $username, string $password): array
    {
        if (Authenticator::isLoggedIn()) {
            return [
                "error" => true,
                "success" => false,
                "message" => "user is currently logged in. Log out first before attempting another login.",
            ];
        }
        try {
            $user = User::fromUsername($username);
            if (!$user->verifyPassword($password)) {
                throw new InvalidCredentialsException();
            }
            $_SESSION["username"] = $user->username;

            return [
                "success" => true,
                "error" => false,
                "message" => "authentication successful",
                "username" => $user->username,
                "name" => $user->name,
            ];
        } catch (UserNotFoundException $_) {
            throw new InvalidCredentialsException();
        }
    }

    /**
     * logOutHandler
     *
     * @throws UnauthorizedException
     * @return array{
     * success: true,
     * error: false,
     * message: string
     * }
     */
    static public function logOutHandler(): array
    {
        if (!Authenticator::isLoggedIn()) {
            throw new UnauthorizedException("already logged out");
        }
        Settings::getInstance()->sessionDestroy();
        return [
            "success" => true,
            "error" => false,
            "message" => "log out successful",
        ];
    }
    /**
     * Protect a function call from being run by unauthorized users
     *
     * @param callable $protectedFunction
     * @return mixed
     */
    static public function protect(callable $protectedFunction): mixed
    {
        if (!self::isLoggedIn()) {
            throw new UnauthorizedException();
        }
        return $protectedFunction();
    }
    #endregion
};
