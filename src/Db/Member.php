<?php

declare(strict_types=1);

namespace NTNUI\Swimming\Db;

use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use NTNUI\Swimming\App\Settings;
use NTNUI\Swimming\Db\Cin;
use NTNUI\Swimming\Enum\Gender;
use NTNUI\Swimming\Exception\Api\ApiException;
use NTNUI\Swimming\Exception\Api\CinException;
use NTNUI\Swimming\Exception\Api\EnrollmentException;
use NTNUI\Swimming\Exception\Api\MemberException;
use NTNUI\Swimming\Exception\Api\UserException;
use NTNUI\Swimming\Util\Hash;
use Webmozart\Assert\Assert;

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
 */
class Member
{
    public const DATE_FORMAT = "Y-m-d"; // https://www.php.net/manual/en/datetime.format.php
    public const TIME_ZONE = "Europe/Oslo";
    public const FILTER_OPTIONS_ZIP = [
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
     * @param \DateTimeImmutable $birthDate
     * @param PhoneNumber $phone
     * @param Gender $gender
     * @param string $email
     * @param string $address
     * @param int $zip
     * @param ?string $license
     * @param \DateTimeImmutable $registrationDate
     * @param ?\DateTimeImmutable $approvedDate
     * @param bool $haveVolunteered
     * @param bool $licenseForwarded
     * @param ?int $cinId database cin row id
     * @param ?int $id database member row id
     *
     * @throws \MemberException on registration error
     * @throws \Exception on unrecoverable error
     *
     */
    private function __construct(
        private string $name,
        private \DateTimeImmutable $birthDate,
        private PhoneNumber $phone,
        private Gender $gender,
        private string $email,
        private string $address,
        private int $zip,
        private ?string $license,
        private \DateTimeImmutable $registrationDate = new \DateTimeImmutable('now', new \DateTimeZone(self::TIME_ZONE)),
        private ?\DateTimeImmutable $approvedDate = null,
        private bool $haveVolunteered = false,
        private bool $licenseForwarded = false,
        private ?int $cinId = null,
        private ?int $id = null
    ) {
        try {
            if (self::exists($phone)) {
                if (self::isActive($phone)) {
                    throw MemberException::memberIsActive();
                }
                throw new \Exception("user has registered but is not active yet"); // TODO: create new exception
            }
        } catch (UserException $_) {
        }

        // truncate strings
        $name = htmlspecialchars(self::trimSpace($name));
        $email = htmlspecialchars(self::trimSpace($email));
        $address = htmlspecialchars(self::trimSpace($address));
        $license = htmlspecialchars(self::trimSpace($license));

        // validate ints
        $zip = filter_var($zip, FILTER_VALIDATE_INT, self::FILTER_OPTIONS_ZIP);
        $email = filter_var($email, FILTER_VALIDATE_EMAIL, FILTER_NULL_ON_FAILURE);

        // validate strings
        if (strlen(self::getFirstName($name)) < 2) {
            throw MemberException::personalInformationInvalid("name too short");
        }
        if (strlen(self::getSurname($name)) < 3) {
            throw MemberException::personalInformationInvalid("surname too short");
        }
        if (strlen($address) < 6) {
            throw MemberException::personalInformationInvalid("address too short");
        }
        if (strlen($name) > 40) {
            throw MemberException::personalInformationInvalid("name too long");
        }
        // Age validation
        if (self::getAge($birthDate) < 18) {
            throw MemberException::personalInformationInvalid("user too young");
        }

        // license
        $CLUBS_PATH = __DIR__ . "/../assets/clubs.json";
        $fileContents = file_get_contents($CLUBS_PATH);
        if ($fileContents === false) {
            throw new \Exception("could not read $CLUBS_PATH");
        }
        $clubs = null;
        try {
            $clubs = json_decode($fileContents, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $_) {
            throw new \Exception(json_last_error_msg());
        }

        if (!in_array($license, $clubs, true) && $license === "NTNUI Triatlon" && $license !== "") {
            throw ApiException::invalidRequest("license club does not exists");
        }

        // save cin number to member if exists
        $cinId = null;
        try {
            $memberHash = self::calculateHash($birthDate, $gender, $phone);
            $cin = Cin::fromMemberHash($memberHash);
            $cin->touch();
            $cinId = $cin->id;
        } catch (CinException) { // CinNotFound
            // has probably not been a member before and cin has never been created for this user
        }
        $this->cinId = $cinId;

        // block registration unless enrollment is open.
        if (!\NTNUI\Swimming\Util\enrollmentIsOpen(Settings::getInstance()->getEnrollment())) {
            throw EnrollmentException::closed();
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
        $member["cinId"] = null;

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
            throw MemberException::memberNotFound();
        }
        return new Member(
            id: $member["id"],
            name: $member["name"],
            birthDate: new \DateTimeImmutable($member["birthDate"], new \DateTimeZone(self::TIME_ZONE)),
            phone: PhoneNumberUtil::getInstance()->parse($member["phone"]),
            gender: Gender::fromString($member["gender"]),
            email: $member["email"],
            address: $member["address"],
            zip: $member["zip"],
            license: $member["license"],
            registrationDate: new \DateTimeImmutable($member["registrationDate"], new \DateTimeZone(self::TIME_ZONE)),
            approvedDate: new \DateTimeImmutable($member["approvedDate"], new \DateTimeZone(self::TIME_ZONE)),
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
        $member["cinId"] = null;

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
            throw MemberException::memberNotFound();
        }
        return new Member(
            id: $member["id"],
            name: $member["name"],
            birthDate: new \DateTimeImmutable($member["birthDate"], new \DateTimeZone(self::TIME_ZONE)),
            phone: PhoneNumberUtil::getInstance()->parse($member["phone"]),
            gender: Gender::fromString($member["gender"]),
            email: $member["email"],
            address: $member["address"],
            zip: $member["zip"],
            license: $member["license"],
            registrationDate: new \DateTimeImmutable($member["registrationDate"], new \DateTimeZone(self::TIME_ZONE)),
            approvedDate: new \DateTimeImmutable($member["approvedDate"], new \DateTimeZone(self::TIME_ZONE)),
            haveVolunteered: $member["haveVolunteered"],
            licenseForwarded: $member["licenseForwarded"],
            cinId: $member["cinId"],
        );
    }

    public static function new(
        string $name,
        \DateTimeImmutable $birthDate,
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

    /**
     * toArray
     *
     * @return array{
     * id:int,
     * name:string,
     * gender:string,
     * birthDate:int,
     * phone:string,
     * email:string,
     * address:string,
     * zip:int,
     * license:?string,
     * registrationDate:int,
     * approvedDate:?int,
     * haveVolunteered:bool,
     * licenseForwarded:bool,
     * cinId:?int
     * }}
     */
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
            // TODO: CinException::alreadyForwarded();
        }
        if (!isset($this->cinId)) {
            throw new \InvalidArgumentException("this member does not have a customer identification number");
            // TODO: CinException::missingCin();
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

    /**
     * enrollmentApproveHandler
     *
     * @param integer $memberId
     * @return array{success:true, error:false, message:string}
     */
    public static function enrollmentApproveHandler(int $memberId): array
    {
        self::fromId($memberId)->approveEnrollment();
        return [
            "success" => true,
            "error" => false,
            "message" => "member approved successfully"
        ];
    }

    /**
     * licenseHandler
     *
     * @param integer $memberId
     * @return array{success:true, error:false, message:string}
     */
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

    /**
     * patchHandler
     *
     * @param array $jsonObject
     * @return array{success:true,error:false,message:string}
     */
    public function patchHandler(array $jsonObject): array
    {
        $allowedPatches = ["volunteering", "cin"];
        foreach ($jsonObject as $key) {
            if (!in_array($key, $allowedPatches)) {
                // TODO: ApiException::badRequest("$key is not allowed");
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

    /**
     * getAllAsArray
     *
     * @return array{int,array{
     * id: int,
     * name: string,
     * gender: string,
     * birthDate: int,
     * phone: string,
     * email: string,
     * address: string,
     * zip: int,
     * license: ?string,
     * registrationDate: int,
     * approvedDate: ?int,
     * haveVolunteered: bool,
     * licenseForwarded: bool,
     * cinId: ?int
     * }}
     */
    public static function getAllAsArray(): array
    {
        $sql = "SELECT * FROM members";
        return self::fetchArray($sql);
    }

    /**
     * enroll
     *
     * @param array $jsonRequest
     * @return array{success:true,error:false,message:string}
     */
    public static function enroll(array $jsonRequest): array
    {
        $missing_keys = [];
        foreach (["name", "birthDate", "phone", "gender", "email", "address", "zip"] as $key) {
            if ($jsonRequest[$key] === null) {
                $missing_keys[] = $key;
            }
        }
        if (count($missing_keys) > 0) {
            // TODO: ApiException::missingArgument("Following inputs...");
            throw new \InvalidArgumentException("Following input are missing: [" . implode(", ", $missing_keys) . "]");
        }
        // DateTime does not throw exceptions when failing to create a DateTime object.
        // in stead it returns boolean false which triggers a TypeError since Member::new expects a DateTime object
        // consider moving to Moment library which will throw appropriate exception.
        Member::new(
            name: $jsonRequest["name"],
            birthDate: \DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $jsonRequest["birthDate"], new \DateTimeZone(self::TIME_ZONE)),
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

    /**
     * getAllActiveAsArray
     *
     * @return array{int,array{
     * id: int,
     * name: string,
     * gender: string,
     * birthDate: int,
     * phone: string,
     * email: string,
     * address: string,
     * zip: int,
     * license: ?string,
     * registrationDate: int,
     * approvedDate: ?int,
     * haveVolunteered: bool,
     * licenseForwarded: bool,
     * cinId: ?int
     * }}
     */
    public static function getAllActiveAsArray(): array
    {
        $sql = "SELECT * FROM members WHERE approvedDate IS NOT NULL";
        return self::fetchArray($sql);
    }

    /**
     * getAllInactiveAsArray
     *
     * @return array{int,array{
     * id: int,
     * name: string,
     * gender: string,
     * birthDate: int,
     * phone: string,
     * email: string,
     * address: string,
     * zip: int,
     * license: ?string,
     * registrationDate: int,
     * approvedDate: ?int,
     * haveVolunteered: bool,
     * licenseForwarded: bool,
     * cinId: ?int
     * }}
     */
    public static function getAllInactiveAsArray(): array
    {
        $sql = "SELECT * FROM members WHERE approvedDate IS NULL";
        return self::fetchArray($sql);
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

    private function getAge(\DateTimeImmutable $birthDate): int
    {
        $now = new \DateTimeImmutable();
        $interval = $now->diff($birthDate);
        return $interval->y;
    }

    // maybe move to Cin.php
    private static function calculateHash(\DateTimeInterface $birthDate, Gender $gender, PhoneNumber $phone): Hash
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
            // TODO: throw MemberException::memberIsAlreadyApproved();
            throw new \Exception("member is already approved");
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
        $this->approvedDate = new \DateTime();
    }

    /**
     * Wrapper for db select queries. Used to fetch data from db and forward directly to clients. Will convert all dates to unix timestamp.
     *
     * TODO: consider replacing cinId with cin
     * @param string $sql query to execute
     * @param ?string $bindTypes types of arguments
     * @param mixed $var1
     * @param ?array ...$vars
     * @return array{int,array{id:int,name:string,gender:string,birthDate:int,phone:string,email:string,address:string,zip:int,license:?string,registrationDate:int,approvedDate:?int,haveVolunteered:bool,licenseForwarded:bool,cinId:?int}}
     */
    private static function fetchArray(string $sql, ?string $bindTypes = null, mixed &$var1 = null, ?array &...$vars): array
    {
        $db = new DB();
        $db->prepare($sql);
        if (isset($bindTypes)) {
            $db->bindParam($bindTypes, $var1, ...$vars);
        }
        $members = [];
        $member = [];

        $member["id"] = 0;
        $member["name"] = "";
        $member["gender"] = "";
        $member["birthDate"] = "";
        $member["phone"] = "";
        $member["email"] = "";
        $member["address"] = "";
        $member["zip"] = 0;
        $member["license"] = null;
        $member["registrationDate"] = "";
        $member["approvedDate"] = null;
        $member["haveVolunteered"] = false;
        $member["licenseForwarded"] = false;
        $member["cinId"] = null;

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
        $db->execute();
        $dates = ["birthDate", "registrationDate", "approvedDate"];

        while ($db->fetch()) {
            // convert all valid dates to unix timestamp
            foreach ($dates as $date) {
                if (isset($member[$date])) {
                    $member[$date] = \DateTime::createFromFormat(self::DATE_FORMAT, $member[$date], new \DateTimeZone(self::TIME_ZONE))->getTimestamp();
                }
                Assert::nullOrInteger($member[$date]);
            }
            $member["licenseForwarded"] = (bool)$member["licenseForwarded"];
            array_push($members, $member);
        }
        if (count($members) === 0) {
            throw MemberException::memberNotFound();
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
        $approvedDate = null;
        $db->bindResult($approvedDate);
        if (!$db->fetch()) {
            throw MemberException::memberNotFound();
        }
        return $approvedDate ?? false;
    }

    /**
     * return one member as array
     *
     * @param integer $memberId
     * @return array{id:int,name:string,gender:string,birthDate:int,phone:string,email:string,address:string,zip:int,license:?string,registrationDate:int,approvedDate:?int,haveVolunteered:bool,licenseForwarded:bool,cinId:?int}
     */
    private static function getByIdAsArray(int $memberId): array
    {
        $sql = "SELECT * FROM members WHERE id=?";
        return self::fetchArray($sql, "i", $memberId)[1];
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
