<?php 
global $settings;
// TODO: Move styling to css file
// TODO: Add translations
// TODO: change key 'id' in database to 'user_id' and to 'role_id'
// TODO: Remove role 'superadmin' keep 'admin' as god role
// TODO: Add change role (change name)
// TODO: Add domain requirements on new users. Use settings file for storing allowed domains
// TODO: check if username and or role already exists
// TODO: move/merge this to authenticator and/or access_control.
// TODO: Convert to REST-API and use JS for calls. (Probably after the merge mentioned above)
// TODO: Move hard coded mail content to translator
// TODO: Create API/users.php, split backend and frontend.

// Get arguments on this request
$action = argsURL("REQUEST", "users_action");
$username = argsURL("REQUEST", "username");
$name = argsURL("REQUEST", "name");
$email = argsURL("REQUEST", "email");
$role_id = argsURL("REQUEST", "role_id");
$user_id = argsURL("REQUEST", "user_id");
$new_role_name = argsURL("REQUEST", "new_role_name");
$update_type = argsURL("REQUEST", "update_type");

$conn = connect("web");

// FIX ME
if ($update_type === "Delete") {
	if ($action === "update_user") {
		$action = "delete_user";
	} else {
		$action = "delete_role";
	}
}

// Log action if user have access, otherwise
if (!$access_control->can_access("users", $action)) {
	log::alert("You don't have access to $action", __FILE__, __LINE__);
	$action = null;
}

// if action is set do it
switch ($action) {
	case null:
		// ignore no action
		break;
	case 'create_user':
		create_user($name, $username, $email);
		break;
	case 'update_user':
		update_user($conn, $name, $username, $user_id, $role_id);
		break;
	case 'delete_user':
		delete_user($conn, $user_id);
		break;
	case 'create_role':
		create_role($conn, $new_role_name);
		break;
	case 'update_role':
		log::alert("action: $action is not yet implemented", __FILE__, __LINE__);
		break;
	case 'delete_role':
		delete_role($conn, role_string_to_id($conn, $role_id));
		break;
	default:
		log::alert("action: $action is not implemented", __FILE__, __LINE__);
		break;
}

// print menus and quit
print_user_matrix($conn);
print_forms();
$conn->close();

// User functions
function create_user(string $name, string $username, string $email)
{
	global $settings, $access_control, $action;

	if (!$email || !$username || !$name) {
		log::die("parameters are not set", __FILE__, __LINE__);
	}

	if (Authenticator::username_exists($username)) {
		log::alert("username: $username already exists", __FILE__, __LINE__);
		return;
	}

	// create user with random password
	$random_password = substr(md5(mt_rand()), 0, 7);
	if (!Authenticator::create_user($username, $random_password)) {
		log::alert("Could not create user with $username", __FILE__, __LINE__);
		return;
	}

	// Send mail
	$email_title = "NTNUI Swimming: New user";
	$email_content = "Your user account has been created.\n" .
		"Username: $username\n" .
		"Password: $random_password\n" .
		"You will be forced to change password on first login.\n";
	$email_from = "From: " . $settings["emails"]["bot-general"];

	mail(
		$email,
		$email_title,
		$email_content,
		$email_from
	);
	$access_control->log("users", $action, $username);
}

function update_user(mysqli $conn, string $name, string $username, int $user_id, int $role_id)
{
	global $access_control, $action;

	// check for valid request
	if (!$user_id || !$role_id || !$username || !$name) {
		log::die("parameters are not set", __FILE__, __LINE__);
	}

	// TODO: if username is updated, check that it does not collide with others

	$sql = "UPDATE users SET username=?, name=?, role=? WHERE id=?";
	$query = $conn->prepare($sql);
	if (!$query) {
		log::die("could not prepare query " . mysqli_error($conn), __FILE__, __LINE__);
	}
	$query->bind_param("ssii", $username, $name, $role_id, $user_id);
	if (!$query) {
		log::die("could not bind params " . mysqli_error($conn), __FILE__, __LINE__);
	}
	if (!$query->execute()) {
		log::die("Could not execute query " . mysqli_error($conn), __FILE__, __LINE__);
	}
	log::alert("Record updated successfully");
	$access_control->log("admin/users", $action, user_id_to_name($conn, $user_id));
	$query->close();
}

function delete_user(mysqli $conn, int $user_id)
{
	global $access_control, $action;

	if (!$user_id) {
		log::die("Parameters are not correct", __FILE__, __LINE__);
	}

	$name_user = user_id_to_name($conn, $user_id);
	if (role_id_to_string($conn, user_id_to_user_role($conn, $user_id)) === "admin") {
		log::alert("Admins cannot be deleted", __FILE__, __LINE__);
		return;
	}

	$sql = "DELETE FROM users WHERE id=?"; // id is user_id
	$query = $conn->prepare($sql);
	if (!$query) {
		log::die("Could not execute query", __FILE__, __LINE__);
	}
	$query->bind_param("i", $user_id);
	if (!$query) {
		log::die("Could not bind params", __FILE__, __LINE__);
	}
	if (!$query->execute()) {
		log::die("Could not execute query", __FILE__, __LINE__);
	}
	$query->close();
	$access_control->log("admin/users", $action, $name_user);
}

// Role functions
function create_role(mysqli $conn, string $new_role_name)
{
	global $access_control, $action;

	if (role_exists($conn, $new_role_name)) {
		log::alert("Role $new_role_name already exists", __FILE__, __LINE__);
		return;
	}

	$sql = "INSERT INTO roles (name) VALUES (?)";
	$query = $conn->prepare($sql);
	if (!$query) {
		log::die("Could not execute query", __FILE__, __LINE__);
	}
	$query->bind_param("s", $new_role_name);
	if (!$query) {
		log::die("Could not bind params", __FILE__, __LINE__);
	}
	if (!$query->execute()) {
		log::die("Could not execute query", __FILE__, __LINE__);
	}
	log::alert("Role created successfully", __FILE__, __LINE__);

	$query->close();
	$access_control->log("admin/users", $action, $new_role_name);
}

function delete_role(mysqli $conn, int $role_id)
{
	global $access_control, $action;
	$name_role = role_id_to_string($conn, $role_id);

	if (role_id_to_string($conn, $role_id) === "admin") {
		log::alert("Cannot delete admin role", __FILE__, __LINE__);
		return;
	}

	// delete users with the role
	$users = get_users_with_role_id($conn, $role_id);
	foreach ($users as $user) {
		delete_user($conn, $user);
	}

	// delete role
	$sql = "DELETE FROM roles WHERE id=?"; // id is role_id
	$query = $conn->prepare($sql);
	if (!$query) {
		log::die("Could not prepare query", __FILE__, __LINE__);
	}
	$query->bind_param("i", $role_id);
	if (!$query) {
		log::die("Could not bind params", __FILE__, __LINE__);
	}
	if (!$query->execute()) {
		log::die("Could not execute query", __FILE__, __LINE__);
	}
	$query->close();
	$access_control->log("admin/users", $action, $name_role);
}

// db functions
function get_users_with_role_id(mysqli $conn, int $role_id)
{
	$sql = "SELECT id FROM users WHERE role=?";
	$query = $conn->prepare($sql);
	if (!$query) {
		log::die("Could not prepare statement", __FILE__, __LINE__);
	}
	$query->bind_param("i", $role_id);
	if (!$query) {
		log::die("Could not bind params", __FILE__, __LINE__);
	}
	$query->execute();
	if (!$query) {
		log::die("Could not execute statement", __FILE__, __LINE__);
	}
	$user_id = 0;
	$query->bind_result($user_id);
	if (!$query) {
		log::die("Could not bind result", __FILE__, __LINE__);
	}
	$result = new arrayObject();
	while ($query->fetch()) {
		$result->append($user_id);
	}
	$query->close();
	return $result;
}

function get_roles(mysqli $conn)
{
	$sql = "SELECT id, name FROM roles"; // id is role_id
	$query = $conn->prepare($sql);
	if (!$query) {
		log::die("Could not prepare statement", __FILE__, __LINE__);
	}
	$query->execute();
	if (!$query) {
		log::die("Could not execute statement", __FILE__, __LINE__);
	}
	$user_id = "";
	$role = "";
	$query->bind_result($user_id, $role);
	if (!$query) {
		log::die("Could not bind result", __FILE__, __LINE__);
	}
	$roles = array();
	while ($query->fetch()) {
		$roles[$user_id] = $role;
	}
	$query->close();
	return $roles;
}

function role_exists(mysqli $conn, string $name_role)
{
	$sql = "SELECT COUNT(*) FROM roles WHERE name=?";
	$query = $conn->prepare($sql);
	if (!$query) {
		log::die("Could not execute query", __FILE__, __LINE__);
	}
	$query->bind_param("s", $name_role);
	if (!$query) {
		log::die("Could not bind params", __FILE__, __LINE__);
	}
	if (!$query->execute()) {
		log::die("Could not execute query", __FILE__, __LINE__);
	}
	$result = 0;
	if (!$query->bind_result($result)) {
		log::die("Could not bind results", __FILE__, __LINE__);
	}
	$query->fetch();
	$query->close();
	return (bool)$result;
}

function role_id_to_usernames(mysqli $conn, int $role_id)
{
	$sql = "SELECT username FROM users WHERE role=?"; // role is role_id
	$query = $conn->prepare($sql);
	if (!$query) {
		log::die("Could not execute query", __FILE__, __LINE__);
	}
	$query->bind_param("i", $role_id);
	if (!$query) {
		log::die("Could not bind params", __FILE__, __LINE__);
	}
	if (!$query->execute()) {
		log::die("Could not execute query", __FILE__, __LINE__);
	}
	$temp = "";
	if (!$query->bind_result($result)) {
		log::die("Could not bind results", __FILE__, __LINE__);
	}
	$result = array();
	$i = 0;
	while ($query->fetch()) {
		$result[$i] = $temp;
		++$i;
	}
	$query->close();
	return $result;
}

function role_id_to_string(mysqli $conn, int $role_id)
{
	$sql = "SELECT name from roles WHERE id=?"; // id is role_id
	$query = $conn->prepare($sql);
	if (!$query) {
		log::die("Could not execute query", __FILE__, __LINE__);
	}
	$query->bind_param("i", $role_id);
	if (!$query) {
		log::die("Could not bind params", __FILE__, __LINE__);
	}
	if (!$query->execute()) {
		log::die("Could not execute query", __FILE__, __LINE__);
	}
	$result = "";
	if (!$query->bind_result($result)) {
		log::die("Could not bind results", __FILE__, __LINE__);
	}
	$query->fetch();
	$query->close();
	return $result;
}

function role_string_to_id(mysqli $conn, string $role_name)
{
	$sql = "SELECT id FROM roles WHERE name=?"; // id is role_id
	$query = $conn->prepare($sql);
	if (!$query) {
		log::die("Could not execute query", __FILE__, __LINE__);
	}
	$query->bind_param("s", $role_name);
	if (!$query) {
		log::die("Could not bind params", __FILE__, __LINE__);
	}
	if (!$query->execute()) {
		log::die("Could not execute query", __FILE__, __LINE__);
	}
	$result = 0;
	if (!$query->bind_result($result)) {
		log::die("Could not bind results", __FILE__, __LINE__);
	}
	if (!$query->execute()) {
		log::die("Could not execute query", __FILE__, __LINE__);
	}
	$query->fetch();
	$query->close();
	return $result;
}

function username_to_user_id(mysqli $conn, string $username)
{
	$sql = "SELECT id FROM users WHERE username=?"; // id is user_id
	$query = $conn->prepare($sql);
	if (!$query) {
		log::die("Could not execute query", __FILE__, __LINE__);
	}
	$query->bind_param("s", $username);
	if (!$query) {
		log::die("Could not bind params", __FILE__, __LINE__);
	}
	if (!$query->execute()) {
		log::die("Could not execute query", __FILE__, __LINE__);
	}
	$result = 0;
	if (!$query->bind_result($result)) {
		log::die("Could not bind results", __FILE__, __LINE__);
	}
	$query->close();
	return $result;
}

function user_id_to_username(mysqli $conn, int $user_id)
{
	$sql = "SELECT username FROM users WHERE id=?"; // id is user_id
	$query = $conn->prepare($sql);
	if (!$query) {
		log::die("Could not execute query", __FILE__, __LINE__);
	}
	$query->bind_param("i", $user_id);
	if (!$query) {
		log::die("Could not bind params", __FILE__, __LINE__);
	}
	if (!$query->execute()) {
		log::die("Could not execute query", __FILE__, __LINE__);
	}
	$result = "";
	if (!$query->bind_result($result)) {
		log::die("Could not bind results", __FILE__, __LINE__);
	}
	$query->close();
	return $result;
}

function user_id_to_name(mysqli $conn, int $user_id)
{
	$sql = "SELECT name from users WHERE id=?"; // id is user_id
	$query = $conn->prepare($sql);
	if (!$query) {
		log::die("Could not execute query", __FILE__, __LINE__);
	}
	$query->bind_param("i", $user_id);
	if (!$query) {
		log::die("Could not bind params", __FILE__, __LINE__);
	}
	if (!$query->execute()) {
		log::die("Could not execute query", __FILE__, __LINE__);
	}
	$result = "";
	if (!$query->bind_result($result)) {
		log::die("Could not bind results", __FILE__, __LINE__);
	}
	$query->fetch();
	$query->close();
	return $result;
}

function user_id_to_user_role(mysqli $conn, int $user_id)
{
	$sql = "SELECT role FROM users WHERE id=?"; // id is user_id
	$query = $conn->prepare($sql);
	if (!$query) {
		log::die("Could not execute query", __FILE__, __LINE__);
	}
	$query->bind_param("i", $user_id);
	if (!$query) {
		log::die("Could not bind params", __FILE__, __LINE__);
	}
	if (!$query->execute()) {
		log::die("Could not execute query", __FILE__, __LINE__);
	}
	$result = 0;
	if (!$query->bind_result($result)) {
		log::die("Could not bind results", __FILE__, __LINE__);
	}
	$query->close();
	return $result;
}

// print functions
function print_user_matrix(mysqli $conn)
{
	global $access_control;

	$sql = "SELECT id, username, name, role FROM users"; // id is user_id
	$query = $conn->prepare($sql);
	if (!$query) {
		log::die("Could not prepare statement", __FILE__, __LINE__);
	}
	$query->execute();
	if (!$query) {
		log::die("Could not execute statement", __FILE__, __LINE__);
	}
	$name = "";
	$user_id = "";
	$username = "";
	$role_id = "";
	$query->bind_result($user_id, $username, $name, $role_id);
	if (!$query) {
		log::die("Could not bind result", __FILE__, __LINE__);
	}
?>
	<div class="box">
		<table style="width: 100%;">
			<tr>
				<th>Username</th>
				<th>Name</th>
				<th>Role</th>
				<th>Actions</th>
			</tr>
			<?php
			while ($query->fetch()) { ?>
				<tr>
					<form method="POST" action="?users_action=update_user">
						<?php print_user_entry($user_id, $username, $name, $role_id, get_roles(connect("web"))); ?>
						<td><input type="submit" name="update_type" value="Save" style="width:49%;" />
							<?php
							if (role_id_to_string(connect("web"), $role_id) != "admin" && $access_control->can_access("users", "delete")) {
								print("<input type='submit' class='red' name='update_type' value='Delete' style='width:49%;'/>");
							}
							?>
						</td>
					</form>
				</tr>
			<?php } ?>
		</table>
	</div>
<?php
	$query->close();
}

function print_user_entry(int $user_id, string $username, string $name, string $role_id, array $roles)
{ ?>
	<input name="user_id" type="hidden" value="<?php print $user_id ?>" />
	<td><input name="username" type="text" value="<?php print $username ?>" /></td>
	<td><input name="name" type="text" value="<?php print $name ?>" /></td>
	<td>
		<select name="role_id">
			<?php foreach ($roles as $i => $r) {
				print "<option value='$i'" . ($i == $role_id ? " selected" : "") . ">$r</option>";
			} ?>
		</select>
	</td>
<?php
}

function print_forms()
{ ?>
	<div class="box">
		<h2>Add role</h2>
		<form method="POST" action="?users_action=create_role">
			<label for="new_role_name">Name:</label>
			<input name="new_role_name" type="text" placeholder="Designated Swimmer" />
			<input type="submit" value="Add" />
		</form>
		<h2>Modify role</h2>
		<form method="POST" action="?users_action=modify_role">
			<table style="width: 100%;">
				<tr>
					<th>Role name</th>
					<th>Action</th>
				</tr>
				<?php
				$roles = get_roles(connect("web"));
				foreach ($roles as $i => $r) {
					print_role_entry($i);
				} ?>
			</table>
		</form>
	</div>

	<div class="box">
		<h2>Add user</h2>
		<form method="POST" action="?users_action=create_user">
			<label for="username">Username:</label>
			<input name="username" type="text" placeholder="Username" required />
			<label for="name">Name:</label>
			<input name="name" type="text" placeholder="Name" required />
			<label for="email">Email:</label>
			<input name="email" type="email" placeholder="me@example.com" />
			<input type="submit" value="Add" />
		</form>
	</div>


<?php
}

function print_role_entry(int $role_id)
{
?>
	<tr>
		<td>
			<input name="role_id" type="text" value="<?php print role_id_to_string(connect("web"), $role_id); ?>" />
		</td>
		<td>
			<input type="submit" name="update_type" value="Save" style="width:49%;" />
			<input type="submit" name="update_type" value="Delete" style="width:49%;" />
		</td>
	</tr>
<link href="<?php global $settings; print $settings['baseurl'];?>/css/admin/users.css">
<script type="text/javascript" src="<?php global $settings; print $settings['baseurl'];?>/js/admin/users.js"></script>

<?php
}
