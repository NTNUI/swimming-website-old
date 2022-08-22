<?php

declare(strict_types=1);

namespace NTNUI\Swimming\Util;

use DateTime;
use NTNUI\Swimming\Util\DB;
use NTNUI\Swimming\Util\Hash;
use NTNUI\Swimming\Exception\Api\CinException;

/**
 * @property-read int $cin
 * @property-read Hash $memberHash
 * @property-read DateTime $lastUsed
 */
class Cin
{
    public const DATE_FORMAT = "Y-m-d"; // https://www.php.net/manual/en/datetime.format.php
    public const TIME_ZONE = "Europe/Oslo";

    private int $cin;
    private Hash $memberHash;
    private \DateTime $lastUsed;

    public function __get(string $name): int|Hash|\DateTime
    {
        return $this->$name;
    }

    private function __construct(
        public readonly int $id,
        int $cin,
        string $memberHash,
        string $lastUsed,
    ) {
        if ($cin > 99999999 || $cin < 10000000) {
            throw CinException::cinInvalid();
        }
        $this->cin = $cin;
        $this->memberHash = new Hash($memberHash);
        $this->lastUsed = \DateTime::createFromFormat(self::DATE_FORMAT, $lastUsed, new \DateTimeZone(self::TIME_ZONE));
    }

    public static function new(int $cin, Hash $memberHash): self
    {
        $db = new DB();
        $db->prepare('INSERT INTO cin VALUES cin=?, memberHash=?');
        $hash = $memberHash->get();
        $db->bindParam('is', $cin, $hash);
        $db->execute();
        $cinId = $db->insertedId();
        $db->prepare("SELECT * FROM cin WHERE id=?");
        $db->bindParam("i", $cinId);
        $cin = 0;
        $lastUsedString = "";
        $db->execute();
        $db->bindResult($id, $cin, $_, $lastUsedString);
        $db->fetch();
        return new self(
            id: $id,
            cin: $cin,
            memberHash: $hash,
            lastUsed: $lastUsedString
        );
    }

    public static function fromId(int $cinId): self
    {
        $db = new DB();
        $db->prepare('SELECT * FROM cin WHERE id=?');
        $db->bindParam('i', $cinId);
        $db->execute();
        $cin = 0;
        $lastUsedString = "";
        $hash = "";
        $db->bindResult($_, $cin, $hash, $lastUsedString);
        $db->fetch();
        return new self(
            id: $cinId,
            cin: $cin,
            memberHash: $hash,
            lastUsed: $lastUsedString
        );
    }

    public static function fromMemberHash(Hash $memberHash): self
    {
        $db = new DB();
        $db->prepare('SELECT * FROM cin WHERE memberHash=?');
        $hash = $memberHash->get();
        $db->bindParam('s', $hash);
        $db->execute();
        $cinId = 0;
        $cin = 0;
        $lastUsedString = "";
        $db->bindResult($cinId, $cin, $_, $lastUsedString);
        if (!$db->fetch()) {
            // no rows were fetched.
            throw CinException::cinNotFound();
        }
        return new self(
            id: $cinId,
            cin: $cin,
            memberHash: $hash,
            lastUsed: $lastUsedString
        );
    }

    public function touch(): void
    {
        $db = new DB();
        $db->prepare('UPDATE cin SET lastUsed=NOW() WHERE id=?');
        $cinId = $this->id;
        $db->bindParam("i", $cinId);
        $db->execute();
    }

    public function updateCin(int $cin): void
    {
        if ($cin > 99999999 || $cin < 10000000) {
            throw CinException::cinInvalid();
        }
        $db = new DB();
        $db->prepare("UPDATE cin SET VALUE cin=? WHERE id=?");
        $cinId = $this->id;
        $db->bindParam("ii", $cin, $cinId);
        $db->execute();
    }
}
