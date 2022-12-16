<?php

declare(strict_types=1);

namespace NTNUI\Swimming\App;

use Maknz\Slack\Attachment;
use Maknz\Slack\Client;
use NTNUI\Swimming\Api\Auth;
use NTNUI\Swimming\Api\Charge;
use NTNUI\Swimming\Api\Member;
use NTNUI\Swimming\Api\Product;
use NTNUI\Swimming\Api\StripeCallback;
use NTNUI\Swimming\Api\User;
use NTNUI\Swimming\App\Response;
use NTNUI\Swimming\Exception\Api\ApiException;
use NTNUI\Swimming\Exception\Api\AuthenticationException;

// TODO: Switch to psr-7 Request Response interface 

class Router
{

    public function __construct(
        public readonly string $requestMethod,
        public readonly array $request,
        private Client $slack,
        public readonly string $pathIndexHtml,
        public readonly string $path404Html,
    ) {
    }

    public static function getValidEndpoints(): array
    {
        // remove .php extension
        $validEndpoints = str_replace(".php", "", str_replace(__DIR__ . "/../Api/", "", glob(__DIR__ . "/../Api/*.php")));
        $validEndpoints = array_map(fn ($endpoint) => lcfirst($endpoint), $validEndpoints);
        return $validEndpoints;
    }

    /**
     * run - serve the request
     *
     * @note: This function will output index.html directly to the client unless request is targeted to API
     * TODO: fix this^^ Should always return a valid Response object.
     * 
     * @param string $requestUri the string from url bar in the web browser
     * @return Response a json request with status codes
     */
    public function run(string $requestUri): Response
    {
        $args = array_filter(array_reverse(explode("/", $requestUri)));

        $page = array_pop($args);

        if ($page !== "api") {
            // TODO: check if file exists or something like that before returning a 404.
            echo file_get_contents($this->pathIndexHtml);
            exit;
        }

        $service = array_pop($args);

        // what if $service does not exists? eg GET /api/
        $questionMarkPos = strpos($service, "?");
        if ($questionMarkPos !== false) {
            $service = substr($service, 0, $questionMarkPos);
        } // we don't need to parse get arguments since they are already available through $_GET
        $response = new Response();
        try {
            $response = match ($service) {
                "auth" => Auth::run($this->requestMethod, $args, $this->request),
                "member" => Member::run($this->requestMethod, $args, $this->request),
                "user" => User::run($this->requestMethod, $args, $this->request),
                "product" => Product::run($this->requestMethod, $args, $this->request),
                "charge" => Charge::run($this->requestMethod, $args, $this->request),
                "stripeCallback" => StripeCallback::run($this->requestMethod, $args, $this->request),
                default => throw ApiException::endpointDoesNotExist(),
            };
            if (empty($response->code)) {
                $response->code = empty($response->data) ? Response::HTTP_NOT_FOUND : Response::HTTP_OK;
            }
        } catch (AuthenticationException | ApiException $ex) {
            $response->code = $ex->getCode();
            $response->data = [
                "success" => false,
                "error" => true,
                "message" => $ex->getMessage(),
                "args", $args,
            ];
        } catch (\Throwable $ex) {
            $response->code = Response::HTTP_INTERNAL_SERVER_ERROR;
            $response->data = [
                "success" => false,
                "error" => true,
                "message" => "internal server error",
            ];

            if (boolval(filter_var($_ENV["DEBUG"], FILTER_VALIDATE_BOOLEAN))) {
                $response->data["message"] = $ex->getMessage();
                $response->data["code"] = $ex->getCode();
                $response->data["file"] = $ex->getFile();
                $response->data["line"] = $ex->getLine();
                $response->data["args"] = $args;
                $response->data["backtrace"] = $ex->getTrace();
            }
            if (boolval(filter_var($_ENV["SLACK_ENABLE"], FILTER_VALIDATE_BOOLEAN))) {
                $backtrace = json_encode($ex->getTrace(), \JSON_PRETTY_PRINT | \JSON_OBJECT_AS_ARRAY | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_LINE_TERMINATORS | \JSON_UNESCAPED_UNICODE);
                $file = str_replace($_SERVER["DOCUMENT_ROOT"] . "/", "", $ex->getFile());
                $attachment = new Attachment(
                    [
                        'text' => ':error: Uncaught exception',
                        'color' => 'danger',
                        "fields" => [
                            [
                                "title" => "Exception message",
                                "short" => true,
                                "value" => $ex->getMessage(),
                            ],
                            [
                                "title" => "Endpoint",
                                "short" => true,
                                "value" => lcfirst($service)
                            ],
                            [
                                "title" => "Request Method",
                                "short" => true,
                                "value" => $_SERVER["REQUEST_METHOD"]
                            ],
                            [
                                "title" => "File",
                                "short" => true,
                                "value" => $file,
                            ],
                            [
                                "title" => "Line",
                                "short" => true,
                                "value" => $ex->getLine(),
                            ],
                            [
                                "title" => "Exception Code",
                                "short" => true,
                                "value" => $ex->getCode(),
                            ],
                            [
                                "title" => "Backtrace",
                                "short" => false,
                                "value" => <<<MARKDOWN
                    ```json
                    $backtrace
                    ```
                    MARKDOWN
                            ],
                            [
                                "title" => "Request",
                                "short" => false,
                                "value" => "```json\n" . json_encode($_REQUEST, \JSON_PRETTY_PRINT) .  "```",
                            ],

                        ],
                    ]
                );
                $this->slack->to($_ENV["SLACK_CHANNEL_CRASH"])->attach($attachment)->send();
            }
        }
        return $response;
    }
}
