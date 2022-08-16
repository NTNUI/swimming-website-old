<?php

declare(strict_types=1);

require_once(__DIR__ . "/Db.php");
require_once(__DIR__ . "/Authenticator.php");
require_once(__DIR__ . "/Log.php");

class User
{
	const DATE_FORMAT = "Y-m-d H:i:s"; // https://www.php.net/manual/en/datetime.format.php
	const TIME_ZONE = "Europe/Oslo";

	// allow read access for all member variables but disallow setting them freely
	public function __get(string $name): string|DateTime|int|NULL
	{
		if (!property_exists($this, $name)) {
			throw new Exception("property does not exists");
		}
		return $this->$name;
	}

	#region constructors
	private function __construct(
		private string $name,
		private string $username,
		private ?DateTime $passwordSetDate = NULL,
		private ?int $dbRowId = NULL,
	) {
		// length limitations are there just in case
		if (strlen($name) > 40) {
			throw new \InvalidArgumentException("name too long");
		}
		if (strlen($name) < 6) {
			throw new \InvalidArgumentException("name too short");
		}

		if (strlen($username) > 20) {
			throw new \InvalidArgumentException("username too long");
		}

		if (strlen($username) < 5) {
			throw new InvalidArgumentException("username too short");
		}
	}

	public static function postHandler(array $jsonRequest): array
	{
		new self(
			name: $jsonRequest["name"],
			username: $jsonRequest["username"]
		);
		return [
			"success" => true,
			"error" => false,
			"message" => "user created successfully",
		];
	}
	// 
	public function deleteHandler(): array
	{
		throw new NotImplementedException("delete handler not implemented yet");
		// after db deletion this instance needs to be deleted. unset($this) does not work
		$db = new DB('web');
		$db->prepare('DELETE FROM users WHERE id=?');
		$id = $this->dbRowId;
		$db->bindParam('i', $id);
		$db->execute();
		return [
			"success" => true,
			"error" => false,
			"message" => "user deleted successfully",
		];
	}

	/**
	 * Create new user
	 * 
	 * New users will get an email with a random password.
	 * New users will have to change their password on their first login
	 *
	 * @param string $name
	 * @param string $username
	 * @return self
	 */
	public static function new(
		string $name,
		string $username,
		string $email
	): self {
		if (self::usernameExists($username)) {
			throw new CreateException("username taken");
		}
		$user = new self($name, $username);


		$db = new DB('web');
		$db->prepare('INSERT INTO users VALUES name=?, username=?');
		$db->bindParam('ss', $name, $username);
		$db->execute();
		$user->DbRowId = $db->insertedId();

		$randomPassword = substr(md5((string)mt_rand()), 0, 9);

		self::sendRegistrationEmail($email, $randomPassword);
		return $user;
	}

	/**
	 * get a user object from database
	 *
	 * @param integer $dbRowId
	 * @return self
	 */
	public static function fromId(int $dbRowId): self
	{
		$db = new DB('web');
		$db->prepare('SELECT name, username, lastPassword FROM users WHERE id=?');
		$db->bindParam('i', $dbRowId);
		$db->execute();
		$db->bindResult($name, $username, $lastPassword);
		if (!$db->fetch()) {
			throw new UserNotFoundException();
		}

		return new self($name, $username, $lastPassword, $dbRowId);
	}
	
	public static function fromUsername(string $username): self
	{
		$db = new DB('web');
		$db->prepare('SELECT id, name, lastPassword FROM users WHERE username=?');
		$db->bindParam('s', $username);
		$db->execute();
		$db->bindResult($id, $name, $lastPassword);
		if (!$db->fetch()) {
			throw new UserNotFoundException();
		}

		return new self($name, $username, $lastPassword, $id);
	}
	#endregion

	#region getters
	public function toArray(): array
	{
		return [
			"id" => $this->dbRowId,
			"name" => $this->name,
			"username" => $this->username,
			"lastPasswordDate" => $this->passwordSetDate,
		];
	}
	#endregion

	#region setters
	public function setName(string $name): void
	{
		if (strlen($name) > 40) {
			throw new \InvalidArgumentException("name too long");
		}
		if (strlen($name) < 6) {
			throw new \InvalidArgumentException("name too short");
		}

		$db = new DB('web');
		$db->prepare('UPDATE users SET name=? WHERE id=?');
		$id = $this->dbRowId;
		$db->bindParam('is', $id, $name);
		$db->execute();
		$db->fetch();
		$this->name = $name;
	}

	public function setUsername(string $username): void
	{
		if (strlen($username) > 20) {
			throw new \InvalidArgumentException("username too long");
		}

		if (strlen($username) < 5) {
			throw new InvalidArgumentException("username too short");
		}
		if (self::usernameExists($username)) {
			throw new \InvalidArgumentException(); // TODO: new exception that represents this error.
		}
		$db = new DB('web');
		$db->prepare('UPDATE users SET username=? WHERE id=?');
		$id = $this->dbRowId;
		$db->bindParam('is', $id, $username);
		$db->execute();
		$db->fetch();
		$this->username = $username;
	}

	#endregion

	#region handlers
	public function patchHandler(array $jsonObject): array
	{
		if (!array_key_exists("name", $jsonObject) && !array_key_exists("username", $jsonObject)) {
			throw new \InvalidArgumentException("nothing to patch");
		}
		if (array_key_exists("name", $jsonObject)) {
			$this->setName($jsonObject["name"]);
		}
		if (array_key_exists("username", $jsonObject)) {
			$this->setUsername($jsonObject["username"]);
		}
		return [
			"success" => true,
			"error" => false,
			"message" => "records updated successfully",
		];
	}

	public function verifyPassword(string $password): bool
	{
		$db = new DB('web');
		$db->prepare('SELECT passwd FROM users WHERE id=?');
		$id = $this->dbRowId;
		$db->bindParam('i', $id);
		$db->execute();
		$passwordHash = "";
		$db->bindResult($passwordHash);
		$db->fetch();		
        return password_verify($password, $passwordHash);
	}

	#endregion

	#region static member functions


	public static function usernameExists(string $username): bool
	{
		$db = new DB("web");
		$db->prepare("SELECT COUNT(*) FROM users WHERE username=?");
		$db->bindParam("s", $username);
		$result = 0;
		$db->execute();
		$db->bindResult($result);
		$db->fetch();
		return (bool)$result;
	}

	private static function sendRegistrationEmail(string $emailAddress, string $randomPassword): void
	{
		$emailTitle = "NTNUI Swimming: New user";

		$html = <<<'HTML'
		Your user account has been created.
		Password: $randomPassword
		
		You'll have to change it on first login.
		HTML;


		$result = @mail(
			$emailAddress,
			$emailTitle,
			nl2br($html)
		);
		/* if (!$result) {
			throw new \Exception("failed to send registration email for user");
		} */
	}

	public static function getAllAsArray(): array
	{
		$db = new DB('web');
		$db->prepare('SELECT id, name, username, lastPassword FROM users');
		$db->execute();
		$id = 0;
		$name = 0;
		$username = "";
		$lastPassword = NULL;
		$db->bindResult($id, $name, $username, $lastPassword);
		$users = [];
		$user = [];
		while ($db->fetch()) {
			$user = [
				"id" => $id,
				"name" => $name,
				"username" => $username,
				"lastPassword" => $lastPassword
			];
			array_push($users, $user);
		}
		return $users;
	}

	#endregion

}

