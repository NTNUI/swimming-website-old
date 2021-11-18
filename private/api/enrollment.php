<?php
global $settings;
function trim_space(string $text): string
{
    $text = trim($text);
    while (true) {
        $result = str_replace("  ", " ", $text);
        if ($result === $text) {
            return $text;
        } else {
            $text = $result;
        }
    }
}

function get_first_name(string $name): string
{
    return substr($name, 0, (strlen($name) - strpos(strrev($name), " ") - 1));
}

function get_surname(string $name): string
{
    return substr($name, strlen($name) - strpos(strrev($name), " "));
}

function value_exists(string $needle, array $haystack): bool
{
    foreach ($haystack as $_ => $value) {
        if ($needle === $value)
            return true;
    }
    return false;
}

header("Content-Type: application/json; charset=UTF-8");
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    print(json_encode(["error" => "Method " . $_SERVER["REQUEST_METHOD"] . " is not accepted. This endpoint only accepts POST requests."]));
    return;
}

// Input is storing input values and modifies them during the execution by validating and sanitizing the input.
// New information are also added to this input.
$input = array();

foreach (["name", "isMale", "phoneNumber", "birthDate", "zip", "address", "email"] as $param) {
    if (!isset($_POST[$param])) {
        http_response_code(400);
        print(json_encode(["error" => "missing parameter, " . $param]));
        return;
    }
}

array_push($input, [
    "dryrun" => isset($_POST["dryrun"]) ? $_POST["dryrun"] : "false",
    "name" => $_POST["name"],
    "isMale" => $_POST["isMale"],
    "phoneNumber" => $_POST["phoneNumber"], // can be string
    "birthDate" => $_POST["birthDate"],
    "zip" => filter_var($_POST["zip"], FILTER_VALIDATE_INT, ["flag" => FILTER_NULL_ON_FAILURE, "min_range" => 1000, "max_range" => 9999]),
    "address" => $_POST["address"],
    "email" => filter_var($_POST["email"], FILTER_VALIDATE_EMAIL,  FILTER_NULL_ON_FAILURE),
    "licensee" => isset($_POST["licensee"]) ? $_POST["licensee"] : "",
]);
$input = $input[0];
// validate input
if (strlen($input["name"]) < 5) {
    http_response_code(400);
    print(json_encode(["error" => "parameter 'name' is too small"]));
    return;
}

$input["name"] = trim_space($input["name"]);
if (!strpos($input["name"], " ")) {
    http_response_code(400);
    print(json_encode(["error" => "Space was not found in the name. Make sure that you pass inn full name. Not just first name."]));
    return;
}
if (strcmp($input["isMale"], "") === 0) {
    http_response_code(400);
    print(json_encode(["error" => "Parameter isMale is not set."]));
    return;
}

if (filter_var($input["isMale"], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === null) {
    http_response_code(400);
    print(json_encode(["error" => "Parameter isMale accepts only strings '1', 'true', 'on', 'yes', '0', 'false', 'off' and 'no'."]));
    return;
}
if (filter_var($input["dryrun"], FILTER_VALIDATE_BOOLEAN)) {
    $input["dryrun"] = $_POST["dryrun"];
} else {
    $input["dryrun"] = false;
}
if (strlen($input["phoneNumber"]) < 8) {
    http_response_code(400);
    print(json_encode(["error" => "Phone number is less than 8 characters"]));
    return;
}
if ($input["zip"] === null) {
    http_response_code(400);
    print(json_encode(["error" => "Could not decode zip"]));
    return;
}
$input["address"] = trim_space($input["address"]);
if (strlen($input["address"]) < 6) {
    http_response_code(400);
    print(json_encode(["error" => "Address is less than 6 characters"]));
    return;
}
if ($input["email"] === null) {
    http_response_code(400);
    print(json_encode(["error" => "Email validation failed"]));
    return;
}

$input["licensee"] = trim_space($input["licensee"]);
$clubs = json_decode(file_get_contents("assets/clubs.json"));
if (!value_exists($input["licensee"], $clubs) && $input["licensee"] !== "NTNUI Triatlon" && $input["licensee"] !== "") {
    http_response_code(400);
    print(json_encode(["error" => "Invalid licensee club. If this is a mistake, register without one and contact us."]));
    return;
}
$input["name"] = filter_var($input["name"], FILTER_SANITIZE_STRING);
$input["email"] = filter_var($input["email"], FILTER_SANITIZE_EMAIL);
$input["phoneNumber"] = filter_var($input["phoneNumber"], FILTER_SANITIZE_STRING);
$input["address"] = filter_var($input["address"], FILTER_SANITIZE_STRING);
$input["licensee"] = filter_var($input["licensee"], FILTER_SANITIZE_STRING);
$input["first_name"] = get_first_name($input["name"]);
$input["surname"] = get_surname($input["name"]);

// check for excising entries
{
    $db = new DB("member");
    $sql = "SELECT id FROM ${settings['memberTable']} WHERE phoneNumber=?";
    $db->prepare($sql);
    $db->bind_param("s", $input["phoneNumber"]);
    $db->execute();
    $result = 0;
    $db->stmt->bind_result($result);
    $db->stmt->fetch();
    $input["user_exists"] = (bool)$result;
    if ($result) {
        http_response_code(400);
        print(json_encode(["error" => "User already registered."]));
        return;
    }
}
// check for excising CIN number
{
    $db = new DB("member");
    $sql = "SELECT NSF_CIN FROM member_CIN WHERE hash=?";
    $db->prepare($sql);
    $input["hash"] = hash("sha256", $input["birthDate"] . $input["phoneNumber"] . $input["isMale"]);
    $db->bind_param("s", $input["hash"]);
    $db->execute();
    $CIN = 0;
    $db->stmt->bind_result($CIN);
    $db->stmt->fetch();
    $input["CIN"] = $CIN;
    if ($CIN && !$input["dryrun"]) {
        // update last valid date for CIN number
        $db->stmt->close();
        $db->prepare("UPDATE member_CIN SET last_used=NOW() WHERE NSF_CIN=?");
        $db->bind_param("i", $CIN);
        $db->execute();
    }
}
// register
if (!$input["dryrun"]) {
    $db = new DB("member");
    $sql = "INSERT INTO member (first_name, surname, gender, birth_date, phone_number, email, address, zip, licensee, CIN, registration_date) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())";
    $db->prepare($sql);

    $gender = $input["isMale"] ? "Male" : "Female";
    $first_name = get_first_name($input["name"]);
    $surname = get_surname($input["name"]);
    $birthDate = date("Y-m-d", strtotime($input["birthDate"]));
    
    $db->bind_param("sssssssisi", $first_name, $surname, $gender , $birthDate, $input["phoneNumber"], $input["email"], $input["address"], $input["zip"], $input["licensee"], $input["CIN"]);
    $db->execute();
}

http_response_code(200);
$input["status"] = "Member has been registered successfully";

if ($input["licensee"] == "") {
    $input["further_action"] = "Payment";
    $input["url"] =  $settings["baseurl"] . "/store?item_id=" . $settings['license_store_item_id'];
} else {
    $input["further_action"] = "manual_approval";
}
print(json_encode($input));
