<?php

declare(strict_types=1);

namespace NTNUI\Swimming\Util;

enum Language
{
    case ENGLISH;
    case NORWEGIAN;

    public function toString(): string
    {
        return match ($this) {
            self::ENGLISH => "en",
            self::NORWEGIAN => "no",
        };
    }

    public static function fromString(string $language): self
    {
        $language = strtolower($language);
        return match ($language) {
            "en" => self::ENGLISH,
            "no" => self::NORWEGIAN,
            default => throw new \InvalidArgumentException("that language is not supported"),
        };
    }

    public static function isLanguage(mixed $language): bool
    {
        return $language === "en" || $language === "no";
    }
}
