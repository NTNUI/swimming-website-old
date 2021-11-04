<?php

/**
 * Create a new connection to database.
 */
class DB
{
	private mysqli $conn;
	/**
	 * Connect to a database. Credentials are automatically retrieved from global $settings.
	 * Create a new instance like so
	 * $conn = new DB();
	 * @see __destruct method. It automatically disconnects form the database when this instance gets out of scope.
	 * @param string $database name
	 * @throws mysqli_sql_exception on failed connection
	 */
	public function __construct(string $database)
	{
		global $settings;
		mysqli_report(MYSQLI_REPORT_ALL);

		/**
		 * temporary workaround to allow use of english table name.
		 * TODO:
		 * 1. change source code to use the english name
		 * 2. rename table name from medlem to member and update the settings.json file
		 * 3. remove this workaround
		 */
		if ($database == 'member') {
			$database = "medlem";
		}

		$database = $settings["SQL_server"]["databases"][$database];
		$server_name = $settings["SQL_server"]["servername"];
		$username = $settings["SQL_server"]["username"];
		$password = $settings["SQL_server"]["password"];

		$this->conn = new mysqli($server_name, $username, $password, $database);
		if (!$this->conn->set_charset("utf8")) {
			throw new mysqli_sql_exception("Failed to set charset");
		}
	}
	/**
	 * Disconnect from the database when DB class gets out of scope.
	 */
	public function  __destruct()
	{
		if (!$this->conn->close()) {
			throw new mysqli_sql_exception("Could not close connection to db");
		}
	}
}

/**
 * @deprecated use class DB instead.
 * @see class DB() in library/util/db.php
 */
function connect($database)
{
	global $settings;
	mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_INDEX);

	/**
	 * temporary workaround to allow use of english table name.
	 * TODO:
	 * 1. change source code to use the english name
	 * 2. rename table name from medlem to member and update the settings.json file
	 * 3. remove this workaround
	 */
	if ($database == 'member') {
		$database = "medlem";
	}

	$database = $settings["SQL_server"]["databases"][$database];
	$server_name = $settings["SQL_server"]["servername"];
	$username = $settings["SQL_server"]["username"];
	$password = $settings["SQL_server"]["password"];

	if (!$database) {
		log::die("Failed to retrieve database name", __FILE__, __LINE__);
	}
	$conn = mysqli_connect($server_name, $username, $password, $database);
	if (!$conn) {
		log::die("Failed to connect to database: " . mysqli_connect_error(), __FILE__, __LINE__);
	}
	if (!$conn->set_charset("utf8")) {
		log::die("Failed to set utf8 charset: " . mysqli_error($conn), __FILE__, __LINE__);
	}
	return $conn;
}
