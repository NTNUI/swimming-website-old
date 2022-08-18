<?php

declare(strict_types=1);

require_once(__DIR__ . "/Cin.php");
require_once(__DIR__ . "/Db.php");
require_once(__DIR__ . "/Gender.php");
require_once(__DIR__ . "/Hash.php");
require_once(__DIR__ . "/Log.php");

use \libphonenumber\PhoneNumberUtil;
use \libphonenumber\PhoneNumber;
use \libphonenumber\PhoneNumberFormat;


/**
 * class Member
 * 
 * Create a new Member object by calling one of static constructors:
 * - Member::new() creates a new member. New members are not active
 * - Member::fromPhone() fetches one member from database by phone number
 * - Member::fromId() fetches one member from database by row id
 * default constructor is private and should only be called internally to guarantee that
 * any instance of member correspond to a row in the database.
 * 
 * all modifiers are protected by login check using Authenticator class
 *
 */
class Member
{
    const DATE_FORMAT = "Y-m-d"; // https://www.php.net/manual/en/datetime.format.php
    const TIME_ZONE = "Europe/Oslo";
    const FILTER_OPTIONS_ZIP = [
        "flag" => FILTER_NULL_ON_FAILURE,
        "min_range" => 1000,
        "max_range" => 9999
    ];

    #region constructor

    /**
     * constructor
     * 
     * Constructs a new Member instance. Validates input upon creation
     *
     * @param string $name
     * @param DateTime $birthDate
     * @param PhoneNumber $phone
     * @param Gender $gender
     * @param string $email
     * @param string $address
     * @param int $zip
     * @param ?string $license
     * @param DateTime $registrationDate
     * @param ?DateTime $approvedDate
     * @param bool $haveVolunteered
     * @param bool $licenseForwarded
     * @param ?int $cinId database cin row id
     * @param ?int $id database member row id
     * 
     * @throws MemberIsActiveException if a member already exists
     * @throws InvalidArgumentException on input error
     * @throws Exception on unrecoverable error
     * 
     * TODO: check if $id can be required by caller
     */
    private function __construct(
        private string $name,
        private DateTime $birthDate,
        private PhoneNumber $phone,
        private Gender $gender,
        private string $email,
        private string $address,
        private int $zip,
        private ?string $license,
        private DateTime $registrationDate = new DateTime('now', new DateTimeZone(self::TIME_ZONE)),
        private ?DateTime $approvedDate = NULL,
        private bool $haveVolunteered = false,
        private bool $licenseForwarded = false,
        private ?int $cinId = null,
        private ?int $id = NULL
    ) {
        try {
            if (self::exists($phone)) {
                if (self::isActive($phone)) {
                    throw new MemberIsActiveException();
                }
                throw new \Exception("user has registered but is not active yet"); // TODO: create new exception
            }
        } catch (MemberNotFoundException $_) {
        }

        // truncate strings
        $name = htmlspecialchars(self::trimSpace($name));
        $email = htmlspecialchars(self::trimSpace($email));
        $address = htmlspecialchars(self::trimSpace($address));
        $license = htmlspecialchars(self::trimSpace($license));


        // validate ints
        $zip = filter_var($zip, FILTER_VALIDATE_INT, self::FILTER_OPTIONS_ZIP);
        $email = filter_var($email, FILTER_VALIDATE_EMAIL,  FILTER_NULL_ON_FAILURE);

        // validate strings
        if (strlen(self::getFirstName($name)) < 2) {
            throw new \InvalidArgumentException("name too short");
        }
        if (strlen(self::getSurname($name)) < 3) {
            throw new \InvalidArgumentException("name too short");
        }
        if (strlen($address) < 6) {
            throw new \InvalidArgumentException("address too short");
        }
        if (strlen($name) > 40) {
            throw new \InvalidArgumentException("name too long");
        }
        // Age validation
        if (self::getAge($birthDate) < 18) {
            throw new \InvalidArgumentException("user too young");
        }

        // license
        $clubsPath = "assets/clubs.json";
        $fileContents = file_get_contents($clubsPath);
        if ($fileContents === false) {
            throw new \Exception("could not read $clubsPath");
        }
        $clubs = NULL;
        try {
            $clubs = json_decode($fileContents, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $_) {
            throw new \Exception(json_last_error_msg());
        }

        if (!in_array($license, $clubs, true) && $license === "NTNUI Triatlon" && $license !== "") {
            throw new \InvalidArgumentException("license club does not exists");
        }

        // save cin number to member if exists
        $cinId = NULL;
        try {
            $memberHash = self::calculateHash($birthDate, $gender, $phone);
            $cin = Cin::fromMemberHash($memberHash);
            $cin->touch();
            $cinId = $cin->id;
        } catch (CinNotFoundException $_) {
            // has probably not been a member before and cin has never been created for this user
        }
        $this->cinId = $cinId;

        // block registration unless enrollment is open.
        if (!enrollmentIsOpen(Settings::getInstance()->getEnrollment())) {
            throw new \InvalidArgumentException("enrollment is currently closed");
        }

        $this->$name = $name;
        $this->gender = $gender;
        $this->birthDate = $birthDate;
        $this->phone = $phone;
        $this->email = $email;
        $this->address = $address;
        $this->zip = $zip;
        $this->license = $license;
        $this->$cinId = $cinId;
    }

    public static function fromPhone(PhoneNumber $phone): self
    {
        $db = new DB();
        $db->prepare("SELECT * FROM members where phone=?");

        $phoneString = PhoneNumberUtil::getInstance()->format($phone, PhoneNumberFormat::E164);
        $db->bindParam("s", $phoneString);
        $db->execute();
        $member = [];
        $member["id"] = 0;
        $member["name"] = "";
        $member["gender"] = "";
        $member["birthDate"] = "";
        $member["phone"] = "";
        $member["email"] = "";
        $member["address"] = "";
        $member["zip"] = 0;
        $member["license"] = "";
        $member["registrationDate"] = "";
        $member["approvedDate"] = "";
        $member["haveVolunteered"] = false;
        $member["licenseForwarded"] = false;
        $member["cinId"] = NULL;

        $db->bindResult(
            $member["id"],
            $member["name"],
            $member["gender"],
            $member["birthDate"],
            $member["phone"],
            $member["email"],
            $member["address"],
            $member["zip"],
            $member["license"],
            $member["registrationDate"],
            $member["approvedDate"],
            $member["haveVolunteered"],
            $member["licenseForwarded"],
            $member["cinId"],
        );
        if ($db->fetch() === false) {
            throw new MemberNotFoundException();
        }
        return new Member(
            id: $member["id"],
            name: $member["name"],
            birthDate: new DateTime($member["birthDate"], new DateTimeZone(self::TIME_ZONE)),
            phone: PhoneNumberUtil::getInstance()->parse($member["phone"]),
            gender: Gender::fromString($member["gender"]),
            email: $member["email"],
            address: $member["address"],
            zip: $member["zip"],
            license: $member["license"],
            registrationDate: new DateTime($member["registrationDate"], new DateTimeZone(self::TIME_ZONE)),
            approvedDate: new DateTime($member["approvedDate"], new DateTimeZone(self::TIME_ZONE)),
            haveVolunteered: $member["haveVolunteered"],
            licenseForwarded: $member["licenseForwarded"],
            cinId: $member["cinId"],
        );
    }

    public static function fromId(int $dbRowId): self
    {
        $db = new DB();
        $db->prepare("SELECT * FROM members WHERE id=?");
        $db->bindParam("i", $dbRowId);
        $db->execute();
        $member = [];
        
        $member["id"] = 0;
        $member["name"] = "";
        $member["gender"] = "";
        $member["birthDate"] = "";
        $member["phone"] = "";
        $member["email"] = "";
        $member["address"] = "";
        $member["zip"] = 0;
        $member["license"] = "";
        $member["registrationDate"] = "";
        $member["approvedDate"] = "";
        $member["haveVolunteered"] = false;
        $member["licenseForwarded"] = false;
        $member["cinId"] = NULL;

        $db->bindResult(
            $member["id"],
            $member["name"],
            $member["gender"],
            $member["birthDate"],
            $member["phone"],
            $member["email"],
            $member["address"],
            $member["zip"],
            $member["license"],
            $member["registrationDate"],
            $member["approvedDate"],
            $member["haveVolunteered"],
            $member["licenseForwarded"],
            $member["cinId"],
        );
        if ($db->fetch() === false) {
            throw new MemberNotFoundException();
        }
        return new Member(
            name: $member["name"],
            birthDate: new DateTime($member["birthDate"], new DateTimeZone(self::TIME_ZONE)),
            phone: PhoneNumberUtil::getInstance()->parse($member["phone"]),
            gender: Gender::fromString($member["gender"]),
            email: $member["email"],
            address: $member["address"],
            zip: $member["zip"],
            license: $member["license"],
            registrationDate: new DateTime($member["registrationDate"], new DateTimeZone(self::TIME_ZONE)),
            approvedDate: new DateTime($member["approvedDate"], new DateTimeZone(self::TIME_ZONE)),
            haveVolunteered: $member["haveVolunteered"],
            licenseForwarded: $member["licenseForwarded"],
            cinId: $member["cinId"],
            id: $member["id"],
        );
    }

    public static function new(
        string $name,
        DateTime $birthDate,
        PhoneNumber $phone,
        Gender $gender,
        string $email,
        string $address,
        int $zip,
        ?string $license
    ): self {
        $member = new Member(
            $name,
            $birthDate,
            $phone,
            $gender,
            $email,
            $address,
            $zip,
            $license,
        );
        $db = new DB();
        $sql = "INSERT INTO members (name, gender, birthDate, phone, email, address, zip, license, registrationDate) VALUES (?,?,?,?,?,?,?,?,?,NOW())";
        $db->prepare($sql);
        $birthDate = $birthDate->format(self::DATE_FORMAT);
        $gender = $gender->toString();
        $phoneNumber = PhoneNumberUtil::getInstance()->format($phone, PhonenumberFormat::E164);
        $db->bindParam(
            "sssssssis",
            $name,
            $gender,
            $birthDate,
            $phoneNumber,
            $email,
            $address,
            $zip,
            $license
        );
        $db->execute();
        $member->id = $db->insertedId();
        return $member;
    }

    #endregion

    #region getters
    public function toArray(): array
    {
        return self::getByIdAsArray($this->id);
    }

    private function getHash(): Hash
    {
        return self::calculateHash($this->birthDate, $this->gender, $this->phone);
    }

    public function isMember(): bool
    {
        return isset($this->approvedDate);
    }

    #endregion

    #region setters 
    public function approveEnrollment(): void
    {
        $this->setMembershipActive();
        $this->sendApprovalEmail();
    }

    public function setLicenseForwarded(): void
    {
        if (isset($this->licenseForwarded)) {
            throw new \InvalidArgumentException("license has already been forwarded for this user. Call an admin");
        }
        if (!isset($this->cinId)) {
            throw new \InvalidArgumentException("cinId is not set");
        }

        // set approved date in db
        $db = new DB();
        $sqlUpdate = <<<'SQL'
        UPDATE members SET licenseForwarded=1 WHERE phone=?;
        SQL;
        $db->prepare($sqlUpdate);
        $phoneNumber = PhoneNumberUtil::getInstance()->format($this->phone, PhonenumberFormat::E164);
        $db->bindParam("s", $phoneNumber);
        $db->execute();

        $this->licenseForwarded = true;
    }

    public function setVolunteering(bool $state): void
    {
        // set approved date in db
        $db = new DB();
        $sqlUpdate = <<<'SQL'
        UPDATE members SET volunteering=? WHERE phone=?;
        SQL;
        $db->prepare($sqlUpdate);
        $state = (int)$state;
        $db->bindParam("i", $state);
        $db->execute();

        $this->haveVolunteered = $state;
    }

    public function setCin(int $cin): void
    {
        if (isset($this->cinId)) {
            Cin::fromId($this->cinId)->updateCin($cin); // might throw on duplication error
        } else {
            Cin::new($cin, $this->getHash()); // might throw on duplication error
        }
    }

    #endregion

    #region handlers

    public static function enrollmentApproveHandler(int $memberId): array
    {
        self::fromId($memberId)->approveEnrollment();
        return [
            "success" => true,
            "error" => false,
            "message" => "member approved successfully"
        ];
    }

    public static function licenseHandler(int $memberId): array
    {
        self::fromId($memberId)->setLicenseForwarded();
        return [
            "success" => true,
            "error" => false,
            "message" => "license forwarded has been set",
        ];
    }

    public static function exists(PhoneNumber $phone): bool
    {
        $db = new DB();
        $sql = "SELECT COUNT(*) AS count FROM members WHERE phone=? GROUP BY approvedDate";
        $db->prepare($sql);
        $phoneNumber = \libphonenumber\PhoneNumberUtil::getInstance()->format($phone, PhonenumberFormat::E164);
        $db->bindParam("s", $phoneNumber);
        $db->execute();
        $result = 0;
        $db->bindResult($result);
        $db->fetch();
        return (bool)$result;
    }

    public function patchHandler(array $jsonObject): array
    {
        $allowedPatches = ["volunteering", "cin"];
        foreach ($jsonObject as $key) {
            if (!in_array($key, $allowedPatches)) {
                throw new \InvalidArgumentException("$key is not allowed");
            }
        }
        if (array_key_exists("volunteering", $jsonObject)) {
            $this->setVolunteering((bool)filter_var($jsonObject["volunteering"], FILTER_VALIDATE_BOOLEAN));
        }
        if (array_key_exists("cin", $jsonObject)) {
            Cin::fromMemberHash($this->getHash())->updateCin($jsonObject["cin"]);
        }
        return [
            "success" => true,
            "error" => false,
            "message" => "member successfully patched",
        ];
    }
    public static function getAllAsArray(): array
    {
        $sql = "SELECT * FROM members";
        $members = self::fetchArray($sql);
        foreach ($members as &$member) {
            $member["licenseForwarded"] = (bool)$member["licenseForwarded"];
        }
        if (count($members) === 0) {
            throw new MemberNotFoundException();
        }
        return $members;
    }

    public static function enroll(array $jsonRequest): array
    {
        $missing_keys = [];
        foreach (["name", "birthDate", "phone", "gender", "email", "address", "zip"] as $key) {
            if ($jsonRequest[$key] === NULL) {
                $missing_keys[] = $key;
            }
        }
        if (count($missing_keys) > 0) {
            throw new \InvalidArgumentException("Following input are missing: [" . implode(", ", $missing_keys) . "]");
        }
        // DateTime does not throw exceptions when failing to create a DateTime object.
        // in stead it returns boolean false which triggers a TypeError since Member::new expects a DateTime object
        // consider moving to Moment library which will throw appropriate exception.
        Member::new(
            name: $jsonRequest["name"],
            birthDate: DateTime::createFromFormat(self::DATE_FORMAT, $jsonRequest["birthDate"], new DateTimeZone(self::TIME_ZONE)),
            phone: PhoneNumberUtil::getInstance()->parse($jsonRequest["phone"]),
            gender: Gender::fromString($jsonRequest["gender"]),
            email: $jsonRequest["email"],
            address: $jsonRequest["address"],
            zip: (int)$jsonRequest["zip"],
            license: $jsonRequest["license"],
        );
        return [
            "success" => true,
            "error" => false,
            "message" => "membership registration successfully"
        ];
    }

    public static function getAllActiveAsArray(): array
    {
        $sql = "SELECT * FROM members WHERE approvedDate IS NOT NULL";
        $members = self::fetchArray($sql);
        if (count($members) < 1) {
            throw new MemberNotFoundException();
        }
        return $members;
    }

    public static function getAllInactiveAsArray(): array
    {
        $sql = "SELECT * FROM members WHERE approvedDate IS NULL";
        $members = self::fetchArray($sql);
        if (count($members) < 1) {
            throw new MemberNotFoundException();
        }
        return $members;
    }

    #endregion

    #region private

    private static function getFirstName(string $name): string
    {
        return substr($name, 0, (strlen($name) - strpos(strrev($name), " ") - 1));
    }

    private static function getSurname(string $name): string
    {
        return substr($name, strlen($name) - strpos(strrev($name), " "));
    }

    private function getAge(DateTime $birthDate): int
    {
        $now = new DateTime();
        $interval = $now->diff($birthDate);
        return $interval->y;
    }

    // maybe move to Cin.php
    private static function calculateHash(DateTime $birthDate, Gender $gender, PhoneNumber $phone): Hash
    {
        $phoneString = PhoneNumberUtil::getInstance()->format($phone, PhoneNumberFormat::E164);
        return new Hash($birthDate["birthDate"]->format(self::DATE_FORMAT) . $phoneString . $gender->toString());
    }

    private static function trimSpace(string $text): string
    {
        $text = trim($text);
        while (true) {
            $result = str_replace("  ", " ", $text);
            if ($result === $text) {
                return $text;
            } else {
                $text = $result;
            }
        }
    }

    private function setMembershipActive(): void
    {
        if (isset($this->approvedDate)) {
            throw new Exception("member is already approved");
        }

        // set approved date in db
        $db = new DB();
        $sqlUpdate = <<<'SQL'
        UPDATE members SET approvedDate=NOW() WHERE id=?;
        SQL;
        $db->prepare($sqlUpdate);
        $db->bindParam("i", $this->id);
        $db->execute();

        // this might cause data desync between this object and whatever is stored in db
        $this->approvedDate = new DateTime();
    }

    private static function fetchArray(string $sql, ?string $bindTypes = NULL, mixed &$var1 = NULL, ?array &...$vars): array
    {

        $db = new DB();
        $db->prepare($sql);
        if (isset($bindTypes)) {
            $db->bindParam($bindTypes, $var1, $args);
        }
        $db->execute();
        $members = [];
        $member = [];
        $member["id"] = NULL;
        $member["name"] = NULL;
        $member["gender"] = NULL;
        $member["birthDate"] = NULL;
        $member["phone"] = NULL;
        $member["email"] = NULL;
        $member["address"] = NULL;
        $member["zip"] = NULL;
        $member["license"] = NULL;
        $member["registrationDate"] = NULL;
        $member["approvedDate"] = NULL;
        $member["haveVolunteered"] = NULL;
        $member["licenseForwarded"] = NULL;
        $member["cinId"] = NULL;
        $db->bindResult(
            $member["id"],
            $member["name"],
            $member["gender"],
            $member["birthDate"],
            $member["phone"],
            $member["email"],
            $member["address"],
            $member["zip"],
            $member["license"],
            $member["registrationDate"],
            $member["approvedDate"],
            $member["haveVolunteered"],
            $member["licenseForwarded"],
            $member["cinId"],
        );
        while ($db->fetch()) {
            array_push($members, $member);
        }
        return $members;
    }



    private static function isActive(PhoneNumber $phone): bool
    {
        $db = new DB();
        $sql = "SELECT approvedDate FROM members WHERE phone=?";
        $db->prepare($sql);
        $phoneNumber = \libphonenumber\PhoneNumberUtil::getInstance()->format($phone, PhonenumberFormat::E164);
        $db->bindParam("s", $phoneNumber);
        $db->execute();
        $approvedDate = NULL;
        $db->bindResult($approvedDate);
        if (!$db->fetch()) {
            throw new MemberNotFoundException();
        }
        return $approvedDate ?? false;
    }

    private static function getByIdAsArray(int $memberId): array
    {
        $sql = "SELECT * FROM members WHERE id=?";
        $members = self::fetchArray($sql, "i", $memberId);
        if (count($members) < 1) {
            throw new MemberNotFoundException();
        }
        return $members;
    }

    private function sendApprovalEmail(): void
    {
        $subject = "NTNUI Swimming - membership approved";
        $from = Settings::getInstance()->getEmailAddress("bot");
        $accountable = "svomming-medlem@ntnui.no"; // TODO: get from settings

        $headers = <<<HEADERS
        MIME-Version: 1.0
        Content-type: text/html; charset=utf-8
        From: NTNUI Swimming <"$from">
        Reply-to: NTNUI Swimming Membership Accountable <$accountable>
        HEADERS;

        $bodyEmail = <<<'HTML'
        Confirmation of registration
    
        Congratulations, you have now a valid membership in NTNUI Swimming!
        All information about membership in NTNUI swimming is available through <a href='https://ntnui.slab.com/posts/welcome-to-ntnui-swimming-%F0%9F%92%A6-44w4p9pv'>this</a> link. Add it to book marks.

        Love from NTNUI Swimming
        
        HTML;

        // TODO: add mail service to devcontainer
        @mail($this->email, $subject, nl2br($bodyEmail), str_replace("\n", "\r\n", $headers));
    }
    #endregion

}
