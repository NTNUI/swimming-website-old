<?php
if ($_SESSION['innlogget'] == 1) { ?>
	<div class='box'>
		<h1>Passordbytte</h1>
		Du endrer passord for 
		<span style='color: orange'><?php print $_SESSION['navn'] ?></span>
	</div>
<?php
	$oldpass = $_POST['oldpass'];
	$nypass1 = $_POST['nypass1'];
	$nypass2 = $_POST['nypass2'];
	$navn = $_SESSION['navn'];

	//bytter brukernavn og passord hvis utfyllt riktig
	if($oldpass != null){
		if($nypass1 == $nypass2){
			
			$conn = connect("web");
			if (!$conn) {
				die('Could not connect: ' . mysqli_error($conn));
			}

			$sql = "SELECT id, passwd, name, last_password FROM users WHERE username=?";
			$query = $conn->prepare($sql);
			$query->bind_param("s", $_SESSION['user']);
			$query->execute();
			$query->bind_result($id, $passwd, $name, $last_update);
			if (!$query->fetch()) {
				die("error fetching from database");
			}

			$query->close();
			if(check_password($oldpass, $passwd, $last_update)) {
				$new_password = password_hash($nypass1, PASSWORD_DEFAULT);
				$update = $conn->prepare("UPDATE users SET passwd=?, last_password=NOW() WHERE id=?");
				$update->bind_param("si", $new_password, $id);
				if ($update->execute()) {
					$access_control->log("admin/changepass", "change password");
					print("<div class='box green'>Passordet ble oppdatert");
					if (isset($_SESSION['changepass'])) {
						unset($_SESSION['changepass']);?>
						Du vil taes til riktig side om 2 sekunder, eller klikk <a href="" onclick="">her</a>
						<script type="text/javascript">
						setTimeout(function () {
							window.location = window.location.href;
						}, 2000);
						</script>

						<?php
					}
					printf("</div>");
				} else {
					printf("Noe gikk galt :/");
				}

					$update->close();
				}else{ ?>
					<div class="box error">
						Du har tastet det gamle passordet ditt feil!
					</div>
	<?php 				}
				mysqli_close($con);

		}else{?>
			<div class="box error">
				De to kolonnene med nytt passord var ikke like!
			</div>
<?php		}
	}

		//utfyllings skjema
	?>
	<form method="POST">
		<label for="oldpass">Gammelt passord:</label>
		<input type="password" name="oldpass" required/>
		<label for="nypass1">Nytt Passord:</label>
		<input type="password" name="nypass1" required/>
		<label for="nypass2">Gjenta nytt Passord:</label>
		<input type="password" name="nypass2" required/>
		<input type="submit" value="Bytt Passord" />
	</form>	
		<?php
}

?>
