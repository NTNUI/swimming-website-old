<?php
require_once("library/helpers/admin.php");

if (!Authenticator::is_logged_in()) {
	if (!Authenticator::has_posted_login_credentials()) {
		print_password_form();
		return;
	}
	$username = argsURL("POST", "username");
	$password = argsURL("POST", "password");
	try {
		Authenticator::log_in($username, $password);
	} catch (\AuthenticationException $ex ) {
		print_password_form(false, $ex->getMessage());
		return;
	}
	// update session variables
	$_SESSION["logged_in"] = 1;
	$_SESSION["username"] = $username;
}
global $access_control;
$access_control = new AccessControl($_SESSION["username"]);

print_admin_header(Authenticator::get_name($_SESSION["username"]));

if (Authenticator::log_out_requested()) {
	Authenticator::log_out();
	if (Authenticator::is_logged_in()) {
		log::die("User is still logged in", __FILE__, __LINE__);
	}
	print_log_out_success();
	return;
}

if (Authenticator::pass_change_requested()) {
	if (!Authenticator::has_posted_updated_credentials()) {
		print_password_form(true);
		return;
	}

	// check new credentials
	$new_password = argsURL("POST", "new_pass1");
	$new_password2 = argsURL("POST", "new_pass2");

	$error = Authenticator::validate_new_passwords($new_password, $new_password2);
	if ($error) {
		print_password_form(true, $error);
		exit;
	} else {
		Authenticator::change_password($username, $new_password);
		Authenticator::log_out();
		header("Location: " . $settings["baseurl"] . "/admin");
		exit;
	}
}

// check access
redirect($action);
