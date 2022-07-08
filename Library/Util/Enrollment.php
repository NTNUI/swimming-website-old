<?php

declare(strict_types=1);

function enrollment_is_active(array $enrollment_settings): bool
{
	if ($enrollment_settings["open"] !== "auto") {
		return $enrollment_settings["open"];
	}
	$start_month = $enrollment_settings["start_month"];
	$start_day = $enrollment_settings["start_day"];
	$end_month = $enrollment_settings["end_month"];
	$end_day = $enrollment_settings["end_day"];

	$date_format = "j F"; // day month
	$start_date = DateTime::createFromFormat($date_format, $start_day . " " . $start_month);
	$end_date = DateTime::createFromFormat($date_format, $end_day . " " . $end_month);

	if ($start_date === false || $end_date === false) {
		throw new Exception("Could not construct date time object");
	}
	$now = new DateTime("now");
	if ($now > $start_date && $now < $end_date) {
		return true;
	}

	return false;
}