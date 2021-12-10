<?php
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

// FIXME
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
		update_user($name, $username, $user_id, $role_id);
		break;
	case 'delete_user':
		delete_user($user_id);
		break;
	case 'create_role':
		create_role($new_role_name);
		break;
	case 'update_role':
		log::alert("action: $action is not yet implemented", __FILE__, __LINE__);
		break;
	case 'delete_role':
		delete_role(role_string_to_id($role_id));
		break;
	default:
		log::alert("action: $action is not implemented", __FILE__, __LINE__);
		break;
}

// print menus and quit
print_user_matrix();
print_forms();
return;


/**
 * Create a new user
 * Side effects:
 * - function call logged
 * - Sends a mail with password to given email address
 *
 * @param string $name
 * @param string $username
 * @param string $email
 * @return void
 */
function create_user(string $name, string $username, string $email)
{
	if (!$email || !$username || !$name) {
		log::die("parameters are not set", __FILE__, __LINE__);
	}

	if (Authenticator::username_exists($username)) {
		log::alert("username: $username already exists", __FILE__, __LINE__);
		return;
	}

	// create user with random password
	$random_password = substr(md5(mt_rand()), 0, 7);
	Authenticator::create_user($name, $username, $random_password);

	// log action
	global $access_control, $action;
	$access_control->log("users", $action, $username);

	// Send mail
	// TODO: move content to settings
	global $settings;
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
}


/**
 * Update user information. All parameters need to be set
 * Side effects:
 * - function call logged
 * - User alert()ed with success message
 * 
 * @param string $name Full name of the user
 * @param string $username username used to log in
 * @param integer $user_id static user id used to login
 * @param integer $role_id connected role
 * @return void
 */
function update_user(string $name, string $username, int $user_id, int $role_id)
{
	if (!$user_id || !$role_id || !$username || !$name) {
		log::die("parameters are not set", __FILE__, __LINE__);
	}

	// TODO: if username is updated, check that it does not collide with others
	$db = new DB("web");
	$db->prepare("UPDATE users SET username=?, name=?, role=? WHERE id=?");
	$db->bind_param("ssii", $username, $name, $role_id, $user_id);
	$db->execute();

	// log action
	global $access_control, $action;
	$access_control->log("admin/users", $action, user_id_to_name($user_id));

	log::alert("Record updated successfully");
}


/**
 * Delete a user
 * Side effects:
 * - function call logged
 * 
 * @param integer $user_id of user to be deleted
 * @return void
 */
function delete_user(int $user_id)
{
	if (!$user_id) {
		log::die("Parameters are not correct", __FILE__, __LINE__);
	}
	if (role_id_to_string(user_id_to_user_role($user_id)) === "admin") {
		log::alert("Admins cannot be deleted", __FILE__, __LINE__);
		return;
	}
	// TODO: if attempting to delete last user abort.
	// TODO: use transactions

	// Delete user
	$db = new DB("web");
	$db->prepare("DELETE FROM users WHERE id=?");
	$db->bind_param("i", $user_id);
	$db->execute();

	// log action
	global $access_control, $action;
	$name_user = user_id_to_name($user_id);
	$access_control->log("admin/users", $action, $name_user);
}


/**
 * Create a new role.
 * Side effect:
 * - function call logged
 * 
 * @param string $new_role_name name of the role
 * @return void
 */
function create_role(string $new_role_name)
{
	if (role_exists($new_role_name)) {
		log::alert("Role $new_role_name already exists", __FILE__, __LINE__);
		return;
	}

	// create new role
	$db = new DB("web");
	$db->prepare("INSERT INTO roles (name) VALUES (?)");
	$db->bind_param("s", $new_role_name);
	$db->execute();

	// log action
	global $access_control, $action;
	$access_control->log("admin/users", $action, $new_role_name);
}


/**
 * Delete role and all associated users connected to that role
 * Side effects:
 * - Log action
 * 
 * @param integer $role_id
 * @return void
 */
function delete_role(int $role_id)
{
	if (role_id_to_string($role_id) === "admin") {
		log::alert("Cannot delete admin role", __FILE__, __LINE__);
		return;
	}

	// delete users with the role
	$users = get_users_with_role_id($role_id);
	foreach ($users as $user) {
		delete_user($user);
	}

	// delete role
	$db = new DB("web");
	$db->prepare("DELETE FROM roles WHERE id=?");
	$db->bind_param("i", $role_id);
	$db->execute();

	// log action
	global $access_control, $action;
	$name_role = role_id_to_string($role_id);
	$access_control->log("admin/users", $action, $name_role);
}


/**
 * Get users with a @param role_id
 * 
 * @param integer $role_id
 * @return array of ints containing user_id's
 */
function get_users_with_role_id(int $role_id): array
{
	$db = new DB("web");
	$db->prepare("SELECT id FROM users WHERE role=?");
	$db->bind_param("i", $role_id);
	$db->execute();
	$user_id = 0;
	$db->stmt->bind_result($user_id);
	$result = array();
	while ($db->fetch()) {
		array_push($result, $user_id);
	}
	return $result;
}


/**
 * get all available roles
 *
 * @return array of strings containing role name indexed by role_id
 * @note @return array array values are indexed by role_id.
 */
function get_roles(): array
{
	$db = new DB("web");
	$db->prepare("SELECT id, name FROM roles");
	$db->execute();
	$user_id = "";
	$role = "";
	$db->stmt->bind_result($user_id, $role);
	$roles = array();
	while ($db->fetch()) {
		$roles[$user_id] = $role;
	}
	return $roles;
}

/**
 * Check if a role exists
 *
 * @param string $name_role
 * @return bool true if role exists. False otherwise.
 */
function role_exists(string $name_role): bool
{
	// count number of roles with a given name
	$db = new DB("web");
	$db->prepare("SELECT COUNT(*) FROM roles WHERE name=?");
	$db->bind_param("s", $name_role);
	$db->execute();
	$result = 0;
	$db->stmt->bind_result($result);
	$db->fetch();
	// convert amount to a boolean
	return (bool)$result;
}

/**
 * Get all usernames with @param $role_id
 * 
 * @param integer $role_id
 * @return array of strings with usernames
 */
function role_id_to_usernames(int $role_id): array
{
	$db = new DB("web");
	$db->prepare("SELECT username FROM users WHERE role=?");
	$db->bind_param("i", $role_id);
	$db->execute();
	$result = array();
	$db->stmt->bind_result($result);
	$db->fetch();
	return $result;
}

/**
 * Get the role name given its @param role_id
 *
 * @param integer $role_id of the role
 * @return string role name / title
 */
function role_id_to_string(int $role_id): string
{
	$db = new DB("web");
	$db->prepare("SELECT name from roles WHERE id=?");
	$db->bind_param("i", $role_id);
	$db->execute();
	$result = "";
	$db->stmt->bind_result($result);
	$db->fetch();
	return $result;
}

/**
 * Get role id given role name / title
 *
 * @param string $role_name
 * @return string
 */
function role_string_to_id(string $role_name): string
{
	$db = new DB("web");
	$db->prepare("SELECT id FROM roles WHERE name=?");
	$db->bind_param("s", $role_name);
	$db->execute();
	$result = 0;
	$db->stmt->bind_result($result);
	$db->fetch();
	return $result;
}

/**
 * Get user id given @param username
 *
 * @param string $username
 * @return integer user id
 */
function username_to_user_id(string $username): int
{
	$db = new DB("web");
	$db->prepare("SELECT id FROM users WHERE username=?");
	$db->bind_param("s", $username);
	$db->execute();
	$result = 0;
	$db->stmt->bind_result($result);
	return $result;
}

/**
 * get username given a @param user_id
 *
 * @param integer $user_id
 * @return string username
 */
function user_id_to_username(int $user_id): string
{
	$db = new DB("web");
	$db->prepare("SELECT username FROM users WHERE id=?");
	$db->bind_param("i", $user_id);
	$db->execute();
	$result = "";
	$db->stmt->bind_result($result);
	return $result;
}

/**
 * get name of the user with a give @param user_id
 *
 * @param integer $user_id
 * @return string name of the user
 */
function user_id_to_name(int $user_id): string
{
	$db = new DB("web");
	$db->prepare("SELECT name from users WHERE id=?");
	$db->bind_param("i", $user_id);
	$db->execute();
	$result = "";
	$db->stmt->bind_result($result);
	$db->fetch();
	return $result;
}

/**
 * get role id given user id
 *
 * @param integer $user_id
 * @return integer role_id
 */
function user_id_to_user_role(int $user_id): int
{
	$db = new DB("web");
	$db->prepare("SELECT role FROM users WHERE id=?");
	$db->bind_param("i", $user_id);
	$db->execute();
	$result = 0;
	$db->stmt->bind_result($result);
	return $result;
}


/**
 * Prints out rows of available users
 * prints out html content.
 * 
 * @return void
 */
function print_user_matrix()
{
	// get user info
	$db = new DB("web");
	$db->prepare("SELECT id, username, name, role FROM users");
	$db->execute();
	$name = "";
	$user_id = "";
	$username = "";
	$role_id = "";
	$db->stmt->bind_result($user_id, $username, $name, $role_id);

	// create a row with actions for each user. 
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
			while ($db->fetch()) { ?>
				<tr>
					<form method="POST" action="?users_action=update_user">
						<?php print_user_entry($user_id, $username, $name, $role_id, get_roles()); ?>
						<td><input type="submit" name="update_type" value="Save" style="width:49%;" />
							<?php
							global $access_control;
							if (role_id_to_string($role_id) != "admin" && $access_control->can_access("users", "delete")) {
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
}


/**
 * Print out a row with user management actions
 *
 * @param integer $user_id
 * @param string $username
 * @param string $name
 * @param string $role_id current role.
 * @param array $roles list of available rows
 * @return void
 * 
 * TODO: role_id should be an int not string. Investigate
 */
function print_user_entry(int $user_id, string $username, string $name, string $role_id, array $roles)
{ ?>
	<input name="user_id" type="hidden" value="<?php print $user_id ?>" />
	<td><input name="username" type="text" value="<?php print $username ?>" /></td>
	<td><input name="name" type="text" value="<?php print $name ?>" /></td>
	<td>
		<select name="role_id">
			<?php foreach ($roles as $i => $r) {
				// print available roles
				print "<option value='$i'" . ($i == $role_id ? " selected" : "") . ">$r</option>";
			} ?>
		</select>
	</td>
<?php
}


/**
 * Prints out three forms in html.
 * - Add new role
 * - Modify role
 * - Add new user
 * 
 * @return void
 */
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
				$roles = get_roles();
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


/**
 * Prints out a row for role editing.
 * Change name and delete.
 *
 * @param integer $role_id role to modify
 * @return void
 */
function print_role_entry(int $role_id)
{
	global $settings;
?>
	<tr>
		<td>
			<input name="role_id" type="text" value="<?php print role_id_to_string($role_id); ?>" />
		</td>
		<td>
			<input type="submit" name="update_type" value="Save" style="width:49%;" />
			<input type="submit" name="update_type" value="Delete" style="width:49%;" />
		</td>
	</tr>
	<link href="<?php print $settings['baseurl']; ?>/css/admin/users.css">
	<script type="text/javascript" src="<?php print $settings['baseurl']; ?>/js/admin/users.js"></script>

<?php
}
