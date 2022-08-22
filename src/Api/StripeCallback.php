<?php

/**
 * deprecated
 */

declare(strict_types=1);

namespace NTNUI\Swimming\Api;

// Documentation: https://stripe.com/docs/webhooks
use Stripe\Webhook;
use Stripe\PaymentIntent;
use NTNUI\Swimming\Util\Order;
use NTNUI\Swimming\Util\Member;
use NTNUI\Swimming\Util\Response;
use NTNUI\Swimming\Util\Settings;
use libphonenumber\PhoneNumberUtil;
use NTNUI\Swimming\Util\OrderStatus;

$response = new Response();
global $args;
try {
    if (\NTNUI\Swimming\Util\argsURL("SERVER", "REQUEST_METHOD") !== "POST") {
        throw new \Exception("accepting only post methods");
    }
    $secret = $_ENV["STRIPE_SIGNING_KEY"];
    $data = file_get_contents("php://input");
    if ($data === false) {
        throw new \Exception();
    }
    $sigHeader = \NTNUI\Swimming\Util\argsURL("SERVER", "HTTP_STRIPE_SIGNATURE");
    if (empty($sigHeader)) {
        throw new \InvalidArgumentException("signature invalid");
    }
    $event = Webhook::constructEvent(
        $data,
        $sigHeader,
        $secret
    );

    switch ($event["type"]) {
        case "source.canceled":
        case "charge.failed":
            Order::fromPaymentIntent(PaymentIntent::retrieve($event["data"]["object"]["payment_intent"]))->setOrderStatus(OrderStatus::FAILED);
            $response->code = Response::HTTP_OK;
            break;
        case "charge.succeeded":
            Order::fromPaymentIntent(PaymentIntent::retrieve($event["data"]["object"]["payment_intent"]))->setOrderStatus(OrderStatus::FINALIZED);
            if ($event["data"]["object"]["productHash"] === Settings::getInstance()->getLicenseProductHash()) {
                $phone = PhoneNumberUtil::getInstance()->parse($event["data"]["object"]["phone"]);
                Member::fromPhone($phone)->approveEnrollment();
            }
            $response->code = Response::HTTP_OK;
            break;
        default:
            $response->code = Response::HTTP_BAD_REQUEST;
            break;
    }
} catch (\Throwable $ex) {
    $response->code = Response::HTTP_INTERNAL_SERVER_ERROR;
    if (boolval(filter_var($_ENV["DEBUG"], FILTER_VALIDATE_BOOLEAN))) {
        $response->data["message"] = $ex->getMessage();
        $response->data["code"] = $ex->getCode();
        $response->data["file"] = $ex->getFile();
        $response->data["line"] = $ex->getLine();
        $response->data["args"] = $args;
        $response->data["backtrace"] = $ex->getTrace();
    }
}

$response->sendJson();
