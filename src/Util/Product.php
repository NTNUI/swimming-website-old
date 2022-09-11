<?php

declare(strict_types=1);

namespace NTNUI\Swimming\Util;

use NTNUI\Swimming\Enum\Language;
use NTNUI\Swimming\Exception\Api\ApiException;
use NTNUI\Swimming\Exception\Api\OrderException;
use NTNUI\Swimming\Exception\Api\ProductException;
use NTNUI\Swimming\Util\DB;
use Webmozart\Assert\Assert;

class Product
{
    public const DATE_FORMAT = "Y-m-d H:i:s"; // https://www.php.net/manual/en/datetime.format.php
    public const TIME_ZONE = "Europe/Oslo";
    public const MAX_PRICE = 2000; // failsafe
    public const MIN_PRICE = 3; // minimum requirements by Stripe

    #region constructor

    private function __construct(
        public readonly int $id,
        public readonly string $productHash,
        private string $nameJson,
        private string $descriptionJson,
        private int $price,
        private ?int $priceMember,
        private ?\DateTimeImmutable $availableFrom,
        private ?\DateTimeImmutable $availableUntil,
        public readonly ?int $maxOrdersPerCustomer,
        public readonly bool $requireEmail,
        public readonly bool $requireComment,
        public readonly bool $requirePhone,
        public readonly bool $requireMembership,
        private ?int $inventoryCount,
        private ?string $image,
        private bool $visible,
        public readonly bool $enabled,
        public readonly \DateTimeZone|false $TIME_ZONE = new \DateTimeZone(self::TIME_ZONE)
    ) {
        Assert::false($this->TIME_ZONE);
    }

    public static function new(
        string $nameJson,
        string $descriptionJson,
        int $price,
        ?int $priceMember,
        ?\DateTimeImmutable $availableFrom,
        ?\DateTimeImmutable $availableUntil,
        ?int $maxOrdersPerCustomer,
        bool $requireEmail,
        bool $requireComment,
        bool $requirePhone,
        bool $requireMembership,
        ?int $inventoryCount,
        ?string $image,
        bool $visible,
        bool $enabled
    ): self {
        // validate and transform data to savable format in DB
        // convert from NOK to øre
        if ($price < 3) {
            throw ProductException::priceError("cannot create a product with a price lower than 3 NOK");
        }
        if (!empty($priceMember)) {
            if ($priceMember < 3) {
                throw ProductException::priceError("cannot create a product with a price lower than 3 NOK");
            }
            $priceMember *= 100;
        }
        $price *= 100;

        // check that translations are present
        foreach ([$nameJson, $descriptionJson] as $decodable) {
            foreach (["no", "en"] as $lang) {
                $decoded = json_decode($decodable, true, flags: JSON_THROW_ON_ERROR);
                if (!array_key_exists($lang, $decoded)) {
                    throw ProductException::missingProductInformation("language: '$lang' is missing for products name or description");
                }
                if (empty($decoded[$lang])) {
                    throw ProductException::missingProductInformation("missing description or name for product in $lang");
                }
            }
        }
        // generate random hash until one is available
        $productHash = "";
        do {
            $productHash = substr(md5((string)time()), 0, 20);
        } while (self::productHashExists($productHash));

        // require phone if limitations on purchase exists
        if ($requireMembership || $priceMember || $maxOrdersPerCustomer) {
            $requirePhone = true;
        }

        // convert dates to string format
        $dateStart = $availableFrom === null ? null : $availableFrom->format(self::DATE_FORMAT);
        $dateEnd = $availableUntil === null ? null : $availableUntil->format(self::DATE_FORMAT);

        // save to db
        $db = new DB();
        $sql = <<<'SQL'
        INSERT INTO products
        (
            hash,
            name,
            description,
            image,
            availableFrom,
            availableUntil,
            maxOrdersPerCustomer_per_year,
            requirePhone,
            requireEmail,
            requireComment,
            requireActiveMembership,
            amountAvailable,
            price,
            priceMember,
            visible,
            enabled
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        SQL;
        $db->prepare($sql);

        $db->bindParam(
            "sssiissiiiiiisiii",
            $productHash,
            $name,
            $description,
            $price,
            $priceMember,
            $dateStart,
            $dateEnd,
            $maxOrdersPerCustomer,
            $requirePhone,
            $requireEmail,
            $requireComment,
            $requireMembership,
            $inventoryCount,
            $image,
            $visible,
            $enabled,
        );
        $db->execute();

        // construct object and return
        return new Product(
            id: $db->insertedId(),
            productHash: $productHash,
            nameJson: $nameJson,
            descriptionJson: $descriptionJson,
            price: $price,
            priceMember: $priceMember,
            availableFrom: $availableFrom,
            availableUntil: $availableUntil,
            maxOrdersPerCustomer: $maxOrdersPerCustomer,
            requireEmail: $requireEmail,
            requireComment: $requireComment,
            requirePhone: $requirePhone,
            requireMembership: $requireMembership,
            inventoryCount: $inventoryCount,
            image: $image,
            visible: $visible,
            enabled: $enabled
        );
    }

    public static function fromProductHash(string $productHash): self
    {
        if (!self::productHashExists($productHash)) {
            throw ProductException::productNotFound();
        }
        $db = new DB();
        $sql = "SELECT * FROM products WHERE hash=?";
        $db->prepare($sql);
        $db->bindParam("s", $productHash);
        $db->execute();
        $db->bindResult(
            $productId,
            $productHash,
            $nameJson,
            $descriptionJson,
            $price,
            $priceMember,
            $availableFrom,
            $availableUntil,
            $maxOrdersPerCustomer,
            $requirePhone,
            $requireEmail,
            $requireComment,
            $requireMembership,
            $inventoryCount,
            $image,
            $visible,
            $enabled,
        );
        $db->execute();
        $db->fetch();
        if (!empty($availableFrom)) {
            $availableFrom = \DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $availableFrom, new \DateTimeZone(self::TIME_ZONE));
            Assert::notFalse($availableFrom);
        }
        if (!empty($availableUntil)) {
            $availableUntil = \DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $availableUntil, new \DateTimeZone(self::TIME_ZONE));
            Assert::notFalse($availableUntil);
        }

        return new self(
            id: $productId,
            productHash: $productHash,
            nameJson: $nameJson,
            descriptionJson: $descriptionJson,
            price: $price,
            priceMember: $priceMember,
            availableFrom: $availableFrom,
            availableUntil: $availableUntil,
            maxOrdersPerCustomer: $maxOrdersPerCustomer,
            requirePhone: (bool)$requirePhone,
            requireEmail: (bool)$requireEmail,
            requireComment: (bool)$requireComment,
            requireMembership: (bool)$requireMembership,
            inventoryCount: $inventoryCount,
            image: $image,
            visible: (bool)$visible,
            enabled: (bool)$enabled,
        );
    }

    /**
     * postHandler
     *
     * @param array $jsonRequest
     * @return array{success:true, error:false, message:string}
     */
    public static function postHandler(array $jsonRequest): array
    {
        self::new(
            nameJson: $jsonRequest["nameJson"],
            descriptionJson: $jsonRequest["descriptionJson"],
            price: (int)$jsonRequest["price"],
            priceMember: empty($jsonRequest["priceMember"]) ? null : (int)$jsonRequest["priceMember"],
            availableFrom: $jsonRequest["availableFrom"],
            availableUntil: $jsonRequest["availableUntil"],
            maxOrdersPerCustomer: empty($jsonRequest["maxOrdersPerCustomer"]) ? null : (int)$jsonRequest["maxOrdersPerCustomer"],
            requireEmail: (bool)$jsonRequest["requireEmail"],
            requirePhone: (bool)$jsonRequest["requirePhone"],
            requireComment: (bool)$jsonRequest["requireComment"],
            requireMembership: (bool)$jsonRequest["requireMembership"],
            inventoryCount: empty($jsonRequest["inventoryCount"]) ? null : $jsonRequest["inventoryCount"],
            image: $jsonRequest["image"],
            visible: (bool)$jsonRequest["visible"],
            enabled: (bool)$jsonRequest["enabled"],
        );
        return [
            "success" => true,
            "error" => false,
            "message" => "product created successfully",
        ];
    }

    #endregion constructor

    #region getters

    public function __get(string $key): mixed
    {
        Assert::false(in_array($key, ["nameJson", "descriptionJson", "price", "priceMember"]), "use getters for this class");
        Assert::propertyExists($this, $key);
        return $this->$key;
    }

    /**
     * Get an associative array of the product.
     * TODO: DateTimeImmutable to unix timestamp
     * @return array{
     *      id: int,
     *      productHash: string,
     *      nameJson: string,
     *      descriptionJson: string,
     *      price: int,
     *      priceMember: ?int,
     *      availableFrom: ?\DateTimeImmutable,
     *      availableUntil: ?\DateTimeImmutable,
     *      maxOrdersPerCustomer: ?int,
     *      requireEmail: bool,
     *      requireComment: bool,
     *      requirePhone: bool,
     *      requireMembership: bool,
     *      inventoryCount: ?int,
     *      image: ?string,
     *      visible: bool,
     *      enabled: bool
     * }
     */
    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "productHash" => $this->productHash,
            "nameJson" => $this->nameJson,
            "descriptionJson" => $this->descriptionJson,
            "price" => $this->price / 100,
            "priceMember" => $this->priceMember / 100,
            "availableFrom" => $this->availableFrom,
            "availableUntil" => $this->availableUntil,
            "maxOrdersPerCustomer" => $this->maxOrdersPerCustomer,
            "requireEmail" => $this->requireEmail,
            "requireComment" => $this->requireComment,
            "requirePhone" => $this->requirePhone,
            "requireMembership" => $this->requireMembership,
            "inventoryCount" => $this->inventoryCount,
            "image" => $this->image,
            "visible" => $this->visible,
            "enabled" => $this->enabled,
        ];
    }

    public function getName(Language $language): string
    {
        $decodedName = json_decode($this->nameJson, true, flags: JSON_THROW_ON_ERROR);
        Assert::keyExists($decodedName, $language->toString());
        return $decodedName[$language->toString()];
    }

    public function getDescription(Language $language): string
    {
        $decodedDescription = json_decode($this->descriptionJson, true, flags: JSON_THROW_ON_ERROR);
        Assert::keyExists($decodedDescription, $language->toString());
        return $decodedDescription[$language->toString()];
    }

    public function getPrice(): int
    {
        return $this->price / 100;
    }

    public function getPriceMember(): ?int
    {
        return $this->priceMember ? $this->priceMember / 100 : null;
    }

    public function isAvailable(): bool
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone(self::TIME_ZONE));
        Assert::notFalse($now);
        // TODO: globally replace with sellStart and sellEnd or something similar
        $start = $this->availableFrom;
        $end = $this->availableUntil;

        if (isset($end) && $end < $now) {
            return false;
        }
        if (isset($start) && $start > $now) {
            return false;
        }
        return true;
    }

    #endregion getters

    #region setters

    public function setImage(string $imagePath): void
    {
        if (!file_exists($imagePath)) {
            Log::message("ERROR: file $imagePath does not exists");
            throw ProductException::modifyProduct("There was an error with the uploaded image. Product not created. Please contact admin.", ProductException::HTTP_INTERNAL_SERVER_ERROR);
        }
        if (!is_readable($imagePath)) {
            Log::message("ERROR: file $imagePath is not readable");
            throw ProductException::modifyProduct("There was an error with the uploaded image. Product not created. Please contact admin.", ProductException::HTTP_INTERNAL_SERVER_ERROR);
        }
        // TODO: is $imagePath inside /tmp? should this method move it to runtime storage?
        $db = new DB();
        $db->prepare("UPDATE products SET image=? WHERE id=?");
        $productId = $this->id;
        $db->bindParam("si", $imagePath, $productId);
        $db->execute();
        $this->image = $imagePath;
    }

    public function setAvailableFrom(?\DateTimeInterface $availableFrom): void
    {
        $db = new DB();
        $productId = $this->id;
        if (isset($availableFrom)) {
            if (!empty($this->availableUntil) && $availableFrom > $this->availableUntil) {
                throw ProductException::modifyProduct("cannot set available from past available until");
            }

            $db->prepare("UPDATE products SET availableFrom=? WHERE id=?");
            $timeString = $availableFrom->format(self::DATE_FORMAT);
            $db->bindParam("ss", $timeString, $productId);
        } else {
            $db->prepare("UPDATE products SET availableFrom=NULL WHERE id=?");
            $db->bindParam("s", $productId);
        }

        $db->execute();
        $this->availableFrom = $availableFrom;
    }

    public function setVisibility(bool $visible): void
    {
        $db = new DB();
        $db->prepare("UPDATE products SET visibility=? WHERE id=?");
        $productId = $this->id;
        $db->bindParam("ii", $visible, $productId);
        $db->execute();
        $this->visible = $visible;
    }

    public function setInventoryCount(?int $inventoryCount): void
    {
        if ($inventoryCount < 0) {
            throw ApiException::invalidRequest("inventory count cannot be set to a negative value");
        }

        $db = new DB();
        $productId = $this->id;
        if (isset($inventoryCount)) {
            $db->prepare("UPDATE products SET amountAvailable=? WHERE id=?");
            $db->bindParam("ii", $inventoryCount, $productId);
        } else {
            $db->prepare("UPDATE products SET amountAvailable=NULL WHERE id=?");
            $db->bindParam("i", $productId);
        }
        $db->execute();
        $this->inventoryCount = $inventoryCount;
    }

    public function setAvailableUntil(?\DateTimeInterface $availableUntil): void
    {
        $db = new DB();
        $productId = $this->id;
        if (isset($availableUntil)) {
            if (!empty($this->availableFrom) && $this->availableFrom > $availableUntil) {
                throw ProductException::modifyProduct("cannot set availableUntil before availableFrom");
            }
            $db->prepare("UPDATE products SET availableUntil=? WHERE id=?");
            $timeString = $availableUntil->format(self::DATE_FORMAT);
            $db->bindParam("si", $timeString, $productId);
        } else {
            $db->prepare("UPDATE products SET availableUntil=NULL WHERE id=?");
            $db->bindParam("i", $productId);
        }
        $db->execute();
        $this->availableUntil = $availableUntil;
    }

    public function setPrice(int $price): void
    {
        if (!self::isValidPrice($price)) {
            throw ProductException::modifyProduct("price has to be between " . self::MIN_PRICE ." and " . self::MAX_PRICE . " NOK");
        }
        $price *= 100; // convert from NOK to øre
        $db = new DB();
        $db->prepare("UPDATE products SET price=? WHERE id=?");
        $productId = $this->id;
        $db->bindParam("ii", $price, $productId);
        $db->execute();
        $this->price = $price;
    }


    public function setPriceMember(?int $priceMember): void
    {
        if (!self::isValidPrice($priceMember)) {
            throw ProductException::modifyProduct("price has to be between " . self::MIN_PRICE ." and " . self::MAX_PRICE . " NOK");
        }
        $db = new DB();
        if (!isset($priceMember)) {
            $db->prepare("UPDATE products SET price=NULL WHERE id=?");
            $productId = $this->id;
            $db->bindParam("i", $productId);
            $db->execute();
            $this->priceMember = null;
            return;
        }
        if (!self::isValidPrice($priceMember)) {
            throw ProductException::modifyProduct("invalid price");
        }
        $priceMember *= 100; // convert from NOK to øre
        $db->prepare("UPDATE products SET price=? WHERE id=?");
        $productId = $this->id;
        $db->bindParam("ii", $priceMember, $productId);
        $db->execute();
        $this->priceMember = $priceMember;
    }

    /**
     * patchHandler
     *
     * @param array $jsonRequest
     * @return array{
     * success:true, error:false, message:string
     * }
     */
    public function patchHandler(array $jsonRequest): array
    {
        $allowedPatches = [
            "availableFrom",
            "availableUntil",
            "image",
            "inventoryCount",
            "price",
            "priceMember",
            "visibility",
        ];
        foreach ($jsonRequest as $key) {
            if (!in_array($key, $allowedPatches)) {
                throw ApiException::patchNotAllowed("patch not allowed for $key");
            }
        }
        if (array_key_exists("availableFrom", $jsonRequest)) {
            $this->setAvailableFrom($jsonRequest["availableFrom"]);
        }
        if (array_key_exists("availableUntil", $jsonRequest)) {
            $this->setAvailableUntil($jsonRequest["availableUntil"]);
        }
        if (array_key_exists("image", $jsonRequest)) {
            $this->setImage($jsonRequest["image"]);
        }
        if (array_key_exists("inventoryCount", $jsonRequest)) {
            $this->setInventoryCount($jsonRequest["inventoryCount"]);
        }
        if (array_key_exists("price", $jsonRequest)) {
            $this->setPrice($jsonRequest["price"]);
        }
        if (array_key_exists("priceMember", $jsonRequest)) {
            $this->setPriceMember($jsonRequest["priceMember"]);
        }
        if (array_key_exists("visibility", $jsonRequest)) {
            $this->setVisibility($jsonRequest["visibility"]);
        }
        return [
            "success" => true,
            "error" => false,
            "message" => "product has been patched",
        ];
    }

    /**
     * deleteHandler
     *
     * @return array{success:true,error:false,message:string}
     */
    public function deleteHandler(): array
    {
        // deleting the product required the instance to be deleted.
        // otherwise it might cause inconsistencies with database.
        // by using internal boolean value to check if the instance is valid
        // will result in an if statement in all methods. Not nice
        // unset($this) did not work
        throw ApiException::notImplemented();
        //$db = new DB();
        //$db->prepare('DELETE FROM products WHERE id=?');
        //$productId = $this->id;
        //$db->bindParam('i', $productId);
        //$db->execute();
//
        //return [
        //    "success" => true,
        //    "error" => false,
        //    "message" => "product has been deleted",
        //];
    }

    #endregion setters

    #region static public

    public static function productHashExists(string $productHash): bool
    {
        $db = new DB();
        $db->prepare("SELECT COUNT(*) FROM products WHERE hash=?");
        $db->bindParam("s", $productHash);
        $db->execute();
        $result = 0;
        $db->bindResult($result);
        $db->fetch();
        return (bool)$result;
    }

    // TODO: place in store class
    public static function getInventoryCount(string $productHash): int
    {
        $db = new DB();
        $sql = <<<'SQL'
        SELECT 
        (SELECT COUNT(*) FROM orders WHERE productHash=? AND orderStatus=PLACED OR orderStatus=FINALIZED)
        as completedOrders,
        (SELECT inventoryCount FROM products WHERE productHash=?)
        SQL;
        $db->prepare($sql);
        $db->bindParam('ss', $productHash, $productHash);
        $db->execute();
        $completedOrders = 0;
        $inventoryCount = 0;
        $db->bindResult($completedOrders, $inventoryCount);
        if (!$db->fetch()) {
            throw OrderException::orderNotFound();
        }
        return $completedOrders - $inventoryCount;
    }
    /**
     * Get products as an associative array
     * TODO: DateTimeImmutable to unix timestamp
     * @return array<
     * int, array{
     *      productHash: string,
     *      nameJson: string,
     *      descriptionJson: string,
     *      price: int,
     *      priceMember: ?int,
     *      availableFrom: ?\DateTimeImmutable,
     *      availableUntil: ?\DateTimeImmutable,
     *      maxOrdersPerCustomer: ?int,
     *      requireEmail: bool,
     *      requireComment: bool,
     *      requirePhone: bool,
     *      requireMembership: bool,
     *      inventoryCount: ?int,
     *      image: ?string,
     *      visible: bool,
     *      enabled: bool
     *      }
     * >
     */
    public static function getAllAsArray(): array
    {
        $db = new DB();
        $db->prepare("SELECT * FROM products");
        $db->execute();
        // we don't want users to be able to predict an identifier like id for a product.
        // some products can be hidden and accessible only through a link and thus predictable
        // id's are not suitable for this. productHash is a random string which uniquely identifies
        // each product.
        $db->bindResult(
            $_, // hide product id to clients
            $productHash,
            $nameJson,
            $descriptionJson,
            $price,
            $priceMember,
            $availableFrom,
            $availableUntil,
            $maxOrdersPerCustomer,
            $requirePhone,
            $requireEmail,
            $requireComment,
            $requireMembership,
            $inventoryCount,
            $image,
            $visible,
            $enabled,
        );
        $products = [];
        while ($db->fetch()) {
            $product = null;
            $product["productHash"] = $productHash;
            $product["nameJson"] = $nameJson;
            $product["descriptionJson"] = $descriptionJson;
            $product["price"] = $price;
            $product["priceMember"] = $priceMember;
            $product["availableFrom"] = $availableFrom;
            $product["availableUntil"] = $availableUntil;
            $product["maxOrdersPerCustomer"] = $maxOrdersPerCustomer;
            $product["requireEmail"] = (bool)$requireEmail;
            $product["requireComment"] = (bool)$requireComment;
            $product["requirePhone"] = (bool)$requirePhone;
            $product["requireMembership"] = (bool)$requireMembership;
            $product["inventoryCount"] = $inventoryCount;
            $product["image"] = $image;
            $product["visible"] = (bool)$visible;
            $product["enabled"] = (bool)$enabled;
            array_push($products, $product);
        }
        return $products;
    }

    public static function isValidPrice(int $price): bool
    {
        if ($price > self::MAX_PRICE) {
            return false;
        }
        if ($price < self::MIN_PRICE) {
            return false;
        }
        return true;
    }
    #endregion
}
