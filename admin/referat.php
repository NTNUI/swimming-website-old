<?php
$root_folder = "src/board_meetings";
if (isset($_POST["add"])) {
	$date = strtotime($_POST["meetingDate"]);
	$year = date("Y", $date);
	$month = date("m", $date);
	$day = date("d", $date);
	$filename = $year . "_" . $month . "_" . $day . "_styremøtereferat.pdf";
	if (!is_dir("$root_folder/$year")) mkdir("$root_folder/$year");
	$reportpath = "$root_folder/$year/$filename";


	$report = $_POST['report'];
	//Stores the filename as it was on the client computer.
	$reportname = $_FILES['report']['name'];
	//Stores the filetype e.g image/jpeg
	$reporttype = $_FILES['report']['type'];
	//Stores any error codes from the upload.
	$reporterror = $_FILES['report']['error'];
	//Stores the tempname as it is given by the host when uploaded.
	$reporttemp = $_FILES['report']['tmp_name'];


	if(is_uploaded_file($reporttemp)) {
		if(!move_uploaded_file($reporttemp, $reportpath))  {
			print "FAILED TO SAVE FILE";
			die();
		}
	} else {
		//Default image
	}
	print "<div class='box'>";
	print "Rapport lagret som <a href='$base_url/$reportpath'>$reportpath</a><br>";
	print "</box>";
	return;
}
?>
<div class="box">
	<form method="POST" enctype="multipart/form-data">
		<label for="meetingDate">Dato for møtet</label>
		<input required type="date" name="meetingDate" placeholder="mm-dd-åååå" />
		<label for="report">Referat (må være i pdf)</label>
		<input required name="report" type="file" accept=".pdf"/>
		<button name="add" type="submit">Publiser</button>
	</form>
</div>
