<?php
declare(strict_types=1);

// Fuck this mess...
require_once("library/util/db.php");

// get roles
$roles = array(); {
	$db = new DB("web");
	$roles = array();
	$db->prepare("SELECT id, name FROM roles");
	$db->execute();
	$role_id = 0;
	$role_name = "";
	$db->stmt->bind_result($role_id, $role_name);
	while ($db->fetch()) {
		$roles[$role_id] = $role_name;
	}
}

// get users
$users = array(); {
	$db = new DB("web");
	$sql = "SELECT id, username FROM users";
	$db->prepare($sql);
	$db->execute();
	$user_id = 0;
	$username = "";
	$db->stmt->bind_result($user_id, $username);
	while ($db->fetch()) {
		$users[$user_id] = $username;
	}
}

$role_rules = array();

$role_id = intval(argsURL("REQUEST", "role"));
$user_id = intval(argsURL("REQUEST", "user"));
$type = argsURL("REQUEST", "type");

if ($type !== "") {
	$table = "";

	$id = argsURL("REQUEST", "key");
	$ruleType = argsURL("REQUEST", "ruleType");
	$page = argsURL("REQUEST", "page");

	if ($type === "addRole") {
		if ($ruleType === "" || $page === "") die("Wrong request format");
		$db = new DB("web");
		$db->prepare("INSERT INTO role_access (role, type, page) VALUE (?, ?, ?)");
		$db->bind_param("iss", $role_id, $ruleType, $page);
		if ($db->execute()) { ?>
			<h1 class="box">Record created</h1>
		<?php
		} else { ?>
			<h1 class="box error">Something went wrong...</h1>
		<?php
		}
		$access_control->log("admin/access", "add " . $page . " rule for g[" . $role_id . "] ", $ruleType);
	} else if ($type === "editRole") {
		$db = new DB("web");
		if ($_REQUEST["delete"] === 1) {

			$db->prepare("DELETE FROM role_access WHERE id=?");
			$db->bind_param("i", $id);
			$access_control->log("admin/access", "delete rule", $ruleId);
		} else {
			if ($ruleType === "" || $page === "") die("Wrong request format");
			$db->prepare("UPDATE role_access SET type=?, page=? WHERE id=?");
			$db->bind_param("ssi", $ruleType, $page, $id);
			$access_control->log("admin/access", "change " . $page . " rule for g[" . $role_id . "]", $ruleType);
		}
		if ($db->execute()) { ?>
			<h1 class="box">Record updated</h1>
		<?php
		} else {
		?>
			<h1 class="box error">Something went wrong...</h1>
<?php
		}
	}
}

if ($role_id === 0 && $user_id === 0) $role_id = 1;

if ($role_id !== 0) {
	$db = new DB("web");
	$db->prepare("SELECT id, type, page FROM role_access WHERE role=?");
	$db->bind_param("i", $role_id);
	$db->execute();
	$db->stmt->bind_result($role_access_id, $type, $page);
	while ($db->fetch()) {
		$role_rules[] = array("id" => $role_access_id, "type" => $type, "page" => $page);
	}
}

?>

<div class="box">
	<h2>Role access rules</h2>
	<p>Create groups, put users in groups, create rules for groups.</p>
</div>

<div class="box">
	<label>Groups</label>
	<form method="GET">
		<select name="role" style="width: 50%">
			<?php foreach ($roles as $i => $role) {
				print("<option value=$i" . ($role_id === $i ? " selected" : "") . ">$role</option>");
			} ?>
		</select>
		<button type="submit">Show</button>
	</form>
</div>

<div class="box">
	<label for="">Rules</label>

	<?php if (sizeof($role_rules) > 0) { ?>
		<table style="width: 100%">
			<tr>
				<th>Type</th>
				<th>Page</th>
				<th>Actions</th>
			</tr>
			<?php foreach ($role_rules as $rule) {
			?>
				<form method="POST" action="?role=<?php print $role_id ?>&type=editRole">
					<input name="key" type="hidden" value="<?php print $rule["id"] ?>" />
					<tr>
						<td><select name="ruleType">
								<option value="ALLOW" <?php if ($rule["type"] === "ALLOW") {
															print "selected";
														} ?>>ALLOW</option>
								<option value="DENY" <?php if ($rule["type"] === "DENY") {
															print "selected";
														} ?>>DENY</option>
							</select></td>
						<td><input name="page" value="<?php print $rule["page"] ?>" /></td>
						<td><button type="submit">Save</button><button name="delete" value="1" type="submit">Delete</button></td>
					</tr>
				</form>
			<?php	}
			?>
		</table>
</div>
<?php } ?>

<div class="box">
	<label for="">Create new rule</label>
	<form method="POST" action="?role=<?php print $role_id ?>&type=addRole">
		<label for="ruleType">Type:</label>
		<select name="ruleType">
			<option value="ALLOW">ALLOW</option>
			<option value="DENY">DENY</option>
		</select>
		<label for="page">Page:</label>
		<input name="page" type="text" placeholder="admin/translations.php" />
		<button type="submit">Add</button>
	</form>
</div>
<?php
