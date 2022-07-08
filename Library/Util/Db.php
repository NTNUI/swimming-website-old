<?php

declare(strict_types=1);

/**
 * Create a new connection to database.
 */
class DB
{
	private mysqli $conn;
	private mysqli_stmt $stmt;
	/**
	 * Connect to a database. Credentials are automatically retrieved from environment variables.
	 * @param string $database name
	 */
	public function __construct(string $database)
	{
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

		$hostname = $_ENV["DB_HOSTNAME"];
		$username = $_ENV["DB_USERNAME"];
		$password = $_ENV["DB_PASSWORD"];

		# TODO: remove "svommer_" from table names in database
		try {
			$this->conn = new mysqli($hostname, $username, $password, "svommer_" . $database);
			if (!$this->conn->set_charset("utf8")) {
				throw new \Exception($this->conn->error);
			}
		} catch (\mysqli_sql_exception $_) {
			throw new \Exception($this->conn::$error);
		}
	}

	/**
	 * Prepare an SQL statement for execution
	 *
	 * @param string $sql The query, as a string
	 * @return void
	 */
	public function prepare(string $sql)
	{
		if (empty($sql)) {
			throw new \Exception("sql query is empty");
		}
		$this->stmt = $this->conn->prepare($sql);
		if ($this->stmt === false) {
			throw new \Exception($this->conn->error);
		}
	}

	public function bind_param(string $types, &$var1, &...$_)
	{
		$this->stmt->bind_param($types, $var1, ...$_);
		if (!$this->stmt) {
			throw new \Exception($this->conn->error);
		}
	}

	public function execute()
	{
		if (!$this->stmt->execute()) {
			throw new \Exception($this->conn->error);
		}
	}

	public function store_result()
	{
		if (!$this->stmt->store_result()) {
			throw new \Exception($this->conn->error);
		}
	}

	public function num_rows(): int
	{
		return $this->stmt->num_rows();
	}

	/**
	 * Does not work. Cannot bind results to variable for some reason.
	 * @param [type] $var1
	 * @param [type] ...$_
	 * @return void
	 */
	public function bind_result(&$var1, &...$_)
	{
		if (!$this->stmt->bind_result($var1, ...$_)) {
			throw new \Exception($this->conn->error);
		}
	}
	public function fetch()
	{
		$ret = $this->stmt->fetch();
		if ($ret === false) {
			throw new \Exception($this->conn->error);
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
			throw new \Exception($this->conn->error);
		}
		return $ret;
	}
	public function close(): void
	{
		// close statement if present
		if (isset($this->stmt)) {
			if (!$this->stmt->close()) {
				throw new \Exception($this->conn->error);
			}
		}
	}
	/**
	 * Disconnect from the database when DB class gets out of scope.
	 */
	public function  __destruct()
	{
		// close connection
		$this->close();
		if (isset($this->conn)) {
			if (!$this->conn->close()) {
				throw new \Exception($this->conn->error);
			}
		}
	}
}
