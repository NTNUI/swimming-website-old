<?php

/**
 * Create a new connection to database.
 */
class DB
{
	private mysqli $conn;
	public mysqli_stmt $stmt;
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
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

		$database = $settings["SQL_server"]["databases"][$database];
		$server_name = $settings["SQL_server"]["servername"];
		$username = $settings["SQL_server"]["username"];
		$password = $settings["SQL_server"]["password"];

		$this->conn = new mysqli($server_name, $username, $password, $database);
		if (!$this->conn->set_charset("utf8")) {
			throw new mysqli_sql_exception("Failed to set charset");
		}
	}

	public function prepare(string $sql)
	{
		$this->stmt = $this->conn->prepare($sql);
		if (!$this->stmt) {
			throw new mysqli_sql_exception("Could not prepare statement");
		}
	}

	public function bind_param(string $types, &$var1, &...$_)
	{
		$this->stmt->bind_param($types, $var1, ...$_);
		if (!$this->stmt) {
			throw new mysqli_sql_exception("Could not bind params");
		}
	}

	public function execute()
	{
		if (!$this->stmt->execute()) {
			throw new mysqli_sql_exception("Could not execute statement");
		}
	}

	public function store_result()
	{
		if (!$this->stmt->store_result()) {
			throw new mysqli_sql_exception("Could not store result");
		}
	}

	public function num_rows(): int
	{
		return $this->stmt->num_rows();
	}

	/**
	 * Execute @param $sql and return the result in an array.
	 *
	 * @param string $sql query / statement
	 * @param string $types data types for parameter binding in prepared statement
	 * @param mixed $var1 
	 * @param mixed ...$_
	 * @return array results form db
	 */
	public function execute_and_fetch(string $sql, string $types, mixed &$var1, mixed &...$_): array
	{
		$this->prepare($sql);
		$this->bind_param($types, $var1, ...$_);
		$this->execute();
		$meta = $this->stmt->result_metadata();
		if ($meta === false) {
			throw new mysqli_sql_exception("Could not retrieve metadata");
		}
		$params = [];
		$row = [];
		while ($field = $meta->fetch_field()) {
			$params[] = &$row[$field->name];
		}

		call_user_func_array(array($this->stmt, 'bind_result'), $params);
		while ($this->stmt->fetch()) {
			foreach ($row as $key => $val) {
				$c[$key] = $val;
			}
			$result[] = $c;
		}
		return $result;
	}

	/**
	 * Does not work. Cannot bind results to variable for some reason.
	 * @deprecated use $db->stmt->bind_params() in stead. 
	 * @param [type] $var1
	 * @param [type] ...$_
	 * @return void
	 */
	public function bind_result(&$var1, &...$_)
	{
		if (!$this->stmt->bind_result($var1, $_)) {
			throw new mysqli_sql_exception($this->error);
		}
	}
	public function fetch()
	{
		$ret = $this->stmt->fetch();
		if($ret === false){
			throw new mysqli_sql_exception("Could not fetch data");
		}
		return $ret;
	}

	/**
	 * Disconnect from the database when DB class gets out of scope.
	 */
	public function  __destruct()
	{
		// close statement if present
		if ($this->stmt) {
			if (!$this->stmt->close()) {
				throw new mysqli_sql_exception("Could not close prepared statement");
			}
		}
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
