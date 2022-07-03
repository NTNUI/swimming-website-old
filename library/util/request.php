<?php

declare(strict_types=1);

function isValidURL($URL_part)
{
    // contains directory climbing pattern
    if (preg_match("#\.\./#", $URL_part)) {
        return false;
    }

    // Contains weird charactars.
    if (!preg_match("#^[-a-zA-Z0-9_.]+$#i", $URL_part)) {
        return false;
    }

    return true;
}

// Returns a value is $key parameter is set. Returns NULL if parameter is not set.
function argsURL($type, $key)
{
    switch ($type) {
        case "REQUEST":
            if (isset($_REQUEST[$key])) {
                return $_REQUEST[$key];
            }
            break;
        case "SESSION":
            if (isset($_SESSION[$key])) {
                return $_SESSION[$key];
            }
            break;
        case "GET":
            if (isset($_GET[$key])) {
                return $_GET[$key];
            }
            break;
        case "POST":
            if (isset($_POST[$key])) {
                return $_POST[$key];
            }
            break;
        default:
            die("Wrong usage of argsURL");
    }
    return NULL;
}
