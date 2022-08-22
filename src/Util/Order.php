<?php

declare(strict_types=1);

namespace NTNUI\Swimming\Util;

use Stripe\PaymentIntent;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberUtil;
use NTNUI\Swimming\Util\OrderStatus;
use libphonenumber\PhoneNumberFormat;
use NTNUI\Swimming\Exception\Api\OrderException;
use NTNUI\Swimming\Exception\Api\ProductException;

class Order
{
    private function __construct(
        public readonly Customer $customer,
        public readonly int $id,
        public readonly int $productId,
        public readonly string $intentId,
        public readonly ?string $comment = null,
        private OrderStatus $orderStatus = OrderStatus::PLACED,
    ) {
    }

    #region constructors

    /**
     * create a new order
     *
     *
     * @param Customer $customer
     * @param Product $product
     * @param string $paymentMethodId from stripe
     * @param string $comment order comment
     * @param OrderStatus $orderStatus defaults to OrderStatus::PLACED
     * @return self
     */
    public static function new(
        Customer $customer,
        Product $product,
        string $paymentMethodId,
        string $comment = "",
        OrderStatus $orderStatus = OrderStatus::PLACED,
    ): self {
        if (!$product->enabled) {
            throw ProductException::productNotAvailable("product is not available for purchase");
        }
        if (!$product->isAvailable()) {
            throw ProductException::productNotAvailable("product is not available for purchase");
        }
        if (empty($customer->name)) {
            throw ProductException::missingCustomerDetails("name is missing");
        }
        if (empty($customer->phone) && $product->requirePhone) {
            throw ProductException::missingCustomerDetails("phone number is missing");
        } // TODO: should customers be able to purchase products without a phone number?
        if (empty($customer->email)) {
            throw productException::missingCustomerDetails("email is missing");
        }
        if (empty($comment) && $product->requireComment) {
            throw productException::missingCustomerDetails("comment is missing");
        }

        // Note: Only checking purchases for current year
        if ($product->maxOrdersPerCustomer !== null) {
            if (empty($customer->phone)) {
                throw OrderException::missingOrderDetails("A phone number is required for this purchase");
            }
            if ($product->maxOrdersPerCustomer < count(self::getCompletedAsArray($product->productHash, $customer->phone))) {
                throw OrderException::maxOrdersExceeded();
            }
        }
        if (Product::getInventoryCount($product->productHash) <= 0) {
            throw ProductException::productSoldOut();
        }

        if ($product->requireMembership) {
            if (!Member::fromPhone($customer->phone)->isMember()) {
                throw ProductException::membershipRequired();
            }
        }

        // charge member price if customer has an active membership
        $price = $product->getPrice();
        if (!empty($product->getPriceMember()) && Member::fromPhone($customer->phone)->isMember()) {
            $price = min($product->getPriceMember(), $product->getPrice());
        }
        // TODO: investigate. Cannot create a valid product without the price being valid. Maybe this method can be converted back to being a private method
        if (!Product::isValidPrice($price)) {
            throw ProductException::priceError();
        }

        $price = $product->getPrice();
        if (Member::fromPhone($customer->phone)->isMember()) {
            $price = min($price, $product->getPriceMember());
        }
        $intent = \Stripe\PaymentIntent::create([
            "payment_method" => $paymentMethodId,
            "amount" => $price * 100,
            "currency" => "nok",
            "confirmation_method" => "manual",
            "confirm" => true,
            "receipt_email" => $customer->email,
            "description" => $product->getName(Language::ENGLISH),
            "metadata" => [
                "comment" => $comment,
                "productHash" => $product->productHash,
                "productNameNo" => $product->getName(Language::NORWEGIAN),
                "productNameEn" => $product->getName(Language::ENGLISH),
                "isMember" => Member::fromPhone($customer->phone)->isMember(),
            ],
        ]);
        $db = new DB();
        $sql = "INSERT INTO orders (productId, name, email, phone, intentId, orderStatus, comment) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $db->prepare($sql);
        $productId = $product->id;
        $phoneString = $customer->getPhoneAsString();

        $fullName = $customer->fullName;
        $email = $customer->email;

        $orderStatusString = $orderStatus->toString();
        $intentId = $intent["id"];
        $db->bindParam(
            "issssss",
            $productId,
            $fullName,
            $email,
            $phoneString,
            $intentId,
            $orderStatusString,
            $comment,
        );
        $db->execute();

        $orderId = $db->insertedId();
        \Stripe\PaymentIntent::update($intent["id"], ["metadata" => ["orderId" => $orderId]]);

        return new self(
            customer: $customer,
            id: $orderId,
            productId: $productId,
            intentId: $intentId,
            comment: $comment,
            orderStatus: $orderStatus
        );
    }


    public static function fromId(int $orderId): self
    {
        $db = new DB();
        $db->prepare("SELECT * FROM orders WHERE id=?");
        $db->bindParam("i", $orderId);
        $db->execute();
        $db->bindResult(
            $orderId,
            $productId,
            $customerName,
            $customerEmail,
            $customerPhone,
            $intentId,
            $orderStatus,
            $comment,
            $_, // timestamp, not needed
        );

        $phoneObject = null;
        if ($customerPhone) {
            $phoneObject = PhoneNumberUtil::getInstance()->parse($customerPhone);
        }

        return new Order(
            customer: new Customer($customerName, $customerEmail, $phoneObject),
            id: $orderId,
            productId: $productId,
            intentId: $intentId,
            orderStatus: $orderStatus,
            comment: $comment,
        );
    }


    public static function fromPaymentIntent(PaymentIntent $paymentIntent): self
    {
        $db = new DB();
        $db->prepare("SELECT * FROM orders WHERE intentId=?");
        $id = $paymentIntent["id"];
        $db->bindParam("i", $id);
        $db->bindResult(
            $orderId,
            $productId,
            $customerName,
            $customerEmail,
            $customerPhone,
            $intentId,
            $orderStatus,
            $comment,
            $_, // timestamp, not needed
        );
        if ($db->fetch() === false) {
            throw OrderException::orderNotFound();
        }
        $phoneObject = null;
        if ($customerPhone) {
            $phoneObject = PhoneNumberUtil::getInstance()->parse($customerPhone, "NO");
        }
        return new self(
            new Customer($customerName, $customerEmail, $phoneObject),
            id: $orderId,
            productId: $productId,
            intentId: $intentId,
            comment: $comment,
            orderStatus: OrderStatus::fromString($orderStatus),
        );
    }

    #endregion

    #region setters
    public function setOrderStatus(OrderStatus $orderStatus): void
    {
        $db = new DB();
        $db->prepare("UPDATE orders SET orderStatus = ? WHERE id = ?");
        $orderStatusString = $orderStatus->toString();
        $orderId = $this->id;
        $db->bindParam("si", $orderStatusString, $orderId);
        $db->execute();
        $this->orderStatus = $orderStatus;
    }

    #endregion

    #region getters

    public function getOrderStatus(): OrderStatus
    {
        return $this->orderStatus;
    }

    #endregion

    #region static

    public static function idExists(int $orderId): bool
    {
        $db = new DB();
        $db->prepare("SELECT COUNT(*) FROM orders WHERE id=?");
        $db->bindParam("i", $orderId);
        $db->execute();
        $db->bindResult($result);
        return $db->fetch();
    }


    /**
     * get completed orders as array
     *
     * @param ?string $productHash if set, will return only orders matching that productHash
     * @param ?PhoneNumber $phoneNumber if set, will return only orders matching that product_hash
     * @return array<int, array{
     *      id: int,
     *      productId: int,
     *      customerName: string,
     *      customerEmail: string,
     *      customerPhone: string,
     *      intentId : int,
     *      orderStatus: string,
     *      comment: string
     *      }>
     */
    public static function getCompletedAsArray(?string $productHash = null, ?PhoneNumber $phoneNumber = null): array
    {
        $db = new DB();

        $sql = <<<'SQL'
        SELECT * FROM orders WHERE (orderStatus='FINALIZED' OR orderStatus='DELIVERED')
        SQL;

        $db->prepare($sql);
        $db->execute();
        $db->bindResult(
            $orderId,
            $productId,
            $customerName,
            $customerEmail,
            $customerPhone,
            $intentId,
            $orderStatus,
            $comment,
            $_, // timestamp, not needed
        );
        $orders = [];
        while ($db->fetch()) {
            $order = [
                "id" => $orderId,
                "productId" => $productId,
                "customerName" => $customerName,
                "customerEmail" => $customerEmail,
                "customerPhone" => $customerPhone,
                "intentId" => $intentId,
                "orderStatus" => $orderStatus,
                "comment" => $comment,
            ];
            array_push($orders, $order);
        }
        if (isset($phoneNumber)) {
            $phoneNumberString = PhoneNumberUtil::getInstance()->format($phoneNumber, PhonenumberFormat::E164);
            foreach ($orders as $orderIndex => $order) {
                if ($order["customerPhone"] !== $phoneNumberString) {
                    unset($orders[$orderIndex]);
                }
            }
        }
        if (isset($productHash)) {
            foreach ($orders as $orderIndex => $order) {
                if ($order["productId"] !== Product::fromProductHash($productHash)->id) {
                    unset($orders[$orderIndex]);
                }
            }
        }
        return $orders;
    }

    #endregion
}
