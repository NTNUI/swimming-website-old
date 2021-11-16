<?php
// TODO: Add ability to disable crash alerts
// Usage: log::message("Something important happen", __FILE__, __LINE__);
// log will be visible on the root of the directory in php.log (settings are in .htaccess file)
// remember to pass __FILE__ and __LINE__ arguments
// Don't rely on log messages to be ordered in log! They will be from different users.
// Recommend putting it inside conditionals that will crash the site.
// TODO: add api friendly crasher. Return error and error message in json format (if logged in i guess)
class log
{

    // log event
    static function message($message, $file = __FILE__, $line = __LINE__)
    {
        error_log(basename($file) . ":" . $line . " " . $message);
    }

    // Crash the server, write a log and alert the developer about the incident.
    static function die($message, $file = __FILE__, $line = __LINE__, bool $api = false)
    {
        global $settings;
        http_response_code(500);
        error_log(basename($file) . ":" . $line . " " . $message);
        error_log(print_r(debug_backtrace(), true));
        mail(
            $settings["emails"]["developer"],
            "Error on web server",
            "Error occurred. Please check the logs. Have fun fixing bugs btw."
        );
        if ($api) {
            json_encode(["error" => "true"]);
        } else {
            print("<script>alert('The site has crashed. The developers have now been informed and will resolve the issue as soon as possible. Sorry for the inconvenience.')</script>");
        }
        throw new Exception($message);
    }

    // return 400 Bad request to client and log the event
    static function client_error($message = "Bad request", $file = __FILE__, $line = __LINE__)
    {
        http_response_code(400);
        print($message);
        error_log(basename($file) . ":", $line, $message);
    }

    // Return 403 Forbidden to client and log the event
    static function forbidden($message = "Forbidden", $file = __FILE__, $line = __LINE__)
    {
        http_response_code(403);
        print($message);
        error_log(basename($file) . ":" . $line . " " . $message);
        die();
    }

    // Log $message and javascript.alert($message) to the client
    static function alert($message, $file = __FILE__, $line = __LINE__)
    {
        $message = htmlspecialchars($message);
        error_log(basename($file) . ":" . $line . " " . $message);
        print("<script>alert('$message')</script>");
    }
}
