<?php
$conn = connect("medlem");
if(!$conn){
	header('HTTP/1.1 500 Internal Server Error');
	print("Failed to establish connection to database in memmbercheck\n");
	print(mysqli_error($conn));
	die();
}

$sql = "SELECT fornavn, etternavn FROM ${settings['memberTable']} WHERE YEAR(kontrolldato)=YEAR(now()) AND etternavn=? ORDER BY fornavn, etternavn";

$query = $conn->prepare($sql);
if($query === false){
	header('HTTP/1.1 500 Internal Server Error');
	print("Failed to prepare query in membercheck\n");
	print(mysqli_error($conn));
	die();
}

$lname = $_GET["lname"];
if($lname === NULL){
	header('HTTP/1.1 400 Bad Request');
	print("No parameters given\n");
	die();
}

$query->bind_param("s", $lname);
if(!$query){
	header('HTTP/1.1 500 Internal Server Error');
	print("Failed to bind params in membercheck\n");
	die();
}

$query->execute();
if(!$query){
	header('HTTP/1.1 500 Internal Server Error');
	print("Failed to execute query in membercheck\n");
	die();
}

$query->store_result();
if(!$query){
	header('HTTP/1.1 500 Internal Server Error');
	print("Failed to store result in membercheck\n");
	die();
}

$result = [];
$first_name = "";
$lastname = "";

if($query->num_rows > 0) {
	$query->bind_result($first_name, $lastname);
	if(!$query){
		header('HTTP/1.1 500 Internal Server Error');
		print("Failed to bind bind results in membercheck\n");
		die();
	}

	// save response to result array
	while($query->fetch()) {
		$result[] = array("fornavn" => $first_name, "etternavn" => $lastname);
	}
}

$query->close();
mysqli_close($conn);

// encode the result
$encoded_json = json_encode($result);
if($encoded_json === false){
	header('HTTP/1.1 500 Internal Server Error');
	print("Failed to encode json\n");
	die();
}

// return valid response
header("Content-Type: application/json; charset=UTF-8");
print($encoded_json);
