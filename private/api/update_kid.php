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
session_start();
include_once("library/util/db.php");
if ($_SESSION['innlogget'] != 1){
    header("HTTP/1.0 403 You need to log in first");
    print("access denied");
    return;
}

// remove peasents from styret
if (!$access_control->can_access("api", "KID")) {
    header("HTTP/1.0 403 Forbidden");
    die("You do not have access to this page");
}


// connect to server
$conn = connect("medlem");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}


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
    die("Error: KID is not valid");
}

if (!valid_ID($ID)){
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