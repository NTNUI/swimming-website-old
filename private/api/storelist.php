<?php
/*
$res = array(
	array(
		"title" => "test vanlig",
		"price" => 100,
		"id" => "aabbaa",
		"description" => "Here comes description",
		"amount" => 100,
		"amount_bought" => 5,
		"available_from" => strtotime("01-01-2018 16:00:00"),
		"available_until" => strtotime("01-01-2020 23:59:59"),
	       	"img" => "https://raw.githubusercontent.com/AarexTiaokhiao/IvarK.github.io/master/images/108.png"	
	),
	array(
		"title" => "test utsolgt",
		"price" => 100,
		"id" => "aabbcc",
		"description" => "Here comes description",
		"amount" => 1,
		"amount_bought" => 1,
		"available_from" => strtotime("01-01-2018 16:00:00"),
		"available_until" => strtotime("01-01-2020 23:59:59"),
		"img" => "https://raw.githubusercontent.com/AarexTiaokhiao/IvarK.github.io/master/images/108.png"
	), array(
		"title" => "test ikke åpen",
		"price" => 100,
		"id" => "aabbdd",
		"description" => "Here comes description",
		"amount" => 1,
		"amount_bought" => 0,
		"available_from" => strtotime("01-01-2019 16:00:00"),
		"available_until" => strtotime("01-01-2020 23:59:59"),
		"img" => "https://raw.githubusercontent.com/AarexTiaokhiao/IvarK.github.io/master/images/108.png"
	), array(
		"title" => "test timeout",
		"price" => 100,
		"id" => "aabbee",
		"description" => "Here comes description",
		"amount" => 1,
		"amount_bought" => 0,
		"available_from" => strtotime("01-01-2018 16:00:00"),
		"available_until" => strtotime("01-08-2018 23:59:59"),
		"img" => "https://raw.githubusercontent.com/AarexTiaokhiao/IvarK.github.io/master/images/108.png"
	)
) ;*/

include_once("library/util/store_helper.php");
$store = new StoreHelper($language);
$res = $store->get_items();

foreach ($res as $i => $item) {
	if (array_key_exists("image", $item)) {
		$res[$i]["image"] = "$base_url/img/store/" . $item["image"];
	}
	$res[$i]["id"] = $item["api_id"];
	unset($res[$i]["api_id"]);
}

header("Content-Type: application/json");
print json_encode($res);
