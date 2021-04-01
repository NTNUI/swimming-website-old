<?php

function check_password($password, $db_password, $lastcheck) {
	//Legacy check for older passwords
	if ($lastcheck == null or strtotime($lastcheck) < strtotime("20-09-2018")) {
		return md5($password) == $db_password;
	}

	return password_verify($password, $db_password);
}

// TODO: change this to return a boolean. printing should be done by functions starting with print.
function access_link($page, $inline = false) {
	global $t, $access_control;
	$link = $t->get_url("admin/$page");
	$text = $t->get_translation("admin_$page");

	print "<a href='$link'>$text</a>";
	if (!$access_control->can_access("admin", $page)) print "<span>&#x1f512;</span>";
	if (!$inline) print "<br>";
}

function print_password_change_required(){
	print("<div class='box error'>");
	print("<h1>A password reset has been requested for your user</h1>");
	print("Please change password before accessing admin features");
	print("</div>");
}

function print_admin_header($user){
	print("<div class='box green' style='position: relative'>");
	print("<h1><a href='/svommer/admin/' style='color: #ff0 !important;'>Admin</a></h1>");
	print("Innlogget som: <b style='color: orange'>" . $user . "</b><br>");
	access_link("logout", true);
	print(" - ");
	access_link("changepass", true);
	print("</div>");

}

function print_no_access($page){
	print "<div class='error box'><h1>You don't have access to the page $page</h1>";
	print "<p>Spør noen i styret om du mener dette er feil</p>";
}

function print_web_section(){
	print("<div class='box'>");
	print("<h1>Web</h1>");

	access_link("nyhet");
	// access_link("referat");
	access_link("users");
	access_link("access");
	access_link("translations");
	access_link("store");
	access_link("fredagspils");

	print("</div>");
}

function print_member_section(){
	global $t;
	print("<div class='box'>");
	print("<h1>Medlemmer</h1>");

	access_link("medlemsreg");
	access_link("autopay");
	access_link("dugnad");
	access_link("alumni");
	access_link("kid");
	print("<a href=" . $t->get_url("isMember") . ">Medlemssøk</a><br>");

	print("</div>");
}

function print_login_box(){
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
$UPDATE_PASSWORDS_FROM_BEFORE = strtotime("20-09-2018");
//sjekk brukernavn og passord hvis ikke allerede innlogget

$bruker = $_POST['bruker'];
$pass = $_POST['pass'];
$action = $_REQUEST['action'];

if(!isset($_SESSION['innlogget'])){
	if($bruker != null){
		
		// Check connection
		$conn = connect("web");
		if (!$conn) {
			die("Connection failed: " . mysqli_connect_error());
		}

		$sql = "SELECT passwd, name, last_password FROM users WHERE username=?";
		$query = $conn->prepare($sql);
		$query->bind_param("s", $bruker);
		$query->execute();
		$query->bind_result($db_passwd, $name, $last_date);
		if (!$query->fetch()) {
		   echo "query error";
		}

		if(check_password($pass, $db_passwd, $last_date)) {
			//variable "innlogget" = true
			$_SESSION['innlogget'] = 1;
			$_SESSION['navn'] = $name;
			$_SESSION['user'] = $bruker;
			//Old password
			if ($last_date == null or strtotime($last_date) < $UPDATE_PASSWORDS_FROM_BEFORE) {
				$_SESSION['changepass'] = 1;
			}
			//printf("Innlogging suksessfull som: %s\n<br>", mysql_result($result,$n,"name"));
		}else{
			printf("Feil brukernavn eller passord!");
		}

		$query->close();

		//Referesh access control
		$access_control = new AccessControl($bruker, $conn);
		mysqli_close($conn);

		//slutt på gammel mysql kode
	}
}

// Has to change password
if ($_SESSION['changepass'] == 1) {
	print_password_change_required();
	include("admin/changepass.php");

//hvis innlogget
} else if ($_SESSION['innlogget'] == 1) {
	if ($action != "logout") {
		print_admin_header($_SESSION['navn']);
	}
	$result = $access_control->can_access("admin", $action);
	if (!$result) {
		print_no_access($page);
		return;
	}
	if ($action != "") {
		$side = "admin/${action}_$language.php";
		if (file_exists($side)) {
			include($side);
			return;
		}
		$side = "admin/${action}_no.php";
		if (file_exists($side)) {
			include($side);
			return;
		}
		$side = "admin/${action}.php";
		if (file_exists($side)) {
			include($side);
			return;
		}
		print("Page $action not found");
	}

	print_web_section();
	print_member_section();

	}else{
	//hvis ikke innlogget vises innloggingsskjema
	print_login_box();
}

?>
