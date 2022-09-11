<?php

declare(strict_types=1);

namespace NTNUI\Swimming\Util;

use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class Customer
{
    public function __construct(
        public readonly string $fullName,
        public readonly string $email,
        public readonly ?PhoneNumber $phone = null
    ) {
    }

    public function getPhoneAsString(): string
    {
        return PhoneNumberUtil::getInstance()->format($this->phone, PhoneNumberFormat::E164);
    }
}
