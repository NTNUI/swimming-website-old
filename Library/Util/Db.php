<?php

declare(strict_types=1);

/**
 * Create a new connection to database.
 */
class DB
{
	private mysqli $conn;
	public mysqli_stmt $stmt;
	/**
	 * Connect to a database. Credentials are automatically retrieved from environment variables.
	 * @param string $database name
	 * @throws Exception on failed connection
	 */
	public function __construct(string $database)
	{
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

		$hostname = $_ENV["DB_HOSTNAME"];
		$username = $_ENV["DB_USERNAME"];
		$password = $_ENV["DB_PASSWORD"];

		# TODO: remove "svommer_" from table names in database
		$this->conn = new mysqli($hostname, $username, $password, "svommer_" . $database);
		if (!$this->conn->set_charset("utf8")) {
			throw new Exception($this->conn->error);
		}
	}

	public function prepare(string $sql)
	{
		$this->stmt = $this->conn->prepare($sql);
		if (!$this->stmt) {
			throw new Exception($this->conn->error);
		}
	}

	public function bind_param(string $types, &$var1, &...$_)
	{
		$this->stmt->bind_param($types, $var1, ...$_);
		if (!$this->stmt) {
			throw new Exception($this->conn->error);
		}
	}

	public function execute()
	{
		if (!$this->stmt->execute()) {
			throw new Exception($this->conn->error);
		}
	}

	public function store_result()
	{
		if (!$this->stmt->store_result()) {
			throw new Exception($this->conn->error);
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
	public function execute_and_fetch(string $sql, string $types, &$var1, &...$_): array
	{
		$this->prepare($sql);
		$this->bind_param($types, $var1, ...$_);
		$this->execute();
		$meta = $this->stmt->result_metadata();
		if ($meta === false) {
			throw new Exception($this->conn->error);
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
			throw new Exception($this->conn->error);
		}
	}
	public function fetch()
	{
		$ret = $this->stmt->fetch();
		if ($ret === false) {
			throw new Exception($this->conn->error);
		}
		return $ret;
	}

	public function inserted_id()
	{
		return $this->conn->insert_id;
	}

	public function reset()
	{
		$ret = $this->stmt->reset();
		if ($ret === false) {
			throw new Exception($this->conn->error);
		}
		return $ret;
	}

	/**
	 * Disconnect from the database when DB class gets out of scope.
	 */
	public function  __destruct()
	{
		// close statement if present
		if (isset($this->stmt)) {
			if (!$this->stmt->close()) {
				throw new Exception($this->conn->error);
			}
		}
		if (isset($this->conn)) {
			if (!$this->conn->close()) {
				throw new Exception($this->conn->error);
			}
		}
	}
}
