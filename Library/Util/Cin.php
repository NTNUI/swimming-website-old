<?php

declare(strict_types=1);

require_once(__DIR__ . "/Hash.php");
require_once(__DIR__ . "/Db.php");

class Cin
{
    const DATE_FORMAT = "Y-m-d"; // https://www.php.net/manual/en/datetime.format.php
    const TIME_ZONE = "Europe/Oslo";

    private int $id;
    private int $cin;
    private Hash $memberHash;
    private DateTime $lastUsed;

    public function __get(string $name): int|Hash|DateTime
    {
        return $this->$name;
    }

    private function __construct(
        int $id,
        int $cin,
        string $memberHash,
        DateTime $lastUsed,
    ) {
        if ($cin > 99999999 || $cin < 10000000) {
            throw new cinInvalidException();
        }
        $this->id = $id;
        $this->cin = $cin;
        $this->memberHash = new Hash($memberHash);
        $this->lastUsed = $lastUsed;
    }

    public static function new(int $cin, Hash $memberHash): self
    {

        $db = new DB();
        $db->prepare('INSERT INTO cin VALUES cin=?, memberHash=?');
        $db->bindParam('is', $cin, $memberHash->get());
        $db->execute();
        $id = 0;
        $cin = 0;
        $lastUsed = "";
        $hash = "";
        $db->bindResult($id, $cin, $hash, $lastUsed);
        $db->fetch();
        return new self($id, $cin, $hash, DateTime::createFromFormat(self::DATE_FORMAT, $lastUsed, new DateTimeZone(self::TIME_ZONE)));
    }

    public static function fromId(int $id): self
    {
        $db = new DB();
        $db->prepare('SELECT * FROM cin WHERE id=?');
        $db->bindParam('i', $id);
        $db->execute();
        $id = 0;
        $cin = 0;
        $lastUsed = "";
        $hash = "";
        $db->bindResult($id, $cin, $hash, $lastUsed);
        $db->fetch();
        return new self($id, $cin, $hash, DateTime::createFromFormat(self::DATE_FORMAT, $lastUsed, new DateTimeZone(self::TIME_ZONE)));
    }

    // public static function fromCin(int $cin): self
    // {
    //     $db = new DB();
    //     $db->prepare('SELECT * FROM cin WHERE cin=?');
    //     $db->bindParam('i', $cin);
    //     $db->execute();
    //     $id = 0;
    //     $cin = 0;
    //     $lastUsed = 0;
    //     $hash = "";
    //     $db->bindResult($id, $cin, $hash, $lastUsed);
    //     $db->fetch();
    //     return new self($id, $cin, $hash, $lastUsed);
    // }

    public static function fromMemberHash(Hash $memberHash): self
    {
        $db = new DB();
        $db->prepare('SELECT * FROM cin WHERE memberHash=?');
        $db->bindParam('s', $memberHash->get());
        $db->execute();
        $id = 0;
        $cin = 0;
        $lastUsed = "";
        $hash = "";
        $db->bindResult($id, $cin, $hash, $lastUsed);
        if (!$db->fetch()) {
            // no rows were fetched.
            throw new CinNotFoundException();
        }
        return new self($id, $cin, $hash, DateTime::createFromFormat(self::DATE_FORMAT, $lastUsed, new DateTimeZone(self::TIME_ZONE)));
    }

    public function touch(): void
    {
        $db = new DB();
        $db->prepare('UPDATE cin SET lastUsed=NOW() WHERE id=' . $this->id);
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
