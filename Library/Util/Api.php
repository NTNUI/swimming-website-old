<?php

declare(strict_types=1);

use Lukasoppermann\Httpstatus\Httpstatuscodes;
use Lukasoppermann\Httpstatus\HttpStatus;

class Response implements Httpstatuscodes
{
    public int $code;
    /** @var ?array<mixed> $data */
    public ?array $data = NULL;

    public function sendJson(): void
    {
        header("Content-type: application/json; charset=UTF-8");
        if (!isset($this->code)) {
            throw new Exception("response code not set");
        }
        http_response_code($this->code);
        header($_SERVER["SERVER_PROTOCOL"] . " $this->code " . ((new Lukasoppermann\Httpstatus\HttpStatus())->getReasonPhrase($this->code)));
        if (!empty($this->data)) {
            echo json_encode($this->data, JSON_PRETTY_PRINT);
        }
        $this->data = NULL;
    }

}
