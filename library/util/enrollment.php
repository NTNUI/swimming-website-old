<?php

function enrollment_is_active(): bool
{
	global $settings;
	if ($settings["enrollment"]["open"] !== "auto") {
		return $settings["enrollment"]["open"];
	}
	$start_month = $settings["enrollment"]["start_month"];
	$start_day = $settings["enrollment"]["start_day"];
	$end_month = $settings["enrollment"]["end_month"];
	$end_day = $settings["enrollment"]["end_day"];

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