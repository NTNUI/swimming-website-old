<?php

declare(strict_types=1);

namespace NTNUI\Swimming\Util;

// TODO: convert into a config class
class Settings
{
    /**
     * associative array of emails
     *
     * @var array{leader: string, developer: string, analyst: string, coach: string, bot: string} $emails
     */
    private array $emails;

    /**
     * associative array with enrollment settings
     *
     * @var array{
     *  open: "auto",
     *  startMonth: string,
     *  startDay: int,
     *  endMonth: string,
     *  endDay: int
     * }|array{open: bool} $enrollmentSettings
     */
    private array $enrollmentSettings;
    private string $baseUrl;
    private string $licenseProductHash;
    private const COOKIE_LIFETIME = 14400; // 4 hours
    private static ?self $instance = null;
    public const REQUIRED_EMAIL_ROLES = [
        "analyst",
        "bot",
        "coach",
        "developer",
        "leader",
    ];
    /**
     * Create a new instance of Settings class. First call will run constructor
     * and require @param $config_path. Subsequent calls will not use @param
     * $config_path
     *
     * @param string|null $configPath path to `settings.json`. Required on first call.
     * @return self
     */
    public static function getInstance(?string $configPath = null): self
    {
        if (empty(self::$instance)) {
            self::$instance = new self($configPath);
        }
        return self::$instance;
    }

    private function __construct(string $path)
    {
        if (!file_exists($path)) {
            throw new \Exception("file $path does not exists");
        }

        $fileContent = file_get_contents($path);
        if (!$fileContent) {
            throw new \Exception("could not read contents of $path");
        }

        $decoded = json_decode($fileContent, true, flags: JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR);
        $REQUIRED_KEYS = [
            "emails",
            "enrollment",
        ];
        foreach ($REQUIRED_KEYS as $key) {
            if (!array_key_exists($key, $decoded)) {
                throw new \Exception("key $key does not exists in settings");
            }
        }

        $this->licenseProductHash = $_ENV["LICENSE_PRODUCT_HASH"];
        $this->baseUrl = $_ENV["BASE_URL"];
        if (0 !== strpos($this->baseUrl, "https://")) {
            throw new \Exception("environment variable BASE_URL does not start with 'https://'. This will break links");
        }

        $emailsArray = $decoded["emails"];
        foreach (self::REQUIRED_EMAIL_ROLES as $role) {
            if (!array_key_exists($role, $emailsArray)) {
                throw new \Exception("key $role does not exists in emails");
            }
            if (null === filter_var($emailsArray[$role], FILTER_VALIDATE_EMAIL, FILTER_NULL_ON_FAILURE)) {
                throw new \Exception("email " . $emailsArray[$role] . " is not valid for role $role");
            }
        }
        /** @var array{leader:string,developer:string,analyst:string,coach:string,bot:string} $emailsArray */
        $this->emails = $emailsArray;

        $this->enrollmentSettings = $decoded["enrollment"];
    }

    public function initSession(): void
    {
        $err = session_save_path(__DIR__ . "/../../runtimeData/sessions");
        if ($err === false) {
            throw new \Exception("could not start session");
        }
        $err = session_set_cookie_params(["lifetime" => self::COOKIE_LIFETIME]);
        if ($err === false) {
            throw new \Exception("could not start session");
        }
        $err = ini_set("session.gc_maxlifetime", (string)self::COOKIE_LIFETIME);
        if ($err === false) {
            throw new \Exception("could not start session");
        }
        $err = ini_set("session.gc_probability", "1");
        if ($err === false) {
            throw new \Exception("could not start session");
        }
        $err = ini_set("session.gc_divisor", "100");
        if ($err === false) {
            throw new \Exception("could not start session");
        }
        $err = session_start();
        if ($err === false) {
            throw new \Exception("could not start session");
        }
    }

    public function sessionDestroy(): void
    {
        if (!session_unset()) {
            throw new \Exception("could not unset session");
        }
        if (!session_destroy()) {
            throw new \Exception("could not destroy session");
        }
    }

    public function testSettings(): void
    {
        // test access
        foreach (["/tmp", __DIR__ . "/../../runtimeData/sessions", __DIR__ . "/../../runtimeData/img"] as $dir) {
            if (!is_writable($dir)) {
                throw new \Exception("$dir is not writable");
            }
        }
        foreach ([__DIR__ . "/../assets", __DIR__ . "/../../"] as $dir) {
            if (!is_readable($dir)) {
                throw new \Exception("$dir is not readable");
            }
        }
    }

    public function getBaseurl(): string
    {
        return $this->baseUrl;
    }

    public function getEmailAddress(string $role): string
    {
        if (!in_array($role, self::REQUIRED_EMAIL_ROLES)) {
            throw new \Exception("$role email does not exists");
        }
        return $this->emails[$role];
    }

    /**
     * get enrollment settings
     *
     * @return array{
     *  open: "auto",
     *  startMonth: string,
     *  startDay: int,
     *  endMonth: string,
     *  endDay: int
     * }|array{open: bool}
     */
    public function getEnrollment(): array
    {
        return $this->enrollmentSettings;
    }

    public function getLicenseProductHash(): string
    {
        return $this->licenseProductHash;
    }
};
