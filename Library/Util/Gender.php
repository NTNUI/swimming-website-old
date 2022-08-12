<?php

declare(strict_types=1);

enum Gender: string
{
    case Male = "Male";
    case Female = "Female";
    // case ApacheAttackHelicopter = "Apache Attack Helicopter";

    public function toString(): string
    {
        return match ($this) {
            self::Male => "Male",
            self::Female => "Female",
            // self::ApacheAttackHelicopter => "Apache Attack Helicopter",
        };
    }

    public static function fromString(string $gender): self
    {
        return match (strtolower($gender)) {
            "male" => Gender::Male,
            "female" => Gender::Female,
                // "apache attack helicopter" => Gender::ApacheAttackHelicopter,

            default => throw new \InvalidArgumentException("gender object can only be created from 'Male' or 'Female'. Got: " . $gender),
            // default => throw new \InvalidArgumentException("gender object can only be created from 'Male', 'Female' or 'Apache Attack Helicopter'. Got: " . $gender),
        };
    }
};

