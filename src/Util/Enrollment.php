<?php

declare(strict_types=1);

namespace NTNUI\Swimming\Util;

use Webmozart\Assert\Assert;

/**
 * Check if new members can enroll
 *
 * @param array{
 *  open: "auto",
 *  startMonth: string,
 *  startDay: int,
 *  endMonth: string,
 *  endDay: int
 * }|array{open: bool} $enrollmentSettings
 * @return bool
 */
function enrollmentIsOpen(array $enrollmentSettings): bool
{
    if ($enrollmentSettings["open"] !== "auto") {
        return $enrollmentSettings["open"];
    }
    $startMonth = $enrollmentSettings["startMonth"];
    $startDay = $enrollmentSettings["startDay"];
    $endMonth = $enrollmentSettings["endMonth"];
    $endDay = $enrollmentSettings["endDay"];

    $dateFormat = "j F"; // day month
    $startDate = \DateTime::createFromFormat($dateFormat, $startDay . " " . $startMonth);
    $endDate = \DateTime::createFromFormat($dateFormat, $endDay . " " . $endMonth);

    // TODO: read and test enrollment settings in settings class. Test function needs to be run only once to check if configuration is ok.
    Assert::notFalse($startDate);
    Assert::notFalse($endDate);
    $now = new \DateTime("now");
    Assert::notFalse($now);
    if ($now > $startDate && $now < $endDate) {
        return true;
    }

    return false;
}
