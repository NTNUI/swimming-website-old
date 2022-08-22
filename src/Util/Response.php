<?php

declare(strict_types=1);

namespace NTNUI\Swimming\Util;

use Lukasoppermann\Httpstatus\Httpstatus;
use Lukasoppermann\Httpstatus\Httpstatuscodes;
use NTNUI\Swimming\Exception\Api\ApiException;
use Webmozart\Assert\Assert;

class Response implements Httpstatuscodes
{
    public int $code;
    /** @var ?array<mixed> $data */
    public ?array $data = null;

    public function sendJson(): void
    {
        header("Content-type: application/json; charset=UTF-8");
        Assert::true(isset($this->code), "response code not set");
        http_response_code($this->code);
        header($_SERVER["SERVER_PROTOCOL"] . " $this->code " . ((new Httpstatus())->getReasonPhrase($this->code)));
        if (!empty($this->data)) {
            echo json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        $this->data = null;
    }

    /**
     * get json input from POST request.
     *
     * @throws ApiException if input is missing
     * @throws \JsonException if decoding fails
     *
     * @return array<mixed>
     */
    public static function getJsonInput(): array
    {
        $content = file_get_contents("php://input");
        if ($content === false || $content === "") {
            throw ApiException::invalidRequest("missing json input");
        }
        return json_decode($content, true, flags: JSON_THROW_ON_ERROR);
    }
}
