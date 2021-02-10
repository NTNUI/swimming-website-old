<?php
$sql = "SELECT fornavn, etternavn FROM ${settings['memberTable']} WHERE YEAR(kontrolldato)=YEAR(now()) AND etternavn=? ORDER BY fornavn, etternavn";
$result = mysqli_query($conn, $sql);
$lastname = $_GET["lname"];


$conn = connect("member");

$query = $conn->prepare($sql);
$query->bind_param("s", $lastname);
/*if (!$result) {
   die('Could not query:' . mysql_error());
}*/
$query->execute();
$query->store_result();
$result = [];
if($query->num_rows > 0) {
	$query->bind_result($forn, $ettern);
	while($query->fetch()) {
	//	echo "- " . $row["etternavn"]. ", ";
	//	echo "" . $row["fornavn"]. "<br>";
		$result[] = array("fornavn" => $forn, "etternavn" => $ettern);
	}
}
$query->close();
mysqli_close($conn);

header("Content-Type: application/json; charset=UTF-8");
print json_encode($result);

?>
