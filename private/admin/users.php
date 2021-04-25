<?php

$conn = connect("web");
if (!$conn) {
	die("Connection failed: " . mysqli_connect_error());
}

$type = $_REQUEST["type"];
$username = $_REQUEST["username"];
$realname = $_REQUEST["realname"];

if ($type != "" && $username != "" && $realname != "") {
	if ($type == "edit") {
		$key = $_REQUEST["key"];
		$role = $_REQUEST["role"];

		$sql = "UPDATE users SET username=?, name=?, role=? WHERE id=?";
		$query = $conn->prepare($sql);
		$query->bind_param("ssii", $username, $realname, $role, $key);
		if ($query->execute()) { ?>
			<h1 class="box green">Record updated successfully</h1>spot
		<?php } else { ?>
			<div class="box error">
				<h1>Something went wrong</h1>
				<p><?php print mysqli_error() ?></p>
			</div>
		<?php		}
		$access_control->log("admin/users", "edited user", $key);
		$query->close();
	} else if ($type == "addUser") {
		$email = $_REQUEST["email"];

		//Generate new password
		$pw = substr(md5(mt_rand()), 0, 7);
		$sql = "INSERT INTO users (username, name, passwd) VALUES(?, ?, ?)";
		$query = $conn->prepare($sql);
		//Use legacy password as this forces a pw reset
		//todo: solve this better
		mail(
			$email,
			"NTNUI Svømming: Ny bruker",
			"En bruker har blitt laget til deg. Du kan nå logge inn med engangspassord:\n" .
				"Brukernavn: $username\n" .
				"Passord: $pw",
			"From: " . $settings["emails"]["bot-general"]
		);
		$query->bind_param("sss", $username, $realname, md5($pw));
		if ($query->execute()) { ?>
			<h1 class="box green">User created, mail sent with password</h1>
		<?php } else { ?>
			<div class="box error">
				<h1>Something went wrong</h1>
			</div>
		<?php }
		$query->close();
		$access_control->log("admin/users", "added user", $username);
	}
} else if ($type == "addRole") {
	$newrole = $_REQUEST["rolename"];
	$sql = "INSERT INTO roles (name) VALUES (?)";
	$query = $conn->prepare($sql);
	$query->bind_param("s", $newrole);
	if ($query->execute()) { ?>
		<h1 class="box green">Role created successfully</h1>
	<?php } else { ?>
		<div class="box error">
			<h1>Something went wrong</h1>
			<p><?php print mysqli_error() ?></p>
		</div>
<?php		}
	$query->close();
	$access_control->log("admin/users", "added role", $newrole);
}

$sql = "SELECT id, name FROM roles";
$query = $conn->prepare($sql);
$query->execute();
$query->bind_result($id, $role);
$roles = array();
while ($query->fetch()) {
	$roles[$id] = $role;
}
$query->close();

$sql = "SELECT id, username, name, role FROM users";
$query = $conn->prepare($sql);
$query->execute();
$query->bind_result($id, $username, $name, $role); ?>
<div class="box">

	<table style="width: 100%;">
		<tr>
			<th>Username</th>
			<th>Full Name</th>
			<th>Role</th>
			<th>Actions</th>
		</tr>
		<?php
		while ($query->fetch()) { ?>
			<tr>
				<form method="POST" action="?type=edit">
					<input name="key" type="hidden" value="<?php print $id ?>" />
					<td><input name="username" type="text" value="<?php print $username ?>" /></td>
					<td><input name="realname" type="text" value="<?php print $name ?>" /></td>
					<td>
						<select name="role"><?php
											foreach ($roles as $i => $r) {
												print "<option value='$i'" . ($i == $role ? " selected" : "") . ">$r</option>";
											} ?>
						</select>
					</td>
					<td>
						<input type="submit" value="Lagre" />
					</td>
				</form>
			</tr>
		<?php }
		$conn->close(); ?>
	</table>
</div>

<div class="box">
	<h2>Legg til rolle</h2>
	<form method="POST" action="?type=addRole">
		<label for="rolename">Navn:</label>
		<input name="rolename" type="text" placeholder="Designated Swimmer" />
		<input type="Submit" value="Legg til" />
	</form>
</div>

<div class="box">
	<h2>Legg til bruker</h2>
	<form method="POST" action="?type=addUser">
		<label for="username">Brukernavn:</label>
		<input name="username" type="text" placeholder="Brukernavn" required />
		<label for="realname">Fullt navn:</label>
		<input name="realname" type="text" placeholder="Fult navn" required />
		<label for="email">Epost (brukes bare for å sende tilfeldig passord):</label>
		<input name="email" type="email" placeholder="me@example.com" />
		<input type="submit" value="Lag bruker" />
	</form>
</div>