<?php
require_once("library/util/api.php");
require_once("library/util/enrollment.php");

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

function get_age(DateTime $birthDate): int
{
    $now = new DateTime();
    $interval = $now->diff($birthDate);
    return $interval->y;
}

function valid_captcha(): bool
{
    global $settings;
    $secret = $settings["captcha_key"];
    $token = $_POST['g-recaptcha-response'];
    $url = "https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=$token";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $verify = curl_exec($ch);
    return json_decode($verify)->success;
}

header("Content-Type: application/json; charset=UTF-8");
$input = array();

// block non POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    log::message("Info: Method " . $_SERVER["REQUEST_METHOD"] . " is not accepted", __FILE__, __LINE__);
    http_response_code(405);
    print(json_encode(["error" => true, "message" => "Method " . $_SERVER["REQUEST_METHOD"] . " is not accepted. This endpoint only accepts POST requests."]));
    return;
}
/*
if(!valid_captcha()){
    http_response_code(HTTP_INVALID_REQUEST);
    print(json_encode(["error" => true, "message" => "captcha failed."]));
    return;
}
*/

// check for missing parameters
foreach (["name", "isMale", "phone", "birthDate", "zip", "address", "email"] as $param) {
    if (!isset($_POST[$param])) {
        log::message("Warning: missing parameter, " . $param, __FILE__, __LINE__);
        http_response_code(HTTP_INVALID_REQUEST);
        print(json_encode(["error" => true, "message" => "missing parameter, " . $param]));
        return;
    }
}

// save parameters to local object
array_push($input, [
    "dryrun" => isset($_POST["dryrun"]) ? (bool)$_POST["dryrun"] : false,
    "name" => $_POST["name"],
    "isMale" => isset($_POST["isMale"]) ? (bool)$_POST["isMale"] : false,
    "phone" => $_POST["phone"], // can be string
    "birthDate" => $_POST["birthDate"],
    "zip" => filter_var($_POST["zip"], FILTER_VALIDATE_INT, ["flag" => FILTER_NULL_ON_FAILURE, "min_range" => 1000, "max_range" => 9999]),
    "address" => $_POST["address"],
    "email" => filter_var($_POST["email"], FILTER_VALIDATE_EMAIL,  FILTER_NULL_ON_FAILURE),
    "licensee" => isset($_POST["licensee"]) ? $_POST["licensee"] : "",
    "error" => true,
    "message" => ""
]);
// convert from array of objects to an object. Registers only first user entry.
$input = $input[0];

// if dryrun is enabled no changes to db are made.
if (filter_var($input["dryrun"], FILTER_VALIDATE_BOOLEAN)) {
    $input["dryrun"] = (bool)$_POST["dryrun"];
} else {
    $input["dryrun"] = false;
}

// Validate name
$input["name"] = trim_space($input["name"]);

if (strlen($input["name"]) < 5) {
    log::message("Warning: parameter 'name' is too small", __FILE__, __LINE__);
    http_response_code(HTTP_INVALID_REQUEST);
    $input["message"] = "parameter 'name' is too small";
    print(json_encode($input));
    return;
}

if (strlen($input["name"]) > 40) {
    log::message("Warning: parameter 'name' is greater than 40 characters: ", $input["name"], __FILE__, __LINE__);
    http_response_code(HTTP_INVALID_REQUEST);
    $input["message"] = "parameter 'name' is greater than 40 characters. Send us an email with your id if this is your name.";
    print(json_encode($input));
    return;
}

if (!strpos($input["name"], " ")) {
    log::message("Warning: Space was not found in the name", __FILE__, __LINE__);
    http_response_code(HTTP_INVALID_REQUEST);
    $input["message"] = "No space was found in parameter 'name'. Make sure to use your full name.";
    print(json_encode($input));
    return;
}

if (filter_var($input["isMale"], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === NULL) {
    log::message("Warning: Parameter isMale accepts only boolean", __FILE__, __LINE__);
    http_response_code(HTTP_INVALID_REQUEST);
    print(json_encode($input));
    return;
}

// Phone
// https://github.com/giggsey/libphonenumber-for-php#quick-examples
use \libphonenumber\PhoneNumberUtil;
use \libphonenumber\PhoneNumber;
use \libphonenumber\NumberParseException;
use \libphonenumber\PhoneNumberFormat;

$phone = new PhoneNumber();
try {
    $phoneUtil = PhoneNumberUtil::getInstance();
    $phone = $phoneUtil->parse($input["phone"], "NO");
    $input["phone"] = $phoneUtil->format($phone, PhoneNumberFormat::E164);
} catch (NumberParseException $e) {
    $input["message"] = $e->getMessage();
    print(json_encode($input));
    return;
}

// email
if ($input["email"] === NULL) {
    log::message("Email validation failed", __FILE__, __LINE__);
    http_response_code(HTTP_INVALID_REQUEST);
    $input["message"] = "Failed to validate email";
    print(json_encode($input));
    return;
}

// Age validation
$input["age"] = get_age(new DateTime($input["birthDate"]));
if ($input["age"] < 18) {
    log::message("Warning: User too young", __FILE__, __LINE__);
    http_response_code(HTTP_INVALID_REQUEST);
    $input["message"] = "User too young. Minimum age is 18 years old";
    print(json_encode($input));
    return;
}

// Address and zip
if ($input["zip"] === NULL) {
    log::message("Warning: Could not decode zip", __FILE__, __LINE__);
    http_response_code(HTTP_INVALID_REQUEST);
    $input["message"] = "Could not decode parameter zip";
    print(json_encode($input));
    return;
}

$input["address"] = trim_space($input["address"]);
if (strlen($input["address"]) < 6) {
    log::message("Warning: Address is less than 6 characters", __FILE__, __LINE__);
    http_response_code(HTTP_INVALID_REQUEST);
    $input["message"] = "parameter 'address' is too small";
    print(json_encode($input));
    return;
}

// licensee
$input["licensee"] = trim_space($input["licensee"]);
$clubs = json_decode(file_get_contents("assets/clubs.json"));
if (!value_exists($input["licensee"], $clubs) && $input["licensee"] !== "NTNUI Triatlon" && $input["licensee"] !== "") {
    log::message("Warning: Invalid licensee club", __FILE__, __LINE__);
    http_response_code(HTTP_INVALID_REQUEST);
    $input["message"] = "parameter 'licensee' is not recognized";
    print(json_encode($input));
    return;
}

// sanitize against injections
$input["name"] = htmlspecialchars($input["name"]);
$input["email"] = filter_var($input["email"], FILTER_SANITIZE_EMAIL);
$input["address"] = htmlspecialchars($input["address"]);
$input["licensee"] = htmlspecialchars($input["licensee"]);
$input["first_name"] = get_first_name($input["name"]);
$input["surname"] = get_surname($input["name"]);

// check if phone number has been registered

$db = new DB("member");
$sql = "SELECT approved_date, count(*) AS count FROM member WHERE phone=? GROUP BY approved_date";
$db->prepare($sql);
$db->bind_param("s", $input["phone"]);
$db->execute();
$result = 0;
$approved_date;
$db->stmt->bind_result($approved_date, $result);
$db->fetch();
$input["user_exists"] = (bool)$result;
$input["approved_date"] = isset($approved_date) ? $approved_date : NULL;
$input["membership_status"] = $approved_date ? "active" : "pending";
if ($result) {
    log::message("Warning: User already registered", __FILE__, __LINE__);
    http_response_code(HTTP_INVALID_REQUEST);
    $input["message"] = "User is already registered with " . ($input["membership_status"] === "active" ? "an active" : "a pending") . " membership";
    print(json_encode($input));
    return;
}
$db->reset();

// check for excising CIN number
$sql = "SELECT NSF_CIN FROM member_CIN WHERE hash=?";
$db->prepare($sql);
$input["CIN_hash"] = hash("sha256", $input["birthDate"] . $input["phone"] . $input["isMale"]);
$db->bind_param("s", $input["CIN_hash"]);
$db->execute();
$CIN = 0;
$db->stmt->bind_result($CIN);
$db->fetch();
$input["CIN"] = $CIN;
if ($CIN && !$input["dryrun"]) {
    // update last valid date for CIN number
    $db->stmt->close();
    $db->prepare("UPDATE member_CIN SET last_used=NOW() WHERE NSF_CIN=?");
    $db->bind_param("i", $CIN);
    $db->execute();
}
$db->reset();

// don't perform destructive actions on dryrun
if ($input["dryrun"]) {
    goto return_response;
}

// block registration unless enrollment is open.
if (!enrollment_is_active()) {
    http_response_code(HTTP_FORBIDDEN);
    $input["message"] = "Enrollment is closed";
    print(json_encode($input));
    return;
}

// register

$sql = "INSERT INTO member (first_name, surname, gender, birth_date, phone, email, address, zip, licensee, registration_date) VALUES (?,?,?,?,?,?,?,?,?,NOW())";
$db->prepare($sql);
$gender = $input["isMale"] ? "Male" : "Female";
$first_name = get_first_name($input["name"]);
$surname = get_surname($input["name"]);
$birthDate = date("Y-m-d", strtotime($input["birthDate"]));
$db->bind_param("sssssssis", $first_name, $surname, $gender, $birthDate, $input["phone"], $input["email"], $input["address"], $input["zip"], $input["licensee"]);
$db->execute();

// update CIN number if found
if ($input["CIN"]) {
    $sql = "UPDATE member SET CIN=? WHERE phone=?";
    $db->prepare($sql);
    $db->bind_param("is", $input["CIN"], $input["phone"]);
    $db->execute();
}

// return success
return_response:

http_response_code(HTTP_OK);
$input["error"] = false;
$input["message"] = "Member has been registered successfully";

if ($input["membership_status"] == "pending") {
    if (false && !$input["dryrun"]) {
        // notification to cashier
        $sendTo = $settings["emails"]["analyst"];
        $message = "A new member needs manual approval. Log in to admin pages and approve member.";
        mail($sendTo, "NTNUI-Swimming: New membership request", $message);
    }
    $input["url"] =  $settings["baseurl"] . "/store?product_hash=" . $settings['license_product_hash'];
}

print(json_encode($input));
