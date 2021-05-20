<?php

// remove randoms from the internet

session_start();
if ($_SESSION['logged_in'] != 1){
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
include_once("library/util/db.php");
$conn = connect("medlem");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

global $settings;
$table = $settings["memberTable"];
// TODO: remove unessesarry info
$sql = "SELECT id, fornavn, etternavn, phoneNumber, epost FROM " . $table . " WHERE `KID` = '' AND kontrolldato IS NOT NULL AND triatlon = 0";

$query = $conn->prepare($sql);
if (!$query->execute()) {
    print "error";
    return;
}

$query->bind_result($id, $first, $last, $phone, $email);
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
