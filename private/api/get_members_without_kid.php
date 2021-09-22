<?php

// Remove randoms from the internet
if (!Authenticator::is_logged_in()){
    log::forbidden("Access denied", __FILE__, __LINE__);
}

// remove peasants from styret
if (!$access_control->can_access("api", "KID")) {
    log::message("Access denied for " . Authenticator::get_username(), __FILE__, __LINE__);
    log::forbidden("Access denied", __FILE__, __LINE__);
}

// connect to server
include_once("library/util/db.php");
$conn = connect("medlem");

global $settings;
$table = $settings["memberTable"];
$sql = "SELECT id, fornavn, etternavn, phoneNumber, epost FROM ${settings['memberTable']} WHERE triatlon LIKE 0 AND kontrolldato IS NOT NULL AND kid IS NULL";

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
