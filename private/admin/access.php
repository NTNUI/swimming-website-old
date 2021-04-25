<?php


$conn = connect("web");

$roles = array();
$users = array();

$sql = "SELECT id, name FROM roles";
$query = $conn->prepare($sql);
$query->execute();
$query->bind_result($id, $role);
while ($query->fetch()) {
	$roles[$id] = $role;
}
$query->close();

$sql = "SELECT id, username FROM users";
$query = $conn->prepare($sql);
$query->execute();
$query->bind_result($id, $name);
while ($query->fetch()) {
	$users[$id] = $name;
}
$query->close();

$rolerules = array();
$userrules = array();

$roleId = intval($_REQUEST["role"]);
$userId = intval($_REQUEST["user"]);
$type = $_REQUEST["type"];
if ($type != "") {
	$table = "";
	$id = $_REQUEST["key"];

	$ruleType = $_REQUEST["ruleType"];
	$page = $_REQUEST["page"];

	if ($type == "addRole") {
		if ($ruleType == "" || $page == "") die("Wrong request format");
		$sql = "INSERT INTO role_access (role, type, page) VALUE (?, ?, ?)";
		$query = $conn->prepare($sql);
		$query->bind_param("iss", $roleId, $ruleType, $page);
		if ($query->execute()) { ?>
			<h1 class="box green">Record created</h1>
		<?php		} else { ?>
			<h1 class="box error">Something went wrong...</h1>
		<?php
		}
		$query->close();
		$access_control->log("admin/access", "add " . $page . " rule for g[" . $roleId . "] ", $ruleType);
	} else if ($type == "editRole") {
		$query = NULL;
		if ($_REQUEST["delete"] == 1) {
			$sql = "DELETE FROM role_access WHERE id=?";
			$query = $conn->prepare($sql);
			$query->bind_param("i", $id);
			$access_conrol->log("admin/access", "delete rule", $ruleId);
		} else {
			$sql = "UPDATE role_access SET type=?, page=? WHERE id=?";
			if ($ruleType == "" || $page == "") die("Wrong request format");
			$query = $conn->prepare($sql);
			$query->bind_param("ssi", $ruleType, $page, $id);
			$access_control->log("admin/access", "change " . $page . " rule for g[" . $roleId . "]", $ruleType);
		}
		if ($query->execute()) { ?>
			<h1 class="box green">Record updated</h1>
		<?php		} else { ?>
			<h1 class="box error">Something went wrong...</h1>
<?php
		}
		$query->close();
	}
}

if ($roleId == 0 && $userId == 0) $roleId = 1;

if ($roleId != 0) {
	$sql = "SELECT id, type, page FROM role_access WHERE role=?";
	$query = $conn->prepare($sql);
	$query->bind_param("i", $roleId);
	$query->execute();
	$query->bind_result($id, $type, $page);
	while ($query->fetch()) {
		$rolerules[] = array("id" => $id, "type" => $type, "page" => $page);
	}
	$query->close();
}
if ($userId != 0) {
	$sql = "SELECT id, type, page FROM user_access WHERE role=?";
	$query = $conn->prepare($sql);
	$query->bind_param("i", $userId);
	$query->execute();
	$query->bind_result($id, $type, $page);
	while ($query->fetch()) {
		$userrules[] = array("id" => $id, "type" => $type, "page" => $page);
	}
	$query->close();
}

$conn->close();
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
				print("<option value=$i" . ($roleId == $i ? " selected" : "") . ">$role</option>");
			} ?>
		</select>
		<button type="submit">Vis</button>
	</form>
</div>

<div class="box">
	<label for="">Rules</label>

	<?php if (sizeof($rolerules) > 0) { ?>
		<table style="width: 100%">
			<tr>
				<th>Type</th>
				<th>Page</th>
				<th>Actions</th>
			</tr>
			<?php foreach ($rolerules as $rule) {
			?>
				<form method="POST" action="?role=<?php print $roleId ?>&type=editRole">
					<input name="key" type="hidden" value="<?php print $rule["id"] ?>" />
					<tr>
						<td><select name="ruleType">
								<option value="ALLOW" <?php if ($rule["type"] == "ALLOW") {
															print "selected";
														} ?>>ALLOW</option>
								<option value="DENY" <?php if ($rule["type"] == "DENY") {
															print "selected";
														} ?>>DENY</option>
							</select></td>
						<td><input name="page" value="<?php print $rule["page"] ?>" /></td>
						<td><button type="submit">Lagre</button><button name="delete" value="1" type="submit">Slett</button></td>
					</tr>
				</form>
			<?php	}
			?>
		</table>
</div>
<?php } ?>

<div class="box">
	<label for="">Create new rule</label>
	<form method="POST" action="?role=<?php print $roleId ?>&type=addRole">
		<label for="ruleType">Type:</label>
		<select name="ruleType">
			<option value="ALLOW">ALLOW</option>
			<option value="DENY">DENY</option>
		</select>
		<label for="page">Side:</label>
		<input name="page" type="text" placeholder="admin/translations.php" />
		<button type="submit">Legg til</button>
	</form>
</div>

<div class="box">
	<label for="">User access rules</label>
	<h3 class="box error">Ikke implementert enda</h3>
	<form method="GET">
		<select name="user" style="width: 50%">
			<?php foreach ($users as $i => $user) {
				print("<option value=$i" . ($userId == $i ? " selected" : "") . ">$user</option>");
			} ?>
		</select>
		<button type="submit">Vis</button>
	</form>
</div>
<?php
