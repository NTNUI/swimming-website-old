<?php

declare(strict_types=1);

use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;

class Customer
{
    public function __construct(
        public readonly string $fullName,
        public readonly string $email,
        public readonly ?PhoneNumber $phone = NULL
    ) {
    }

    public function getPhoneAsString(): string
    {
        return PhoneNumberUtil::getInstance()->format($this->phone, PhoneNumberFormat::E164);
    }
}
