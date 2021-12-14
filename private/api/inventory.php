<?php
// Get all products. Get the inventory
// TODO: merge with api/store.php
require_once("library/util/store.php");
$store = new Store($language);
global $settings;
header("Content-Type: application/json");
$products = $store->get_products(0, 30, "", false, false); // get 30 products
if ($products === false) {
	http_response_code(404);
	return;
}
foreach ($products as $i => $product) {
	if (array_key_exists("image", $product)) {
		$products[$i]["image"] = $settings["baseurl"] . "/img/store/" . $product["image"];
	}
	$products[$i]["hash"] = $product["hash"];
	unset($products[$i]["id"]);
}

print json_encode($products);
