<?php
include_once("library/util/store_helper.php");
$store = new StoreHelper($language);
$res = $store->get_items();

foreach ($res as $i => $item) {
	if (array_key_exists("image", $item)) {
		$res[$i]["image"] = "$base_url/img/store/" . $item["image"];
	}
	$res[$i]["id"] = $item["item_hash"];
	unset($res[$i]["item_hash"]);
}

header("Content-Type: application/json");
print json_encode($res);
