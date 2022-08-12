<?php

declare(strict_types=1);

class Log
{
    /**
     * Log a message to file
     *
     * This function will log a message to log file with source file and line number on where the call has been made.
     * 
     * @param string|array<string> $message
     * @return void
     */
    static function message(string|array $message): void
    {
        // replace full file path with relative from project root
        $ex = new Exception();
        $file = str_replace($_SERVER["DOCUMENT_ROOT"] . "/", "", $ex->getTrace()[0]["file"]);
        $line = $ex->getTrace()[0]["line"];

        // print message
        if (is_array($message)) {
            foreach ($message as $key => $value) {
                self::print("$key=$value", $file, $line);
            }
        } else {
            self::print($message, $file, $line);
        }
    }

    private static function print(string $message, string $file, int $line): void
    {
        $message_lines = explode("\n", $message);
        if (count($message_lines) > 1) {
            error_log("$file:$line");
            foreach ($message_lines as $msg) {
                error_log("$msg");
            }
            error_log(" ");
        } else {
            error_log("$file:$line $message");
            error_log(" ");
        }
    }
}
