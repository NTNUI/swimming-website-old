<?php

include_once("library/helpers/admin.php");
include_once("library/util/authenticator.php");


if (!Authenticator::is_logged_in()) {
	if (!Authenticator::has_posted_login_credentials()) {
		print_password_form();
		return;
	}
	$username = argsURL("POST", "username");
	$password = argsURL("POST", "password");
	if (!Authenticator::log_in($username, $password)) {
		print_password_form(false, "Wrong credentials");
		return;
	}
}

$name = argsURL("SESSION", "name");
$username = argsURL("SESSION", "username");
$access_control = new AccessControl($username);

print_admin_header($name);

if (Authenticator::log_out_requested()) {
	Authenticator::log_out();
	if (Authenticator::is_logged_in()) {
		log_exception("User is still logged in", __FILE__, __LINE__);
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
		Authenticator::update_password($new_password);
		Authenticator::log_out();
		header("Location: " . $settings["baseurl"] . "/admin");
		exit;
	}
}

// check access
redirect($action);
