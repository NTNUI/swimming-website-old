<?php

function check_password($password, $db_password, $lastcheck) {
	//Legacy check for older passwords
	if ($lastcheck == null or strtotime($lastcheck) < strtotime("20-09-2018")) {
		return md5($password) == $db_password;
	}

	return password_verify($password, $db_password);
}

function access_link($page, $inline = false) {
	global $t, $access_control;
	$link = $t->get_url("admin/$page");
	$text = $t->get_translation("admin_$page");

	print "<a href='$link'>$text</a>";
	if (!$access_control->can_access("admin", $page)) print "<span>&#x1f512;</span>";
	if (!$inline) print "<br>";
}


// Request users with passwords older than this to update
$UPDATE_PASSWORDS_FROM_BEFORE = strtotime("20-09-2018");
//sjekk brukernavn og passord hvis ikke allerede innlogget

$bruker = $_POST['bruker'];
$pass = $_POST['pass'];
$action = $_REQUEST['action'];

if(!isset($_SESSION['innlogget'])){
	if($bruker != null){


	$conn = connect("web");

		// Check connection
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
if ($_SESSION['changepass'] == 1) { ?>
	<div class="box error">
		<h1>A password reset has been requested for your user</h1>
		Please change password before accessing admin features
	</div>

<?php
	include("admin/changepass.php");

//hvis innlogget
} else if ($_SESSION['innlogget'] == 1) {
	if ($action != "logout") { ?>
	<div class="box green" style="position: relative">
<h1><a href="/svommer/admin/" style="color: #ff0 !important;">Admin</a></h1>
		Innlogget som: <b style="color: orange"><?php print($_SESSION['navn']);?></b><br>
		<?php access_link("logout", true) ?> - <?php access_link("changepass", true) ?>
	</div>
<?php    	}
	$result = $access_control->can_access("admin", $action);
	if (!$result) {
		print "<div class='error box'><h1>You don't have access to the page $page</h1>";
		print "<p>Spør noen i styret om du mener dette er feil</p>";
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
	?>
	<div class="box">
		<h1>Trends</h1>
	<!-- Below are some trends just for fun-->
<!--


This shit uses like 4GB of memory, put a link instead


                  <script type="text/javascript" src="https://ssl.gstatic.com/trends_nrtr/1754_RC01/embed_loader.js"></script>
  <script type="text/javascript">
    trends.embed.renderExploreWidget("TIMESERIES", {"comparisonItem":[{"keyword":"NTNUI Svømming","geo":"NO","time":"today 12-m"}],"category":0,"property":""}, {"exploreQuery":"geo=NO&q=NTNUI%20Sv%C3%B8mming&date=today 12-m","guestPath":"https://trends.google.com:443/trends/embed/"});
  </script>
-->
<!--Trends until this point -->


	</div>

	<div class="box">
		<h1>Web</h1>
		<?php
			access_link("nyhet");
			access_link("referat");
			access_link("users");
			access_link("access");
			access_link("translations");
			access_link("store");
			access_link("fredagspils");
		?>

	</div>

	<div class="box">
		<h1>Medlemmer</h1>
		<?php
		access_link("medlemsreg");
		access_link("autopay");
		access_link("dugnad");
		
		?>
		<a href='<?php print $t->get_url("isMember") ?>'>Medlemsliste</a> (Det er denne Pirbadet skal ha)<br>
	</div>

<!--
	// Old stuff never used
	/*printf("<a href='?side=kurs.php'>Kurs</a> (ikke i bruk)<br>");
	printf("<a href='?side=ninst.php'>Legge til instruktør</a> (ikke i bruk)<br>");
	printf("<a href='?side=nkurs.php'>Sette opp nytt kurs</a> (ikke i bruk)<br><br>");

	printf("<a href='index.php?side=resmenu.php'>RESULTATOR  !!UNDER CONSTRUCTION!!</a><br>"); */ -->
<?php	}else{
	//hvis ikke innlogget vises innloggingsskjema
?>
		<div class="box green">
			<h1>Admin</h1>
			Logg inn først
		</div>
		<form method='post'>
			<label for="bruker">Brukernavn:</label>
			<input type="text" name="bruker" placeholder="Brukernavn" required/>
			<label for="pass">Passord:</label>
			<input type="password" name="pass" placeholder="hunter2" required/>
			<input type="submit" value="Logg inn"/>
		</form>
<?php
}

?>
