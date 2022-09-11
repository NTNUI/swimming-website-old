<?php

declare(strict_types=1);

namespace NTNUI\Swimming\Util;

use NTNUI\Swimming\Exception\Api\AuthenticationException;
use NTNUI\Swimming\Exception\Api\UserException;
use NTNUI\Swimming\Util\Settings;
use NTNUI\Swimming\Util\User;

class Authenticator
{
    #region read

    public static function isLoggedIn(): bool
    {
        if (!isset($_SESSION)) {
            return false;
        }
        if (!array_key_exists("username", $_SESSION)) {
            return false;
        }
        return true;
    }

    public static function getUsername(): ?string
    {
        if (!self::isLoggedIn()) {
            return null;
        }
        return $_SESSION["username"];
    }

    public static function getName(): ?string
    {
        if (!self::isLoggedIn()) {
            return null;
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
        if (self::isLoggedIn()) {
            return [
                "error" => true,
                "success" => false,
                "message" => "user is currently logged in. Log out first before attempting another login.",
            ];
        }
        try {
            $user = User::fromUsername($username);
            if (!$user->verifyPassword($password)) {
                throw AuthenticationException::invalidCredentials();
            }
            $_SESSION["username"] = $user->username;

            return [
                "success" => true,
                "error" => false,
                "message" => "authentication successful",
                "username" => $user->username,
                "name" => $user->name,
            ];
        } catch (UserException $_) { // catch user not found
            throw AuthenticationException::invalidCredentials();
        }
    }

    /**
     * logOutHandler
     *
     * @throws AuthenticationException if user is already logged in
     * @return array{
     * success: true,
     * error: false,
     * message: string
     * }
     */
    public static function logOutHandler(): array
    {
        if (!self::isLoggedIn()) {
            throw AuthenticationException::unauthorized("already logged out");
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
    public static function protect(callable $protectedFunction): mixed
    {
        if (!self::isLoggedIn()) {
            throw AuthenticationException::unauthorized();
        }
        return $protectedFunction();
    }
    #endregion
};
