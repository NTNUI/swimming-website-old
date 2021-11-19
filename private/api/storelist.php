<?php
include_once("library/util/store_helper.php");
$store = new StoreHelper($language);
$res = $store->get_items();

foreach ($res as $i => $item) {
	if (array_key_exists("image", $item)) {
		$res[$i]["image"] = "$base_url/img/store/" . $item["image"];
	}
	$res[$i]["store_item_hash"] = $item["store_item_hash"];
	unset($res[$i]["id"]);
}

header("Content-Type: application/json");
print json_encode($res);
