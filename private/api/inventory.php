<?php
// Get all products. Get the inventory
// TODO: merge with api/store.php
include_once("library/util/store_helper.php");
$store = new StoreHelper($language);
header("Content-Type: application/json");
$products = $store->get_products();
if($products === false){
	http_response_code(404);
	return;
}
foreach ($products as $i => $product) {
	if (array_key_exists("image", $product)) {
		$products[$i]["image"] = "$base_url/img/store/" . $product["image"];
	}
	$products[$i]["hash"] = $product["hash"];
	unset($products[$i]["id"]);
}

print json_encode($products);
