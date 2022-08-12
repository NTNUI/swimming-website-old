<?php

declare(strict_types=1);

use function PHPSTORM_META\type;

// TODO: fetch row as an associative array
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

		// TODO: remove "svommer_" from table names in database
		$this->conn = new mysqli($hostname, $username, $password, "svommer_" . $database);
		if (!$this->conn->set_charset("utf8")) {
			throw new \Exception($this->conn->error);
		}
	}

	/**
	 * Prepare an SQL statement for execution
	 *
	 * @param string $sql The query, as a string
	 * @return void
	 */
	public function prepare(string $sql): void
	{
		if (empty($sql)) {
			throw new \InvalidArgumentException("sql query is empty");
		}
		$statement = $this->conn->prepare($sql);
		if ($statement === false) {
			throw new \Exception($this->conn->error);
		}
		$this->stmt = $statement;
	}

	public function bindParam(string $types, mixed &$var1, mixed &...$_): void
	{
		if (!$this->stmt->bind_param($types, $var1, ...$_)) {
			throw new \Exception($this->conn->error);
		}
	}

	public function resultMetadata(): mysqli_result
	{
		$result = $this->stmt->result_metadata();
		if ($result === false) {
			throw new \Exception($this->conn->error);
		}
		return $result;
	}

	public function execute(): void
	{
		if (!$this->stmt->execute()) {
			throw new \Exception($this->conn->error);
		}
	}

	public function storeResult(): void
	{
		if (!$this->stmt->store_result()) {
			throw new \Exception($this->conn->error);
		}
	}

	public function affectedRows(): int
	{
		$affectedRows = $this->stmt->affected_rows;
		if (gettype($affectedRows) !== "int") {
			throw new \Exception("affected rows did not return an int");
		}
		return $affectedRows;
	}

	public function numRows(): int
	{
		return $this->stmt->num_rows();
	}

	public function bindResult(mixed &$var1, mixed &...$_): void
	{
		if (!$this->stmt->bind_result($var1, ...$_)) {
			throw new \Exception($this->conn->error);
		}
	}
	/**
	 * Fetch results from a prepared statement into the bound variables
	 *
	 * @return bool true if a row has been fetched to bounded variables. False if no more rows are available
	 */
	public function fetch(): bool
	{
		return match ($this->stmt->fetch()) {
			true => true,
			false => throw new \Exception($this->conn->error),
			NULL => false,
		};
	}

	public function insertedId(): int
	{
		// here we assume that column id is always a number
		return (int)$this->conn->insert_id;
	}

	public function reset(): void
	{
		if (!$this->stmt->reset()) {
			throw new \Exception($this->conn->error);
		}
		return;
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
	public function getError(): string
	{
		return $this->conn->error;
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
