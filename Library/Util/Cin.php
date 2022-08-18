<?php

declare(strict_types=1);

require_once(__DIR__ . "/Hash.php");
require_once(__DIR__ . "/Db.php");

/**
 * @property-read int $cin
 * @property-read Hash $memberHash
 * @property-read DateTime $lastUsed
 */
class Cin
{
    const DATE_FORMAT = "Y-m-d"; // https://www.php.net/manual/en/datetime.format.php
    const TIME_ZONE = "Europe/Oslo";

    private int $cin;
    private Hash $memberHash;
    private DateTime $lastUsed;

    public function __get(string $name): int|Hash|DateTime
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
            throw new cinInvalidException();
        }
        $this->cin = $cin;
        $this->memberHash = new Hash($memberHash);
        $this->lastUsed = DateTime::createFromFormat(self::DATE_FORMAT, $lastUsed, new DateTimeZone(self::TIME_ZONE));
    }

    public static function new(int $cin, Hash $memberHash): self
    {

        $db = new DB();
        $db->prepare('INSERT INTO cin VALUES cin=?, memberHash=?');
        $db->bindParam('is', $cin, $memberHash->get());
        $db->execute();
        $cinId = $db->insertedId();
        $db->prepare("SELECT * FROM cin WHERE id=?");
        $db->bindParam("i", $cinId);
        $cin = 0;
        $lastUsedString = "";
        $hash = "";
        $db->execute();
        $db->bindResult($id, $cin, $hash, $lastUsedString);
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
            throw new CinNotFoundException();
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
        $db->bindParam("i", $this->id);
        $db->execute();
    }

    public function updateCin(int $cin): void
    {
        if ($cin > 99999999 || $cin < 10000000) {
            throw new cinInvalidException();
        }
        $db = new DB();
        $db->prepare("UPDATE cin SET VALUE cin=? WHERE id=?");
        $db->bindParam("ii", $cin, $this->id);
        $db->execute();
    }
}
