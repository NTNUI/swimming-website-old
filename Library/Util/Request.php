<?php

declare(strict_types=1);

function isValidURL(string $URLPart): bool
{
    // contains directory climbing pattern
    if (preg_match("#\.\./#", $URLPart)) {
        return false;
    }

    // Contains weird characters.
    if (!preg_match("#^[-a-zA-Z0-9_./]+$#i", $URLPart)) {
        return false;
    }

    return true;
}

// Returns a value is $key parameter is set. Returns NULL if parameter is not set.
function argsURL(string $type, string $key): ?string
{
    switch ($type) {
        case "REQUEST":
            if (!empty($_REQUEST[$key])) {
                return (string)$_REQUEST[$key];
            }
            break;
        case "SESSION":
            if (!empty($_SESSION[$key])) {
                return (string)$_SESSION[$key];
            }
            break;
        case "GET":
            if (!empty($_GET[$key])) {
                return (string)$_GET[$key];
            }
            break;
        case "POST":
            if (!empty($_POST[$key])) {
                return (string)$_POST[$key];
            }
            break;
        case "SESSION":
            if (!empty($_SESSION[$key])) {
                return (string)$_SESSION[$key];
            }
            break;
        case "SERVER":
            if (!empty($_SERVER[$key])) {
                return (string)$_SERVER[$key];
            }
            break;
        default:
            throw new \Exception("Wrong usage of argsURL");
    }
    return NULL;
}
