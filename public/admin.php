<style>
	.admin_header {
		color: #000 !important;
	}

	.admin_username {
		color: #000;
	}
</style>

<?php

function check_password($password, $db_password, $lastcheck)
{
	//Legacy check for older passwords
	if ($lastcheck == null or strtotime($lastcheck) < strtotime("20-09-2018")) {
		return md5($password) == $db_password;
	}

	return password_verify($password, $db_password);
}

// TODO: change this to return a boolean. printing should be done by functions starting with print.
function access_link($page, $inline = false)
{
	global $t, $access_control;
	$link = $t->get_url("admin/$page");
	$text = $t->get_translation("admin_$page");

	print "<a href='$link'>$text</a>";
	if (!$access_control->can_access("admin", $page)) print "<span>&#x1f512;</span>";
	if (!$inline) print "<br>";
}

function print_password_change_required()
{
	print("<div class='box error'>");
	print("<h1>A password reset has been requested for your user</h1>");
	print("Please change password before accessing admin features");
	print("</div>");
}

function print_admin_header($user)
{
	global $t;
	print("<div class='box green' style='position: relative'>");
	print("<h1><a href='/svommer/admin/' class='admin_header'>" . $t->get_translation("admin_header") . "</a></h1>");
	print($t->get_translation("admin_logged_in_as") . "<b class='admin_username'>" . $user . "</b><br>");
	access_link("logout", true);
	print(" - ");
	access_link("changepass", true);
	print("</div>");
}

function print_no_access($page)
{
	print "<div class='error box'><h1>You don't have access to the page $page</h1>";
	print "<p>Spør noen i styret om du mener dette er feil</p>";
}

function print_web_section()
{
	global $t;
	print("<div class='box'>");
	print("<h2> " . $t->get_translation("admin_header_web") . "</h2>");

	access_link("nyhet");
	// access_link("referat");
	access_link("users");
	access_link("access");
	access_link("translations");
	access_link("store");
	access_link("fredagspils");

	print("</div>");
}

function print_member_section()
{
	global $t;
	print("<div class='box'>");
	print("<h2>" . $t->get_translation("admin_header_member") . "</h2>");

	access_link("medlemsreg");
	access_link("autopay");
	access_link("dugnad");
	access_link("alumni");
	access_link("kid");

	print("</div>");
}

function print_login_box()
{
	print("<div class='box green'>");
	print("<h1>Admin</h1>");
	print("Logg inn først");
	print("</div>");
	print("<form method='post'>");
	print("<label for='bruker'>Brukernavn:</label>");
	print("<input type='text' name='bruker' placeholder='Brukernavn' required/>");
	print("<label for='pass'>Passord:</label>");
	print("<input type='password' name='pass' placeholder='hunter2' required/>");
	print("<input type='submit' value='Logg inn'/>");
	print("</form>");
}

// Request users with passwords older than this to update
$UPDATE_PASSWORDS_FROM_BEFORE = strtotime("25-04-2021");
//sjekk brukernavn og passord hvis ikke allerede innlogget

$user = NULL;
$pass = NULL;
$action = NULL;
$logged_in = NULL;
$change_pass = NULL;

if (isset($_POST["bruker"])) {
	$user = $_POST["bruker"];
}
if (isset($_POST["pass"])) {
	$pass = $_POST["pass"];
}
if (isset($_REQUEST["action"])) {
	$action = $_REQUEST["action"];
}
if (isset($_SESSION["logged_in"])) {
	$logged_in = $_POST["logged_in"];
}
if (isset($_SESSION["changepass"])) {
	$change_pass = $_POST["changepass"];
}


if (!isset($_SESSION['logged_in'])) {
	if ($user != null) {

		// Check connection
		$conn = connect("web");
		if (!$conn) {
			log_message("Connection failed: " . mysqli_connect_error(), __FILE__, __LINE__);
			die("Connection failed: " . mysqli_connect_error());
		}

		$sql = "SELECT passwd, name, last_password FROM users WHERE username=?";
		$query = $conn->prepare($sql);
		$query->bind_param("s", $user);
		$query->execute();
		$query->bind_result($db_passwd, $name, $last_date);
		if (!$query->fetch()) {
			echo "query error";
		}

		if (check_password($pass, $db_passwd, $last_date)) {
			//variable "innlogget" = true
			$_SESSION['logged_in'] = 1;
			$_SESSION['navn'] = $name;
			$_SESSION['user'] = $user;
			//Old password
			if ($last_date == null or strtotime($last_date) < $UPDATE_PASSWORDS_FROM_BEFORE) {
				$_SESSION['changepass'] = 1;
			}
		} else {
			printf("Feil brukernavn eller passord!");
		}

		$query->close();

		//Referesh access control
		$access_control = new AccessControl($user, $conn);
		mysqli_close($conn);

		//slutt på gammel mysql kode
	}
}

// Has to change password
if ($_SESSION['changepass'] == 1) {
	print_password_change_required();
	include("private/admin/changepass.php");

	//hvis innlogget
} else if ($_SESSION['logged_in'] == 1) {
	if ($action != "logout") {
		print_admin_header($_SESSION['navn']);
	}
	$result = $access_control->can_access("admin", $action);
	if (!$result) {
		print_no_access($page);
		return;
	}
	if ($action != "") {
		$side = "private/admin/${action}_$language.php";
		if (file_exists($side)) {
			include($side);
			return;
		}
		$side = "private/admin/${action}_no.php";
		if (file_exists($side)) {
			include($side);
			return;
		}
		$side = "private/admin/${action}.php";
		if (file_exists($side)) {
			include($side);
			return;
		}
		print("Page $action not found");
	}

	print_web_section();
	print_member_section();
} else {
	print_login_box();
}

?>