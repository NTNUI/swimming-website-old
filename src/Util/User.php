<?php

declare(strict_types=1);

namespace NTNUI\Swimming\Util;

use NTNUI\Swimming\Exception\Api\ApiException;
use NTNUI\Swimming\Exception\Api\UserException;
use Webmozart\Assert\Assert;

/**
 * @property-read string $name
 * @property-read string $username
 * @property-read ?\DateTime $passwordModified
 */
class User
{
    // these limits are not really necessary but you with php you never know. Better be safe than sorry.
    public const MAX_CHARS_USERNAME = 20;
    public const MIN_CHARS_USERNAME = 5;
    public const MAX_CHARS_NAME = 20;
    public const MIN_CHARS_NAME = 6;
    public const DATE_FORMAT = "Y-m-d"; // https://www.php.net/manual/en/datetime.format.php
    public const TIME_ZONE = "Europe/Oslo";

    private ?\DateTimeImmutable $passwordModified;

    #region constructors

    private function __construct(
        public readonly int $id,
        private string $name,
        private string $username,
        ?string $passwordModified = null,
    ) {
        if (strlen($name) > self::MAX_CHARS_NAME) {
            throw UserException::failedToCreateUser("name too long");
        }

        if (strlen($name) < self::MIN_CHARS_NAME) {
            throw UserException::failedToCreateUser("name too short");
        }

        if (strlen($username) > self::MAX_CHARS_USERNAME) {
            throw UserException::failedToCreateUser("username too long");
        }

        if (strlen($username) < self::MIN_CHARS_USERNAME) {
            throw UserException::failedToCreateUser("username too short");
        }

        if (isset($passwordModified)) {
            // user has changed password
            $this->passwordModified = \DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $passwordModified, new \DateTimeZone(self::TIME_ZONE));
        } else {
            // user has never changed the password
            $this->$passwordModified = null;
        }
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
            throw UserException::failedToCreateUser("username taken");
        }

        $db = new DB();
        $db->prepare('INSERT INTO users VALUES name=?, username=?');
        $db->bindParam('ss', $name, $username);
        $db->execute();

        $userId = $db->insertedId();
        $user = new self(
            name: $name,
            username: $username,
            id: $userId,
            passwordModified: null // password has never been set
        );

        $randomPassword = substr(md5((string)mt_rand()), 0, 9);

        self::sendRegistrationEmail($email, $randomPassword);
        return $user;
    }

    /**
     * get a user object from database
     *
     * @param integer $userId database row id
     * @return self
     */
    public static function fromId(int $userId): self
    {
        $db = new DB();
        $db->prepare('SELECT name, username, passwordModified FROM users WHERE id=?');
        $db->bindParam('i', $userId);
        $db->execute();
        $db->bindResult($name, $username, $passwordModified);
        if (!$db->fetch()) {
            throw UserException::userNotFound();
        }

        return new self(
            id: $userId,
            name: $name,
            username: $username,
            passwordModified: empty($passwordModified) ? null : $passwordModified,
        );
    }

    public static function fromUsername(string $username): self
    {
        $db = new DB();
        $db->prepare('SELECT id, name, passwordModified FROM users WHERE username=?');
        $db->bindParam('s', $username);
        $db->execute();
        $db->bindResult($userId, $name, $passwordModified);
        if (!$db->fetch()) {
            throw UserException::userNotFound();
        }
        return new self(
            id: $userId,
            name: $name,
            username: $username,
            passwordModified: empty($passwordModified) ? null : $passwordModified,
        );
    }

    #endregion

    #region getters

    public function __get(string $name): string|\DateTimeImmutable|int|NULL
    {
        Assert::propertyExists($this, $name);
        return $this->$name;
    }

    /**
     * toArray
     *
     * @return array{id:int,name:string,username:string,passwordModified:int}
     */
    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "username" => $this->username,
            "passwordModified" => $this->passwordModified->getTimestamp(),
        ];
    }

    #endregion

    #region setters

    public function setName(string $name): void
    {
        if (strlen($name) > self::MAX_CHARS_NAME) {
            throw UserException::userModifyException("name too long");
        }
        if (strlen($name) < self::MIN_CHARS_NAME) {
            throw UserException::userModifyException("name too short");
        }

        $db = new DB();
        $db->prepare('UPDATE users SET name=? WHERE id=?');
        $userId = $this->id;
        $db->bindParam('is', $userId, $name);
        $db->execute();
        $db->fetch();
        $this->name = $name;
    }

    public function setUsername(string $username): void
    {
        // following limits are taken by a random guess to avoid weird inputs just in case that fucks something up
        if (strlen($username) > self::MAX_CHARS_USERNAME) {
            throw UserException::userModifyException("username cannot be longer than " . self::MAX_CHARS_USERNAME);
        }

        if (strlen($username) < self::MIN_CHARS_USERNAME) {
            throw new \InvalidArgumentException("username cannot be shorter than" . self::MIN_CHARS_USERNAME);
        }
        if (self::usernameExists($username)) {
            throw UserException::failedToCreateUser("username already exists");
        }
        $db = new DB();
        $db->prepare('UPDATE users SET username=? WHERE id=?');
        $userId = $this->id;
        $db->bindParam('is', $userId, $username);
        $db->execute();
        $db->fetch();
        $this->username = $username;
    }

    #endregion

    #region handlers
    public function patchHandler(array $jsonObject): array
    {
        if (!array_key_exists("name", $jsonObject) && !array_key_exists("username", $jsonObject)) {
            throw ApiException::invalidRequest("nothing to patch");
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
        $db = new DB();
        $db->prepare('SELECT passwd FROM users WHERE id=?');
        $userId = $this->id;
        $db->bindParam('i', $userId);
        $db->execute();
        $passwordHash = "";
        $db->bindResult($passwordHash);
        $db->fetch();
        return password_verify($password, $passwordHash);
    }

    /**
     * postHandler
     *
     * @param array $jsonRequest
     * @return array{success:true,error:false,message:string}
     */
    public static function postHandler(array $jsonRequest): array
    {
        self::new(
            name: $jsonRequest["name"],
            username: $jsonRequest["username"],
            email: $jsonRequest["email"],
        );
        return [
            "success" => true,
            "error" => false,
            "message" => "user created successfully",
        ];
    }

    // This function needs to delete current instance. unset($this) is not allowed. disabling function for now.
    // 	public function deleteHandler(): array
    // 	{
    // 		$db = new DB();
    // 		$db->prepare('DELETE FROM users WHERE id=?');
    // 		$userId = $this->id;
    // 		$db->bindParam('i', $userId);
    // 		$db->execute();
    // 		return [
    // 			"success" => true,
    // 			"error" => false,
    // 			"message" => "user deleted successfully",
    // 		];
    // 	}

    #endregion

    #region static member functions


    public static function usernameExists(string $username): bool
    {
        $db = new DB();
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
        Assert::email($emailAddress);
        $emailTitle = "NTNUI Swimming: New user";

        $html = <<<HTML
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

    /**
     * getAllAsArray
     *
     * @return array{int,array{id:int,name:string,username:string,passwordModified:?int}}
     */
    public static function getAllAsArray(): array
    {
        $db = new DB();
        $db->prepare('SELECT id, name, username, passwordModified FROM users');
        $db->execute();
        $userId = 0;
        $name = "";
        $username = "";
        $passwordModified = null;
        $db->bindResult($userId, $name, $username, $passwordModified);
        $users = [];
        $user = [];
        while ($db->fetch()) {
            $user = null;

            Assert::integer($userId);
            Assert::string($name);
            Assert::string($username);
            Assert::nullOrString($passwordModified);

            $user["id"] = $userId;
            $user["name"] = $name;
            $user["username"] = $username;
            $user["passwordModified"] = empty($passwordModified) ? null : \DateTime::createFromFormat(self::DATE_FORMAT, $passwordModified, new \DateTimeZone(self::TIME_ZONE))->getTimestamp();
            array_push($users, $user);
        }
        return $users;
    }

    #endregion
}
