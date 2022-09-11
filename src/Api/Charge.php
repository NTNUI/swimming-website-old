<?php

declare(strict_types=1);

namespace NTNUI\Swimming\Api;

use libphonenumber\PhoneNumberUtil;
use NTNUI\Swimming\App\Response;
use NTNUI\Swimming\App\Settings;
use NTNUI\Swimming\Db\Member;
use NTNUI\Swimming\Db\Order;
use NTNUI\Swimming\Db\Product;
use NTNUI\Swimming\Enum\OrderStatus;
use NTNUI\Swimming\Interface\Endpoint;
use NTNUI\Swimming\Util\Customer;
use Stripe\PaymentIntent;


class Charge implements Endpoint
{
    public static function run(string $requestMethod, array $args, array $request): Response
    {
        $response = new Response();
        \Stripe\Stripe::setApiKey($_ENV["STRIPE_SECRET_KEY"]);

        $request = Response::getJsonInput();
        $intent = new PaymentIntent();

        if (isset($request["payment_method_id"])) {
            if (!array_key_exists("order", $request)) {
                throw new \InvalidArgumentException("order is not set");
            }

            if (!array_key_exists("customer", $request["order"])) {
                throw new \InvalidArgumentException("customer is not set");
            }

            // stripe expects clients to send full name in name property. Everywhere else fullName is used.
            foreach (["name", "email"] as $key) {
                if (!array_key_exists($key, $request["order"]["customer"])) {
                    throw new \InvalidArgumentException("missing $key inside order");
                }
            }

            if (!array_key_exists("productHash", $request["order"]["product"])) {
                throw new \InvalidArgumentException("productHash is not set");
            }
            $product = Product::fromProductHash($request["order"]["product"]["productHash"]);

            $phone = null;
            if ($request["order"]["customer"]["phone"]) {
                $phone = PhoneNumberUtil::getInstance()->parse($request["order"]["customer"]["phone"]);
            }
            $customer = new Customer($request["order"]["customer"]["name"], $request["order"]["customer"]["email"], $phone);

            $order = Order::new($customer, $product, $request["payment_method_id"], $request["order"]["comment"] ?? "", OrderStatus::PLACED);
            $intent = PaymentIntent::retrieve($order->intentId);
        } elseif (isset($request["payment_intent_id"])) {
            $intent = PaymentIntent::retrieve($request["payment_intent_id"]);
            $intent->confirm();
        } else {
            throw new \InvalidArgumentException("payment_intent_id or payment_method_id needs to be set");
        }

        if ($intent["status"] === "requires_action" && $intent["next_action"]["type"] === "use_stripe_sdk") {
            $response->data = [
                "requires_action" => true,
                "payment_intent_client_secret" => $intent["client_secret"],
            ];
        } elseif ($intent["status"] === "succeeded") {
            Order::fromPaymentIntent(PaymentIntent::retrieve($intent["id"]))->setOrderStatus(OrderStatus::FINALIZED);
            $response->data = [
                "success" => true,
                "error" => false,
                "message" => "Purchase succeeded.\nYou've been charged " . $intent["amount"] / 100 . " NOK",
            ];

            // membership approval hook
            if ($intent["metadata"]["productHash"] === Settings::getInstance()->getLicenseProductHash()) {
                Member::fromPhone(PhoneNumberUtil::getInstance()->parse($request["order"]["customer"]["phone"]))->approveEnrollment();
                $response->data["message"] .= "\nYour membership has been automatically approved.";
                $response->data["redirect_url"] = "https://ntnui.slab.com/posts/welcome-to-ntnui-swimming-%F0%9F%92%A6-44w4p9pv";
            }
        }

        $response->code = Response::HTTP_OK;
        return $response;
    }
}
