<?php
// This file has a stupid name and location.

declare(strict_types=1);

function redirect($action)
{
	global $language, $access_control, $page;
	$result = $access_control->can_access("admin", $action);
	if (!$result) {
		print_no_access($page);
		return;
	}
	if ($action != "") {
		$side = "Private/Admin/" . ucfirst("${action}_$language.php");
		if (file_exists($side)) {
			require_once($side);
			return;
		}
		$side = "Private/Admin/" . ucfirst("${action}_no.php");
		if (file_exists($side)) {
			require_once($side);
			return;
		}
		$side = "Private/Admin/" . ucfirst("${action}.php");
		if (file_exists($side)) {
			require_once($side);
			return;
		}
		print("Page " . ucfirst("$action not found"));
	}
	print_section("web");
	print_section("member");
}

function print_admin_header($name)
{
	global $t;
	print("<div class='box' style='position: relative'>");
	print("<a href=" . $t->get_url("admin") . " class='admin_header'><h1>" . $t->get_translation("admin_header") . "</h1></a>");
	print($t->get_translation("logged_in_as") . "<b class='admin_username'> $name </b><br>");
	access_link("logout", true);
	print(" - ");
	access_link("changepass", true);
	print("</div>");
}

/*
// TODO: when php reaches version >= 8.1 then use an enum as an input
enum loginType{
	case login;
	case changePass;
}

function print_password_form($type = loginType::login, $wrong=false)
*/

function print_password_form($change_password = false, $message = "")
{
	if (!$change_password) {

		print("<div class='box'>");
		print("<h1>Admin</h1>");
		print("</div>");
	}

	print("<div class='box'>");
	if ($message) {
		print("
				<div class='box'>
					<p>$message</p>
				</div>
			");
	}
	print("<form method='POST'>");
	if ($change_password) {

		inputField("password", "Old password", "", true, "oldpass");
		inputField("password", "New password", "", true, "new_pass1");
		inputField("password", "Confirm password", "", true, "new_pass2");
		print("<input type='submit' value='Change password' />");
	} else {
		inputField("text", "Username:", "Username", true, "username");
		inputField("password", "Password:", "hunter2", true, "password");
		print("<input type='submit' value='Log in'/>");
	}
	print("</form></div>");
}
function inputField($type, $label, $placeholder, $required, $inputID)
{
	print("<label for='$inputID'>$label</label>");
	print("<input type='$type' name='$inputID' " . ($placeholder ? "placeholder='$placeholder'" : "") . ($required ? " required" : " ") . "/>");
}

function print_section($section)
{
	global $t;
	print("<div class='box'>");
	print("<h2>" . $t->get_translation("admin_header_" . $section) . "</h2>");
	$inline = true;
	switch ($section) {
		case "member":
			access_link("MemberRegister", $inline);
			access_link("Volunteer", $inline);
			access_link("Cin", $inline);
			access_link("IsMember", $inline);
			break;
		case "web":
			access_link("Users", $inline);
			access_link("Access", $inline);
			access_link("Translations", $inline);
			access_link("Store", $inline);
			access_link("FridayBeer", $inline);
			access_link("Test", $inline);
			break;
		default:
			log::die("Wrong parameter: $section", __FILE__, __LINE__);
	}

	print("</div>");
}

function access_link($page, $inline = false)
{
	global $t, $access_control;
	$page = lcfirst($page);
	$link = $t->get_url("admin/$page");
	$text = $t->get_translation("admin_$page");
	$text = $text ? $text : $page;

	print("<button onclick=window.location.href='$link'>");
	if (!$access_control->can_access("admin", $page)) {
		print("<span class='emoji'>🔒</span>");
	}
	print("$text</button>");

	if (!$inline) {
		print("<br>");
	}
}

function print_log_out_success()
{
	print("
	<div class='box'>
		<h2>You have been logged out</h2>
	</div>
	");
}

function print_no_access($page)
{
	print("
	<div class='error box'>
		<h2>You don't have access to the page $page</h2>
		<p>Contact admin for access</p>
	");
}
