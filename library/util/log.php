<?php

// Usage: log_message("Something important happend", __FILE__, __LINE__);
// log will be visible on the root of the directory in php.log (settings are in .htaccess file)
// remember to pass __FILE__ and __LINE__ arguments
// Don't rely on log messages to be ordered in log! They will be from different users.
// Recomend putting it inside conditionals that will crash the site.
function log_message($message, $file = __FILE__, $line = __LINE__)
{
    error_log(basename($file) . ":" . $line . " " . $message);
}
function log_exception($message, $file = __FILE__, $line = __LINE__)
{
    error_log(basename($file) . ":" . $line . " " . $message);
    throw new Exception($message);
}
