<?php

/**
 * deprecated
 */

declare(strict_types=1);

namespace NTNUI\Swimming\Api;

// Documentation: https://stripe.com/docs/webhooks
use libphonenumber\PhoneNumberUtil;
use NTNUI\Swimming\App\Response;
use NTNUI\Swimming\App\Settings;
use NTNUI\Swimming\Db\Member;
use NTNUI\Swimming\Db\Order;
use NTNUI\Swimming\Enum\OrderStatus;
use NTNUI\Swimming\Exception\Api\ApiException;
use NTNUI\Swimming\Interface\Endpoint;
use Stripe\PaymentIntent;
use Stripe\Webhook;

class StripeCallback implements Endpoint
{
    public static function run(string $requestMethod, array $args, array $request): Response
    {
        $response = new Response();
        if ($requestMethod !== "POST") {
            throw ApiException::methodNotAllowed("accepting only post methods");
        }
        $secret = $_ENV["STRIPE_SIGNING_KEY"];
        $data = Response::getJsonInput();

        $sigHeader = \NTNUI\Swimming\App\argsURL("SERVER", "HTTP_STRIPE_SIGNATURE");
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
        return $response;
    }
}
