<?php

declare(strict_types=1);

require_once(__DIR__ . "/Hash.php");
require_once(__DIR__ . "/Db.php");

class Cin
{
    const DATE_FORMAT = "Y-m-d H:i:s"; // https://www.php.net/manual/en/datetime.format.php
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
        $lastUsed,
    ) {
        // TODO: validate cin
        if($cin > 99999999 || $cin < 10000000){
            throw new cinCreateException();
        }
        $this->id = $id;
        $this->cin = $cin;
        $this->memberHash = new Hash($memberHash);
        $this->lastUsed = new DateTime($lastUsed, new DateTimeZone(self::TIME_ZONE));
    }

    public static function new(int $cin, Hash $memberHash): self
    {
        $db = new DB('web');
        $db->prepare('INSERT INTO cin VALUES cin=?, memberHash=?, lastUsed');
        $db->bindParam('s', $memberHash->get());
        $db->execute();
        $id = 0;
        $cin = 0;
        $lastUsed = 0;
        $hash = "";
        $db->bindResult($id, $cin, $hash, $lastUsed);
        $db->fetch();
        return new self($id, $cin, $hash, $lastUsed);
    }

    public static function fromId(int $id): self
    {
        $db = new DB('web');
        $db->prepare('SELECT * FROM cin WHERE id=?');
        $db->bindParam('i',$id); 
        $db->execute();
        $id = 0;
        $cin = 0;
        $lastUsed = 0;
        $hash = "";
        $db->bindResult($id, $cin, $hash, $lastUsed);
        $db->fetch();
        return new self($id, $cin, $hash, $lastUsed);
    }

    public static function fromCin(int $cin): self
    {
        $db = new DB('web');
        $db->prepare('SELECT * FROM cin WHERE cin=?');
        $db->bindParam('i', $cin);
        $db->execute();
        $id = 0;
        $cin = 0;
        $lastUsed = 0;
        $hash = "";
        $db->bindResult($id, $cin, $hash, $lastUsed);
        $db->fetch();
        return new self($id, $cin, $hash, $lastUsed);
    }

    public static function fromMemberHash(Hash $memberHash): self
    {
        $db = new DB('web');
        $db->prepare('SELECT * FROM cin WHERE memberHash=?');
        $db->bindParam('s', $memberHash->get());
        $db->execute();
        $id = 0;
        $cin = 0;
        $lastUsed = 0;
        $hash = "";
        $db->bindResult($id, $cin, $hash, $lastUsed);
        $db->fetch();
        return new self($id, $cin, $hash, $lastUsed);
    }
}
