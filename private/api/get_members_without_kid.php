<?php
require_once("library/util/db.php");

// Remove randoms from the internet
if (!Authenticator::is_logged_in()){
    log::forbidden("Access denied", __FILE__, __LINE__);
}

// remove peasants from styret
if (!$access_control->can_access("api", "kid")) {
    log::message("Info: Access denied for " . Authenticator::get_username(), __FILE__, __LINE__);
    log::forbidden("Access denied", __FILE__, __LINE__);
}

// connect to server
$db = new DB("member");

global $settings;
// get all approved members without CIN number
$sql = "SELECT id, first_name, surname, phone, email FROM member WHERE licensee = '' AND approved_date IS NOT NULL AND CIN = 0 AND license_forwarded = 0";

$db->prepare($sql);
$db->execute();
$db->stmt->bind_result($id, $first, $last, $phone, $email);
$result = [];
while($db->fetch()) {
    $result[] = array(
        "id" => $id,
        "name" => "$first $last",
        "email" => $email,
        "phone" => $phone
    );
}
header("Content-type: application/json");
print json_encode($result);
return;

?>
