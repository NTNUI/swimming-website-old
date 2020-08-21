Godkjenning av medlem.<br>
<br>

<?php

//henter id variabelen for medlemmet som skal godkjennes
$id = $_REQUEST['id'];
//velger hovedside hvis ingen side er valgt
if($id != ""){

		$servername = "mysql.stud.ntnu.no";
		$username = "svommer_web";
		$password = "svom12";
		$dbname = "svommer_medlem";

		// Create connection
		$conn = mysqli_connect($servername, $username, $password, $dbname);
		$conn->set_charset("utf8");
		// Check connection
		if (!$conn) {
		    die("Connection failed: " . mysqli_connect_error());
		}

		$sql = "UPDATE medlem_2009 SET kontrolldato = NOW( ) WHERE id = $id";
		if (mysqli_query($conn, $sql)) {
		    echo "Record updated successfully<br>";
		} else {
		    echo "Error updating record: " . mysqli_error($conn);
		}
		$sql = "UPDATE medlem_2020 SET kontrolldato = NOW() WHERE id=?";
		$update = $conn->prepare($sql);
		$update->bind_param("i", $id);
		$update->execute();
		$update->close();

		//henter data
		$sql = "SELECT * FROM medlem_2009 WHERE id = '$id'";

		$result = mysqli_query($conn, $sql);

		$row = mysqli_fetch_assoc($result);

		printf("%s \n", $row["fornavn"]);
		printf("%s \n", $row["etternavn"]);
		printf("ble registrert.<br>");


		$sendTo = $row["epost"];

		$subject = "NTNUI Svomming: Medlemskap godkjent / Membership approved";

		$headers .= "Dette er en automatisk e-post sendt av NTNUI-Svømmegruppas medlemssystem. \n";
		$headers .= "\n";
		$headers .= "Din innmelding er registrert og godkjent i vår database.\n";
		$headers .= "En ny adgangsliste blir levert til Pirbadet fortløpende (vanligvis i kveld, kanskje i morgen), slik at du kan begynne å trene. \n";
		$headers .= "\n-------\n\n";
		
		$headers .= "This is an automatic e-mail sent from the membership system in NTNUI Swimming group. \n";
		$headers .= "\n";
		$headers .= "Your enrollment is registered and approved in our database.\n";
		$headers .= "A new list will be delivered to Pirbadet today or tomorrow, so you can start swimming with us. \n";

		mail($sendTo, $subject, $headers, "From: svommer-kasserer@list.stud.ntnu.no");

		mysqli_close($conn);


echo "E-post er sendt til medlemmet.<br>";

}
else{
	echo "Du må velge ett medlem for å godkjenne det.<br>";
}


?>

<a href="http://org.ntnu.no/svommer/index.php?side=medlemsreg.php">Registrer flere medlemmer</a>
