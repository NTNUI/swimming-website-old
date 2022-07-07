<?php
declare(strict_types=1);

global $settings;
$last_friday = date("N") == 5 ? "today" : "last friday";
$friday_beer = date("d-m-Y", strtotime($last_friday));
$db = new DB("web");
// TODO: fix hard coded role rules
$db->prepare("SELECT users.username, users.name, friday_beer.hadbeer FROM users LEFT JOIN (SELECT user_id, MAX(CASE WHEN date=? THEN 1 ELSE 0 END) as hadbeer FROM friday_beer GROUP BY user_id) friday_beer ON friday_beer.user_id = users.id WHERE users.role NOT IN (1, 3, 4)");
$date = date("Y-m-d", strtotime($last_friday));
$db->bind_param("s", $date);
$db->execute();
$db->stmt->bind_result($username, $name, $hadbeer);
while ($db->fetch()) {
	$nobeer = "nobeer" . ($hadbeer ? " hidden" : "");
	$beer = "beer" . ($hadbeer ? "" : " hidden");
	print "<div id='$username' class='box " . ($hadbeer ? "gold" : "") . "'>";
	print "<h3>$name</h3>";
	print "<span class='$nobeer'>Har ikke vært på fredagspils $friday_beer</span><br />";
	print "<button class='$nobeer' onclick='register(\"$username\")'>Registrer oppmøte</button>";
	print "<h1 class='$beer'>HAR DRUKKET ØØL</h1>";
	print "</div>";
}

?>
<link href="<?php print $settings['baseurl']; ?>/css/admin/fredagspils.css">
<script type="text/javascript" src="<?php print $settings['baseurl']; ?>/js/admin/fredagspils.js"></script>