<?php
// TODO: new function: Generate random password
// TODO: new function: require password reset on user_id
// TODO: merge with access_control class

// Static class Authenticator provides object less authentication services.
class Authenticator
{
    // Log user in given a username and a password. Check if login is successful with is_logged_in()
    static public function log_in($username, $password)
    {
        if (Authenticator::is_logged_in()) {
            log::die("Trying to log in even though a user is logged in", __FILE__, __LINE__);
        }
        $password_hash = (new Authenticator)->load_from_db($username);
        if (password_verify($password, $password_hash)) {
            $_SESSION["logged_in"] = 1;
            $_SESSION["username"] = $username;
            return true;
        }
        return false;
    }

    // returns true if the user has POST'ed username and password
    static public function has_posted_login_credentials()
    {
        if (Authenticator::is_logged_in()) {
            log::die("User is POSTing credentials while being logged in", __FILE__, __LINE__);
        }
        return argsURL("POST", "username") && argsURL("POST", "password");
    }

    // returns true if user has POST'ed username, old password and two identical new passwords.
    // to be used when updating old password.
    // TODO: remove if this should be a non authenticator problem
    static public function has_posted_updated_credentials()
    {
        if (!Authenticator::is_logged_in()) {
            log::die("Non logged in user tries to POST credentials", __FILE__, __LINE__);
        }
        return argsURL("POST", "new_pass1") && argsURL("POST", "new_pass2");
    }

    // returns if the user is logged in
    static public function is_logged_in()
    {
        return argsURL("SESSION", "logged_in") == 1;
    }

    // Returns true if user wants to log out
    static public function log_out_requested()
    {
        if (!Authenticator::is_logged_in()) {
            log::die("User is not logged in.", __FILE__, __LINE__);
        }
        return argsURL("REQUEST", "action") == "logout";
    }

    // @returns string|null
    static public function get_username()
    {
        if (!Authenticator::is_logged_in()) {
            return null;
        }
        return $_SESSION["username"];
    }

    // Log out currently logged in user
    static public function log_out()
    {
        if (!Authenticator::is_logged_in()) {
            log::die("Cannot log out a user that is not logged in", __FILE__, __LINE__);
            return;
        }
        return session_unset() && session_destroy();
    }

    // returns true if user needs or requested to change password
    static public function pass_change_requested()
    {

        $password_date = argsURL("SESSION", "password_date");
        if (!Authenticator::is_logged_in()) {
            log::die("User is not logged in", __FILE__, __LINE__);
        }

        if (!$password_date) {
            return true;
        }

        $deadline = new DateTime("now");
        $oneYear = new DateInterval("P365D");
        return $password_date > date_add($deadline, $oneYear) || argsURL("REQUEST", "action") == "changepass";
    }

    // returns a string with an error if error is present. empty string is returned on no error
    static public function validate_new_passwords($password, $password2)
    {
        $username = argsURL("SESSION", "username");
        if (!Authenticator::is_logged_in() || !$username) {
            log::die("ERROR: User is not logged in", __FILE__, __LINE__);
        }

        if ($password !== $password2) {
            return "New passwords are not equal";
        }

        if (strlen($password) < 9) {
            return "Password is too weak";
        }


        $password_hash = (new Authenticator)->load_from_db($username);
        $success = password_verify($password, $password_hash);
        if ($success) {
            return "Old password and new are the same";
        }
        return "";
    }

    static public function get_name()
    {
        if (!Authenticator::is_logged_in()) {
            return null;
        }
        return argsURL("SESSION", "name");
    }

    // Updates password for currently logged in user
    static public function update_password($new_password)
    {
        $username = argsURL("SESSION", "username");

        if (!Authenticator::is_logged_in() || !$username) {
            log::die("ERROR: User is not logged in", __FILE__, __LINE__);
        }

        $conn = connect("web");
        if (!$conn) {
            log::die("ERROR: Connection to db failed", __FILE__, __LINE__);
        }

        $query = $conn->prepare("UPDATE users SET passwd=?, last_password=NOW() WHERE username=?");
        if (!$query) {
            log::die("ERROR: Could not prepare query" . mysqli_error($conn), __FILE__, __LINE__);
        }

        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $query->bind_param("ss", $password_hash, $username);
        if (!$query) {
            log::die("ERROR: Could not bind params " . mysqli_error($conn), __FILE__, __LINE__);
        }

        if (!$query->execute()) {
            log::die("ERROR: Could not update password. " . mysqli_error($conn), __FILE__, __LINE__);
        }

        $_SESSION["password_date"] = new DateTime("now");
        $query->close();
        mysqli_close($conn);
    }

    // @return string|null
    static private function load_from_db($username)
    {
        global $access_control;
        $sql = "SELECT name, passwd, last_password FROM svommer_web.users WHERE username=?";

        $conn = connect("web");
        if (!$conn) {
            log::die("Connection failed", __FILE__, __LINE__);
        }

        $query = $conn->prepare($sql);
        if (!$query) {
            log::die("Could not prepare query " . mysqli_error($conn), __FILE__, __LINE__);
        }

        $query->bind_param("s", $username);
        if (!$query) {
            log::die("Could not bind params " . mysqli_error($conn), __FILE__, __LINE__);
        }

        $query->execute();
        if (!$query) {
            log::die("Could not execute query " . mysqli_error($conn), __FILE__, __LINE__);
        }

        $password_hash = "";
        $password_date = "";
        $name = "";

        $query->bind_result($name, $password_hash, $password_date);
        if (!$query) {
            log::die("Could not bind results " . mysqli_error($conn), __FILE__, __LINE__);
        }

        $result = $query->fetch();

        if ($result === false) {
            log::die("Could not fetch results " . mysqli_error($conn), __FILE__, __LINE__);
        }

        $query->close();
        mysqli_close($conn);

        if ($result === null) {
            log::message("username $username is not found", __FILE__, __LINE__);
            return null;
        }

        // refresh access
        $access_control = new AccessControl($username);

        $_SESSION["password_date"] = $password_date;
        $_SESSION["name"] = $name;
        return $password_hash;
    }
};
