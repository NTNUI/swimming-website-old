<?php
require_once("library/exceptions/user.php");
require_once("library/exceptions/authentication.php");
// TODO: new function: Generate random password
// TODO: new function: require password reset on user_id
// TODO: merge with access_control class

// Static class Authenticator provides object less authentication services.
class Authenticator
{

    /**
     * Logs in user given username and password
     * @see Authenticator::is_logged_in()
     * @param string $username
     * @param string $password
     * @throws \AuthenticationException on failure
     * @return void
     */
    static public function log_in(string $username, string $password)
    {
        if (Authenticator::is_logged_in()) {
            log::die("Trying to log in even though a user is logged in", __FILE__, __LINE__);
        }
        $password_hash = "";
        try {
            $password_hash = (new Authenticator)->load_from_db($username);
        } catch (\UserException $_) {
            throw \AuthenticationException::WrongCredentials();
        }
        if (!password_verify($password, $password_hash)) {
            throw \AuthenticationException::WrongCredentials();
        }
    }


    /**
     * Check if user has POSTed login credentials
     *
     * @return bool true if user has posted login credentials. False otherwise.
     */
    static public function has_posted_login_credentials(): bool
    {
        if (Authenticator::is_logged_in()) {
            log::die("User is POSTing credentials while being logged in", __FILE__, __LINE__);
        }
        return argsURL("POST", "username") && argsURL("POST", "password");
    }


    /**
     * Check if user has sent a POST request containing new passwords. Used when changing passwords.
     * @return bool true if new passwords has been sent. False otherwise. 
     */
    static public function has_posted_updated_credentials(): bool
    {
        if (!Authenticator::is_logged_in()) {
            log::die("Non logged in user tries to POST credentials", __FILE__, __LINE__);
        }
        return argsURL("POST", "new_pass1") && argsURL("POST", "new_pass2");
    }


    /**
     * Is user logged in?
     *
     * @return bool true if user is logged in. False otherwise.
     */
    static public function is_logged_in(): bool
    {
        return argsURL("SESSION", "logged_in") == 1;
    }


    /**
     * User wants to log out?
     *
     * @return bool true if user wants to log out. False otherwise.
     */
    static public function log_out_requested(): bool
    {
        if (!Authenticator::is_logged_in()) {
            log::die("User is not logged in.", __FILE__, __LINE__);
        }
        return argsURL("REQUEST", "action") == "logout";
    }


    /**
     * Get username
     *
     * @return string of currently logged in user. NULL otherwise.
     */
    static public function get_username()
    {
        if (!Authenticator::is_logged_in()) {
            return NULL;
        }
        return $_SESSION["username"];
    }


    /**
     * Log out currently logged in user
     * Side effects:
     * - destroy session
     * - free all session variables
     * @return void
     */
    static public function log_out()
    {
        if (!Authenticator::is_logged_in()) {
            log::die("Cannot log out a user that is not logged in", __FILE__, __LINE__);
            return;
        }
        session_unset();
        session_destroy();
    }


    /**
     * Password change requested?
     * If requested action is "changepass" or current password date is older than one year.
     * @return bool true if password change has been requested. False otherwise.
     */
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


    /**
     * Validate new passwords
     *
     * @param string $password1
     * @param string $password2
     * @return string error message if any error is present. Empty string is returned otherwise.
     */
    static public function validate_new_passwords(string $password, string $password2): string
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

    /**
     * Change a password for @param username
     * Side effects:
     * - Update session variables
     * 
     * @param string $username to modify
     * @param string $new_password
     * @return void
     */
    static public function change_password(string $username, string $new_password)
    {
        if (!Authenticator::is_logged_in() || !$username) {
            log::die("ERROR: User is not logged in", __FILE__, __LINE__);
        }

        $db = new DB("web");
        $db->prepare("UPDATE users SET passwd=?, last_password=NOW() WHERE username=?");
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $db->bind_param("ss", $password_hash, $username);
        $db->execute();

        $_SESSION["password_date"] = new DateTime("now");
    }

    /**
     * Get a users name
     *
     * @param string $username of the user
     * @return string users display name
     * @throws UserException if current user is not logged in
     * @throws UserException if user is not found
     */
    static public function get_name(string $username): string
    {
        if (!Authenticator::is_logged_in()) {
            throw UserException::LoginRequired();
        }
        $db = new DB("web");
        $db->prepare("SELECT name FROM users WHERE username=?");
        $db->bind_param("s", $username);
        $db->execute();
        $name = "";
        $db->stmt->bind_result($name);
        if ($db->fetch() === NULL) {
            throw UserException::NotFound();
        }
        return $name;
    }

    /**
     * Get credentials
     * Side effects:
     * - Refresh global access control
     * - update session variables
     *
     * @param string $username
     * @return string password_hash of @param username
     */
    static private function load_from_db(string $username): string
    {
        // get credentials
        $db = new DB("web");
        $db->prepare("SELECT name, passwd, last_password FROM users WHERE username=?");
        $db->bind_param("s", $username);
        $db->execute();
        $password_hash = "";
        $password_date = "";
        $name = "";
        $db->stmt->bind_result($name, $password_hash, $password_date);
        if ($db->fetch() === NULL) {
            throw UserException::NotFound();
        }

        // refresh access control
        global $access_control;
        $access_control = new AccessControl($username);

        // update session variables
        $_SESSION["password_date"] = $password_date;
        $_SESSION["name"] = $name;

        return $password_hash;
    }


    /**
     * Get username from a user id
     *
     * @param integer $user_id of the user
     * @return string with users username
     * @throws UserException if user is not found
     */
    static public function get_username_from_id(int $user_id): string
    {
        $db = new DB("web");
        $db->prepare("SELECT username FROM users WHERE id=?");
        $db->bind_param("i", $user_id);
        $db->execute();
        $db->stmt->bind_result($username);
        if ($db->fetch() === NULL) {
            throw UserException::NotFound();
        }
        return $username;
    }

    /**
     * Create a new user
     * Side effects:
     * - log action
     * 
     * @param string $name Full name
     * @param string $username unique username
     * @param string $password strong and complicated password
     * @return bool true if created successfully. False if username is taken.
     */
    static public function create_user(string $name, string $username, string $password)
    {
        if (Authenticator::username_exists($username)) {
            log::message("Info: username: $username already exists", __FILE__, __LINE__);
            return false;
        }

        // create user
        $db = new DB("web");
        $db->prepare("INSERT INTO users (name, username, passwd) VALUES(?, ?, ?)");
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $db->bind_param("sss", $name, $username, $password_hash);
        $db->execute();

        log::message("Created a new user with username: $username by " . Authenticator::get_username(), __FILE__, __LINE__);

        return true;
    }


    /**
     * Check if a username exits
     *
     * @param string $username
     * @return bool true if @param $username exists. False otherwise.
     */
    static public function username_exists(string $username): bool
    {
        $db = new DB("web");
        $db->prepare("SELECT COUNT(*) FROM users WHERE username=?");
        $db->bind_param("s", $username);
        $result = 0;
        $db->execute();
        if (!$db->stmt->bind_result($result)) {
            log::die("Could not bind results", __FILE__, __LINE__);
        }
        $db->fetch();
        return (bool)$result;
    }


    /**
     * Undocumented function
     *
     * @param string $page
     * @param string $action
     * @param string $FILE
     * @param int $LINE
     * @return void
     */
    static public function auth_API(string $page, string $action, string $FILE = __FILE__, int $LINE = __LINE__)
    {
        $access_control = new AccessControl(argsURL("SESSION", "username"));
        if (!Authenticator::is_logged_in()) {
            log::forbidden("Access denied", $FILE, $LINE);
        }

        if (!$access_control->can_access($page, $action = "")) {
            log::message("Info: Access denied for " . Authenticator::get_username() . "performing action: " . $action, $FILE, $LINE);
            log::forbidden("Access denied", $FILE, $LINE);
        }
    }
};
