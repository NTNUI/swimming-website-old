<?php

declare(strict_types=1);

require_once(__DIR__ . "/../Exception/Order.php");
require_once(__DIR__ . "/../Exception/Store.php");
require_once(__DIR__ . "/Customer.php");
require_once(__DIR__ . "/Product.php");

use \libphonenumber\PhoneNumber;
use \libphonenumber\PhoneNumberUtil;
use \libphonenumber\PhoneNumberFormat;
use Stripe\PaymentIntent;


enum OrderStatus
{
    case PLACED;
    case FINALIZED;
    case DELIVERED;
    case FAILED;
    case REFUNDED;

    public static function fromString(string $orderStatus): self
    {
        $orderStatus = strtolower($orderStatus);
        return match ($orderStatus) {
            "placed" => self::PLACED,
            "finalized"  => self::FINALIZED,
            "delivered"  => self::DELIVERED,
            "failed"  => self::FAILED,
            "refunded"  => self::REFUNDED,
            default => throw new \Exception("order status cannot be created"),
        };
    }
    public function toString(): string
    {
        return match ($this) {
            self::PLACED => "PLACED",
            self::FINALIZED => "FINALIZED",
            self::DELIVERED => "DELIVERED",
            self::FAILED => "FAILED",
            self::REFUNDED => "REFUNDED",
        };
    }
};

class Order
{
    private function __construct(
        public readonly Customer $customer,
        public readonly int $dbRowId,
        public readonly int $productRowId,
        public readonly string $intentId,
        public readonly ?string $comment = NULL,
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
     * @param OrderStatus $order_status defaults to OrderStatus::PLACED
     * @return self
     */
    public static function new(
        Customer $customer,
        Product $product,
        string $paymentMethodId,
        string $comment = "",
        OrderStatus $orderStatus = OrderStatus::PLACED,

    ): self {
        if (!$product->enabled) throw new ProductNotEnabledException();

        $now = new DateTime();
        if (!$product->isAvailable()) {
            throw new ProductNotAvailableException("this product cannot be purchased at this point");
        }
        if (empty($customer->name)) throw new MissingCustomerDetailsException("Missing customer name");
        if (empty($customer->phone) && $product->require_phone) throw new MissingCustomerDetailsException("A phone number is required for this purchase");
        if (empty($customer->email)) throw new MissingCustomerDetailsException("email is required for all purchases");
        if (empty($comment) && $product->requireComment) throw new MissingOrderDetailsException("A comment is required for this purchase");

        // Note: Only checking purchases for current year
        if ($product->maxOrdersPerCustomer !== NULL) {
            if (empty($customer->phone)) {
                throw new MissingCustomerDetailsException("A phone number is required for this purchase");
            }
            if ($product->maxOrdersPerCustomer < count(self::getCompletedAsArray($product->productHash, $customer->phone))) {
                throw new MaxOrdersExceededException();
            }
        }
        if (Product::getInventoryCount($product->productHash) <= 0) {
            throw new ProductSoldOutException();
        }

        if ($product->requireMembership) {
            if (!Member::fromPhone($customer->phone)->isMember()) {
                throw new CustomerIsNotMemberException();
            }
        }

        // charge member price if customer has an active membership
        $price = $product->getPrice();
        if (!empty($product->getPriceMember()) && Member::fromPhone($customer->phone)->isMember()) {
            $price = min($product->getPriceMember(), $product->getPrice());
        }
        // TODO: investigate. Cannot create a valid product without the price being valid. Maybe this method can be converted back to being a private method
        if (!Product::isValidPrice($price)) {
            throw new PriceErrorException();
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
        $productDbRowId = $product->dbRowId;
        $phoneString = $customer->getPhoneAsString();

        $fullName = $customer->fullName;
        $email = $customer->email;

        $orderStatusString = $orderStatus->toString();
        $db->bindParam(
            "issssss",
            $productDbRowId,
            $fullName,
            $email,
            $phoneString,
            $intent["id"],
            $orderStatusString,
            $comment,
        );
        $db->execute();

        \Stripe\PaymentIntent::update($intent["id"], ["metadata" => ["orderId" => $db->insertedId()]]);

        return new self(
            $customer,
            $db->insertedId(),
            $product->dbRowId,
            $intent["id"],
            $comment,
            $orderStatus
        );
    }


    public static function FromDbRowId(int $dbRowId): self
    {
        $db = new DB();
        $db->prepare("SELECT * FROM orders WHERE id=?");
        $db->bindParam("i", $dbRowId);
        $db->execute();
        $db->bindResult(
            $dbRowId,
            $productRowId,
            $customerName,
            $customerEmail,
            $customerPhone,
            $intentId,
            $orderStatus,
            $comment,
            $_, // timestamp, not needed
        );

        $phoneObject = NULL;
        if ($customerPhone) {
            $phoneObject = PhoneNumberUtil::getInstance()->parse($customerPhone);
        }

        return new Order(
            new Customer($customerName, $customerEmail, $phoneObject),
            $dbRowId,
            $productRowId,
            $intentId,
            $orderStatus,
            $comment,
        );
    }


    public static function fromPaymentIntent(PaymentIntent $paymentIntent): self
    {
        $db = new DB();
        $db->prepare("SELECT * FROM orders WHERE intentId=?");
        $id = $paymentIntent["id"];
        $db->bindParam("i", $id);
        $db->bindResult(
            $dbRowId,
            $productRowId,
            $customerName,
            $customerEmail,
            $customerPhone,
            $intentId,
            $orderStatus,
            $comment,
            $_, // timestamp, not needed
        );
        if ($db->fetch() === false) {
            throw new OrderNotFoundException();
        }
        $phoneObject = NULL;
        if ($customerPhone) {
            $phoneObject = PhoneNumberUtil::getInstance()->parse($customerPhone, "NO");
        }
        return new self(
            new Customer($customerName, $customerEmail, $phoneObject),
            $dbRowId,
            $productRowId,
            $intentId,
            $comment,
            OrderStatus::fromString($orderStatus),
        );
    }

    #endregion

    #region setters
    public function setOrderStatus(OrderStatus $orderStatus): void
    {
        $db = new DB();
        $db->prepare("UPDATE orders SET orderStatus = ? WHERE id = ?");
        $orderStatusString = $orderStatus->toString();
        $dbRowId = $this->dbRowId;
        $db->bindParam("si", $orderStatusString, $dbRowId);
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

    static public function existsFromDbRowId(int $orderId): bool
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
     * @param ?string $product_hash if set, will return only orders matching that product_hash
     * @param ?PhoneNumber $phoneNumber if set, will return only orders matching that product_hash
     * @return array<int, array{
     *      dbRowId: int,
     *      productId: int,
     *      customerName: string,
     *      customerEmail: string,
     *      customerPhone: string,
     *      intentId : int,
     *      orderStatus: string,
     *      comment: string
     *      }>
     */
    public static function getCompletedAsArray(?string $productHash = NULL, ?PhoneNumber $phoneNumber = NULL): array
    {
        $db = new DB();

        $sql = <<<'SQL'
        SELECT * FROM orders WHERE (orderStatus='FINALIZED' OR orderStatus='DELIVERED')
        SQL;

        $db->prepare($sql);
        $db->execute();
        $db->bindResult(
            $dbRowId,
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
                "dbRowId" => $dbRowId,
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
                if ($order["productId"] !== Product::fromProductHash($productHash)->dbRowId) {
                    unset($orders[$orderIndex]);
                }
            }
        }
        return $orders;
    }

    #endregion


}
