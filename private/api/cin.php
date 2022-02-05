<?php

require_once("library/util/api.php");
require_once("library/util/db.php");

$response = new Response();
$json = file_get_contents("php://input");
$input = json_decode($json);

if (!$access_control->can_access("api", "cin")) {
    log::message("Info: Access denied. " . Authenticator::get_username() || "User not logged in.", __FILE__, __LINE__);
    log::forbidden("Access denied", __FILE__, __LINE__);
}

try {
    switch ($_SERVER['REQUEST_METHOD']) {
            /**
         * js fetch() throws an error if GET requests has a body.
         * You can read the long discussion about it here:
         * https://github.com/whatwg/fetch/issues/83
         * 
         * Network Working Group is drafting a new method called SEARCH.
         * follow the progress here: https://datatracker.ietf.org/doc/html/draft-snell-search-method
         * Though it's not implemented or will likely be (because argument against it is just use POST).
         * I'm not creating anything, GET is not allowed, so I'll create my own method inspired by the draft.
         */
        case "SEARCH":
            switch ($input->function) {
                case 'get_missing':
                    $response->data = get_missing();
                    break;
                case 'get_not_payed':
                    $response->data = get_not_payed();
                    break;
                default:
                    throw new InvalidArgumentException("Accepting only 'missing' or 'not_payed'");
                    break;
            }
            break;
        case "PATCH":
            switch ($input->function) {
                case 'patch_cin':
                    patch_cin($input->args->id, $input->args->cin);
                    $response->data = ["success" => true, "message" => "Customer identification number has been saved"];
                    break;
                case 'set_forwarded':
                    if (gettype($input->args->cin) === "array") {
                        foreach (($input->args->cin) as $cin) {
                            if (is_numeric($cin)) {
                                set_forwarded(intval($cin));
                            }
                        }
                    } else {
                        if (is_numeric($cin)) {
                            set_forwarded(intval($cin));
                        }
                    }
                    $response->data = ["success" => true, "message" => "CIN numbers has been successfully updated"];
                    break;
            }

            break;

        default:
            throw new InvalidArgumentException("unsupported request method: " . $_SERVER['REQUEST_METHOD'] . ". Supported methods are SEARCH and PATCH");
            break;
    }
    $response->code = HTTP_OK;
} catch (InvalidArgumentException $ex) {
    $response->code = HTTP_INVALID_REQUEST;
    $response->data = [
        "error" => $ex->getMessage(),
        "backtrace" => $ex->getTraceAsString()
    ];
} catch (Exception | mysqli_sql_exception | Throwable $ex) {
    $response->code = HTTP_INTERNAL_SERVER_ERROR;
    $response->data = [
        "error" => $ex->getMessage(),
        "backtrace" => $ex->getTraceAsString()
    ];
} finally {
    $response->send();
    exit();
}

function patch_cin(int $member_id, int $cin): void
{
    $db = new DB("member");
    // Get data to generate hash
    $db->prepare("SELECT birth_date, phone, gender FROM member WHERE id=?");
    $db->bind_param("i", $member_id);
    $db->execute();
    $birth_date = NULL;
    $phone = NULL;
    $gender = NULL;
    $db->stmt->bind_result($birth_date, $phone, $gender);
    $db->stmt->close();
    if ($birth_date === NULL || $phone === NULL || $gender === NULL) {
        throw new Exception("Could not retrieve personal info");
    }

    // save CIN to users hash
    $hash = hash("sha256", $birth_date . $phone . strval($gender ? 1 : 0));
    $db->prepare("INSERT INTO member_CIN (hash, NSF_CIN, last_used) VALUES (?, ?, NOW())");
    $db->bind_param("si", $hash, $cin);
    $db->execute();

    // save CIN to user
    $db->prepare("UPDATE member SET cin = ? WHERE id = ?");
    $db->bind_param("ii", $cin, $member_id);
    $db->execute();
    $db->stmt->close();
}


/**
 * Set license_forwarded to true for a user with @param CIN
 *
 * @param integer $cin_number
 * @return void
 */
function set_forwarded(int $cin_number): void
{
    $db = new DB("member");
    $sql = "UPDATE member SET license_forwarded=1 WHERE cin =? AND approved_date IS NOT NULL";
    $db->prepare($sql);
    $db->bind_param("i", $cin_number);
    $db->execute();
}

/**
 * Get all members with missing CIN number
 *
 * @return array
 */
function get_missing(): array
{
    $db = new DB("member");
    $sql = "SELECT id, first_name, surname, gender, birth_date, phone, email, address, zip
            FROM member WHERE licensee = '' AND
            approved_date IS NOT NULL AND CIN IS NULL AND license_forwarded = 0";
    $db->prepare($sql);
    $db->execute();

    $meta = $db->stmt->result_metadata();
    if ($meta === false) {
        throw new mysqli_sql_exception("Could not retrieve metadata");
    }
    $params = [];
    $row = [];
    while ($field = $meta->fetch_field()) {
        $params[] = &$row[$field->name];
    }
    $result = array();
    call_user_func_array(array($db->stmt, 'bind_result'), $params);
    while ($db->stmt->fetch()) {
        foreach ($row as $key => $val) {
            $c[$key] = $val;
        }
        $result[] = $c;
    }
    return $result;
}

function get_not_payed(): array
{
    $db = new DB("member");
    // Has a valid CIN number but license is not forwarded.
    $sql = "SELECT id, CIN AS cin FROM member WHERE licensee = '' AND approved_date IS NOT NULL AND CIN IS NOT NULL AND license_forwarded = 0";
    $db->prepare($sql);
    $db->execute();

    $meta = $db->stmt->result_metadata();
    if ($meta === false) {
        throw new mysqli_sql_exception("Could not retrieve metadata");
    }
    $params = [];
    $row = [];
    while ($field = $meta->fetch_field()) {
        $params[] = &$row[$field->name];
    }
    $result = array();
    call_user_func_array(array($db->stmt, 'bind_result'), $params);
    while ($db->stmt->fetch()) {
        foreach ($row as $key => $val) {
            $c[$key] = $val;
        }
        $result[] = $c;
    }
    return $result;
}
