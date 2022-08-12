<?php

declare(strict_types=1);

require_once("Library/Util/Hash.php");
require_once("Library/Util/Db.php");

class Cin
{
    public static function updateLastUsedDate(Hash $memberHash): int
    {
        $db = new DB('web');
        $db->prepare('UPDATE cin SET lastUsed=NOW() WHERE hash=?');
        $db->bindParam('s', $memberHash);
        $db->execute();
        $db->numRows();
        $cinId = $db->insertedId();
        if($db->affectedRows() === 0){
            throw new CinNotFoundException();
        }

    }

    public static function updateLastUsedDate(Hash $memberHash): bool
    {
   
    }

public static function updateCin(int $cin): bool
    {
        
    }
public static function create(int $cin): bool
    {
        
    }

}
