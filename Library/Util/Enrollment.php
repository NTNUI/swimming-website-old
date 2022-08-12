<?php

declare(strict_types=1);

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
	$startDate = DateTime::createFromFormat($dateFormat, $startDay . " " . $startMonth);
	$endDate = DateTime::createFromFormat($dateFormat, $endDay . " " . $endMonth);

	if ($startDate === false || $endDate === false) {
		throw new \Exception("Could not construct date time object");
	}
	$now = new DateTime("now");
	if ($now > $startDate && $now < $endDate) {
		return true;
	}

	return false;
}
