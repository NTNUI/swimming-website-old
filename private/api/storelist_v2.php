<?php
include_once("library/util/store_helper_v2.php");
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
