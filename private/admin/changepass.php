<?php
if ($_SESSION['logged_in'] == 1) { ?>
	<div class='box'>
		<h1>Passordbytte</h1>
		Du endrer passord for
		<span style='color: orange'><?php print $_SESSION['name'] ?></span>
	</div>
	<?php
	$oldpass = $_POST['oldpass'];
	$new_pass1 = $_POST['new_pass1'];
	$new_pass2 = $_POST['new_pass2'];
	$name = $_SESSION['name'];

	//bytter brukernavn og passord hvis utfyllt riktig
	if ($oldpass != null) {
		if ($new_pass1 == $new_pass2) {

			$conn = connect("web");
			if (!$conn) {
				log_message("Connection to db failed", __FILE__, __LINE__);
				die("Could not connect to database");
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
			if (check_password($oldpass, $passwd, $last_update)) {
				$new_password = password_hash($new_pass1, PASSWORD_DEFAULT);
				$update = $conn->prepare("UPDATE users SET passwd=?, last_password=NOW() WHERE id=?");
				$update->bind_param("si", $new_password, $id);
				if ($update->execute()) {
					$access_control->log("admin/changepass", "change password");
					print("<div class='box green'>Passordet ble oppdatert");
					if (isset($_SESSION['changepass'])) {
						unset($_SESSION['changepass']); ?>
						Du vil taes til riktig side om 2 sekunder, eller klikk <a href="" onclick="">her</a>
						<script type="text/javascript">
							setTimeout(function() {
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
			} else { ?>
				<div class="box error">
					Du har tastet det gamle passordet ditt feil!
				</div>
			<?php 				}
			mysqli_close($con);
		} else { ?>
			<div class="box error">
				De to kolonnene med nytt passord var ikke like!
			</div>
	<?php		}
	}

	//utfyllings skjema
	?>
	<form method="POST">
		<label for="oldpass">Gammelt passord:</label>
		<input type="password" name="oldpass" required />
		<label for="new_pass1">Nytt Passord:</label>
		<input type="password" name="new_pass1" required />
		<label for="new_pass2">Gjenta nytt Passord:</label>
		<input type="password" name="new_pass2" required />
		<input type="submit" value="Bytt Passord" />
	</form>
<?php
}

?>