<?php
// TODO: Add ability to disable crash alerts
// Usage: log::message("Something important happen", __FILE__, __LINE__);
// log will be visible on the root of the directory in php.log (settings are in .htaccess file)
// remember to pass __FILE__ and __LINE__ arguments
// Don't rely on log messages to be ordered in log! They will be from different users.
// Recommend putting it inside conditionals that will crash the site.

class log
{

    // log event
    static function message($message, $file = __FILE__, $line = __LINE__)
    {
        error_log(basename($file) . ":" . $line . " " . $message);
    }

    // Crash the server, write a log and alert the developer about the incident.
    static function die($message, $file = __FILE__, $line = __LINE__)
    {
        global $settings;
        header('HTTP/1.1 500 Internal Server Error');
        error_log(basename($file) . ":" . $line . " " . $message);
        error_log(print_r(debug_backtrace(), true));
        print("<script>alert('The site has crashed. The developers have now been informed and will resolve the issue as soon as possible. Sorry for the inconvenience.')</script>");
        mail(
            $settings["emails"]["developer"],
            "Error on web server",
            "Error occurred. Please check the logs. Have fun fixing bugs btw."
        );
        throw new Exception($message);
    }

    // Return 403 log the request and 
    static function forbidden($message, $file = __FILE__, $line = __LINE__)
    {
        header('HTTP/1.1 403 Forbidden');
        print($message);
        error_log(basename($file) . ":" . $line . " " . $message);
        error_log(print_r(debug_backtrace(), true));
        throw new Exception($message);
    }

    // Log and alert the user
    static function alert($message, $file = __FILE__, $line = __LINE__)
    {
        $message = htmlspecialchars($message);
        error_log(basename($file) . ":" . $line . " " . $message);
        print("<script>alert('$message')</script>");
    }
}
