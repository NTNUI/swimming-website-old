<?php

// remove randoms from the internet

session_start();
if (!Authenticator::is_logged_in()){
    header("HTTP/1.0 403 You need to log in first");
    die("Access denied");
}

// remove peasents from styret
if (!$access_control->can_access("api", "KID")) {
    header("HTTP/1.0 403 Forbidden");
    log::message("Access denied for " . Authenticator::get_username(), __FILE__, __LINE__);
    die("You do not have access to this page");
}

// connect to server
include_once("library/util/db.php");
$conn = connect("medlem");

global $settings;
$table = $settings["memberTable"];
// TODO: remove unessesarry info
$sql = "SELECT id, fornavn, etternavn, phoneNumber, epost FROM ${settings['memberTable']} WHERE `KID` = '' AND kontrolldato IS NOT NULL";

$query = $conn->prepare($sql);
if (!$query->execute()) {
    log::die("Could not execute query",__FILE__, __LINE__);
}

$query->bind_result($id, $first, $last, $phone, $email);
if(!$query){
    log::die("Could not bind results", __FILE__, __LINE__);
}
$result = [];
while($query->fetch()) {
    $result[] = array(
        "id" => $id,
        "name" => "$first $last",
        "email" => $email,
        "phone" => $phone
    );
}
$query->close();
$conn->close();

header("Content-type: application/json");
print json_encode($result);
return;

?>
