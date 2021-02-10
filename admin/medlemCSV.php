<div class="box">
<?php
if (!$access_control->can_access("admin", "memberCSV")) {
	header("HTTP/1.0 403 Forbidden");
	die("You do not have access to this page");
}

$conn = connect("member");

if (!$conn) {
	die("Connection failed: " . mysqli_connect_error());
}

$access_control->log("admin/memberCSV", "generate");

$sql = "SELECT * FROM ${settings['memberTable']} WHERE triatlon=0 AND YEAR(kontrolldato) = YEAR(NOW()) AND id IN (439, 440)";
$query = $conn->prepare($sql);
$query->execute();
$result = $query->get_result();

if ($result->num_rows == 0) {
	die("No members registered");
}
print "<table>";
$print_header = true;
while ($row = $result->fetch_assoc()) {
	if ($print_header) {
		$print_header = false;
		print "<tr>";
		$headers = array_keys($row);
		foreach ($headers as $header) {
			print "<th>" . $header;
			print "<input type='checkbox' id='include$row'></input>";
			print "</th>";
		}
		print "</tr>";
	}
	print "<tr>";
	foreach ($row as $value) {
		print "<td>" . $value . "</td>";
	}
	print "</tr>";
}
print "</table>";

$query->close();
$conn->close();
?>
</div>
