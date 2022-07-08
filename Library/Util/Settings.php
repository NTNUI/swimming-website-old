<?php

declare(strict_types=1);

class Settings
{
    private array $emails;
    private array $enrollment_settings;
    private string $base_url;
    private string $landing_page;
    private string $language;
    private string $license_product_hash;
    private string $translation_directory;
    private const COOKIE_LIFETIME = 14400; // 4 hours
    private self $instance = NULL;


    /**
     * Create a new instance of Settings class. First call will run constructor
     * and require @param $config_path. Subsequent calls will not use @param
     * $config_path
     *
     * @param string|null $config_path path to `settings.json`. Required on first call.
     * @return self the instance of Settings class 
     */
    public static function get_instance(?string $config_path = NULL): self
    {
        if (empty(self::$instance)) {
            self::$instance = new self($config_path);
        }
        return self::$instance;
    }

    private function __construct(string $path)
    {
        if (!file_exists($path)) {
            throw new Exception("file $path does not exists");
        }

        $file_content = file_get_contents($path);
        if (!$file_content) {
            throw new Exception("could not read contents of $path");
        }

        $decoded = json_decode($file_content, true, flags: JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR);
        $REQUIRED_KEYS = [
            "emails",
            "baseurl",
            "translations_dir",
            "emails",
            "enrollment",
            "defaults",
            "license_product_hash",
        ];
        foreach ($REQUIRED_KEYS as $key) {
            if (!array_key_exists($key, $decoded)) {
                throw new Exception("key $key does not exists in settings");
            }
        }
        $REQUIRED_EMAIL_ROLES = [
            "analyst",
            "bot",
            "coach",
            "developer",
            "leader",
        ];
        if (0 !== strpos($decoded["baseurl"], "https://")) {
            throw new Exception("decoded[baseurl] does not start with 'https://'. This will break links. decoded[baseurl] contains: " . $decoded["baseurl"]);
        }
        $this->base_url = $decoded["baseurl"];

        $emails_array = $decoded["emails"];
        foreach ($REQUIRED_EMAIL_ROLES as $role) {
            if (!array_key_exists($role, $emails_array)) {
                throw new Exception("key $role does not exists in emails");
            }
            if (NULL === filter_var($emails_array[$role], FILTER_VALIDATE_EMAIL, FILTER_NULL_ON_FAILURE)) {
                throw new Exception("email " . $emails_array[$role] . " is not valid for role $role");
            }
        }
        $this->emails = $emails_array;

        $REQUIRED_DEFAULT_KEYS = [
            "landing-page",
            "language",
        ];
        foreach ($REQUIRED_DEFAULT_KEYS as $key) {
            if (!array_key_exists($key, $decoded["defaults"])) {
                throw new Exception("key $key does not exists in settings['defaults']");
            }
        }
        $this->language = $decoded["defaults"]["language"];
        $this->landing_page = $decoded["defaults"]["landing-page"];

        $this->enrollment_settings = $decoded["enrollment"];
        $this->license_product_hash = $decoded["license_product_hash"];
        $this->translation_directory = $decoded["translations_dir"];
    }

    public function init_session(): void
    {
        $err = session_save_path("sessions");
        if ($err === false) {
            throw new Exception("could not start session");
        }
        $err = session_set_cookie_params(["lifetime" => self::COOKIE_LIFETIME]);
        if ($err === false) {
            throw new Exception("could not start session");
        }
        $err = ini_set("session.gc_maxlifetime", (string)self::COOKIE_LIFETIME);
        if ($err === false) {
            throw new Exception("could not start session");
        }
        $err = ini_set("session.gc_probability", "1");
        if ($err === false) {
            throw new Exception("could not start session");
        }
        $err = ini_set("session.gc_divisor", "100");
        if ($err === false) {
            throw new Exception("could not start session");
        }
        $err = session_start();
        if ($err === false) {
            throw new Exception("could not start session");
        }
    }

    public function test_settings(): void
    {
        // test access
        foreach (["img/store", "/tmp", "sessions", "translations"] as $dir) {
            if (!is_writable($dir)) {
                throw new Exception("$dir is not writable");
            }
        }
        foreach (["css", "js", "Library", "Private", "Public", "vendor", "assets"] as $dir) {
            if (!is_readable($dir)) {
                throw new Exception("$dir is not readable");
            }
        }
    }
    public function get_language(): string
    {
        return $this->language;
    }
    public function get_landing_page(): string
    {
        return $this->landing_page;
    }
    public function get_baseurl(): string
    {
        return $this->base_url;
    }
    public function get_email_address(string $role): string
    {
        if (!array_key_exists($role, ["developer", "analyst", "coach", "bot", "leader"])) {
            throw new Exception("email does not exists");
        }
        return $this->emails[$role];
    }
    public function get_enrollment(): array
    {
        return $this->enrollment_settings;
    }
    public function get_license_product_hash(): string
    {
        return $this->license_product_hash;
    }
    public function get_translations_dir(): string
    {
        return $this->translation_directory;
    }
};
