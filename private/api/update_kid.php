<?php
declare(strict_types=1);

require_once("library/util/db.php");

function valid_KID($KID){
    if($KID > 99999999){
        return false;
    }

    if ($KID < 10000000){
        return false;
    }
    return true;
}

function valid_ID($ID){
    if ($ID > 0){
        return true;
    }
    return false;
}

function save_CIN(int $member_id, int $CIN){
    $hash="";
    // calculate the hash of member_id
    {
        $db = new DB("member");
        $sql = "SELECT gender, birth_date, phone FROM member WHERE member.id=?";
        $db->prepare($sql);
        $db->bind_param("i", $member_id);
        $db->execute();
        $gender = "";
        $birthDate = 0;
        $phone = "";
        $db->stmt->bind_result($gender, $birthDate, $phone);
        $hash = hash("sha256", $birthDate . $phone . $gender == "Male" ? true : false);
    }
    // save CIN number
    {
        $db = new DB("member");
        $sql = "SELECT COUNT(*) FROM member_CIN WHERE hash=?";
        $db->prepare($sql);
        $db->bind_param("s", $hash);
        $db->execute();
        if($db->num_rows()){
            // entry exists
            $db->stmt->close();
            $sql = "UPDATE member_CIN SET NSF_CIN=?, last_used=NOW() WHERE hash=?";
        }else{
            // entry does not exist
            $db->stmt->close();
            $sql = "INSERT INTO member_CIN (NSF_CIN, last_used, hash) VALUES (?,NOW(),?)";
        }
        $db->prepare($sql);
        $db->bind_param("is", $CIN, $hash);
        $db->execute();
    }
}

// remove randoms from the internet
if (!Authenticator::is_logged_in()){
    log::forbidden("Access denied", __FILE__, __LINE__);
}

// remove peasants from the board
if (!$access_control->can_access("api", "kid")) {
    log::message("Info: Access denied for " . Authenticator::get_username(), __FILE__, __LINE__);
    log::forbidden("Access denied", __FILE__, __LINE__);
}

// connect to server
$db = new DB("member");

// Get values
$ID = 0;
$CIN = 0;
if (isset($_GET["ID"]) && intval($_GET["ID"])){
    $ID = $_GET["ID"];
}

if (isset($_GET["KID"]) && intval($_GET["KID"])){
    $CIN = $_GET["KID"];
}


// Validate variables
if (!valid_KID($CIN)){
    log::message("Error: invalid input kid: $CIN", __FILE__, __LINE__);
    die("Error: KID is not valid");
}

if (!valid_ID($ID)){
    log::message("Error: invalid input id: $ID", __FILE__, __LINE__);
    die("Error: ID is not valid");
}

// Update database
global $settings;
$sql = "UPDATE member SET `CIN`=? WHERE id=?";
$db->prepare($sql);
$db->bind_param("si", $CIN, $ID);
$db->execute();
save_CIN($ID, $CIN);

http_response_code(200);
