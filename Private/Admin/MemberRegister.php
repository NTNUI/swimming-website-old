<?php
declare(strict_types=1);

require_once("Library/Templates/Content.php");

print_content_header(
	"Membership requests " . date('Y'),
	"Manual review required for listed users"
);
function createTime($year, $month, $day)
{
	if ($year == 0 and $month == 0) {
		if ($day == 0) return "today";
		if ($day == 1) return "yesterday";
	}
	$time = "";

	if ($year != 0) {
		$time .= $year . " år ";
	}

	if ($month != 0) {
		$time .= $month . ($month == 1 ? " month " : " months");
	}

	$time .= $day . ($day == 1 ? " day" : " days");
	$time .= " since";
	return $time;
}

if (Authenticator::is_logged_in()) {
	$db = new DB("member");

	$sql = "SELECT id, first_name, surname, registration_date, email, licensee FROM member WHERE approved_date IS NULL OR YEAR(approved_date) <> DATE('y') ORDER BY id DESC";

	$db->prepare($sql);

	$db->execute();
	$db->stmt->bind_result($id, $first_name, $surname, $registration_date, $email, $licensee);
	while ($db->fetch()) {
		$surname = htmlspecialchars($surname);
		$first_name = htmlspecialchars($first_name);
		$email = htmlspecialchars($email);

		$interval = date_diff(date_create(), date_create($registration_date));
		$time = createTime($interval->y, $interval->m, $interval->d);
?>
		<div class="box" id="member-<?php print $id ?>">
			<h2 style="display: inline-block;"><?php print "$surname, $first_name" ?></h2>
			<button style="float: right;" onclick="approve(<?php print $id ?>)">Approve</button>
			<table class="center_table" style="width: 100%">
				<tr>
					<td>Registration date:</td>
					<td tooltip="test"><?php print $time ?> (<?php print $registration_date ?>)</td>
				</tr>
				<?php if ($licensee != "") { ?>
					<tr>
						<td>Active license:</td>
						<td>
							<?php print $licensee ?>
						</td>
					</tr>
				<?php } ?>
			</table>
			<a class="btn" href="mailto:<?php print $email ?>">Send email</a>
		</div>
<?php			}
}

?>
<script type="text/javascript" src="<?php print Settings::get_instance()->get_baseurl(); ?>/js/admin/member_register.js"></script>