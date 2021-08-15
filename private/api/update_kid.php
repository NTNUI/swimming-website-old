<?php

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

// remove randoms from the internet
include_once("library/util/db.php");
if (!Authenticator::is_logged_in()){
    log::forbidden("Access denied", __FILE__, __LINE__);
}

// remove peasents from styret
if (!$access_control->can_access("api", "KID")) {
    log::message("Access denied for " . Authenticator::get_username(), __FILE__, __LINE__);
    log::forbidden("Access denied", __FILE__, __LINE__);
}

// connect to server
$conn = connect("medlem");

// Get values
$ID = 0;
$KID = 0;
if (isset($_GET["ID"]) && intval($_GET["ID"])){
    $ID = $_GET["ID"];
}

if (isset($_GET["KID"]) && intval($_GET["KID"])){
    $KID = $_GET["KID"];
}


// Validate variables
if (!valid_KID($KID)){
    log::message("Error: invalid input kid: $KID", __FILE__, __LINE__);
    die("Error: KID is not valid");
}

if (!valid_ID($ID)){
    log::message("Error: invalid input id: $ID", __FILE__, __LINE__);
    die("Error: ID is not valid");
}

// Update database
global $settings;
$sql = "UPDATE medlem SET `KID`=? WHERE id=?";
$query = $conn->prepare($sql);
$query->bind_param("si", $KID, $ID);
$result = $query->execute();
$query->close();
$conn->close();

http_response_code(200);
?>
