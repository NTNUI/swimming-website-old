<?php

define("HTTP_OK", 200);
define("HTTP_INVALID_REQUEST", 400);
define("HTTP_FORBIDDEN", 403);
define("HTTP_NOT_FOUND", 404);
define("HTTP_NOT_IMPLEMENTED", 501);
define("HTTP_INTERNAL_SERVER_ERROR", 500);


class Response{
    public int $code = HTTP_INTERNAL_SERVER_ERROR;
    public array $data = ["error" => true, "message" => "no data"];
    
    public function send(){
        header("Content-type: application/json; charset=UTF-8");
        http_response_code($this->code);
        echo json_encode($this->data);
    }

    public function error(string $message, int $code = HTTP_INVALID_REQUEST){
        $this->code = $code;
        $this->data = ["error" => true, "message" => $message];
    }

    public function __construct()
    {
    }

}
