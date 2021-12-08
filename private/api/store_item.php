<?php
// Get a specific product
// TODO: merge with api/store.php
header("Content-Type: application/json");
if($_SERVER["REQUEST_METHOD"] !== "GET"){
    http_response_code(400);
    return json_encode(["Only GET requests are allowed. For now."]);
}
if(!isset($_GET["product_hash"])){
    http_response_code(404);
    return json_encode([]);
}
log::message("Info: Requested product: " . $_GET["product_hash"], __FILE__, __LINE__);
include_once("library/util/store_helper.php");
$store = new StoreHelper($language);
http_response_code(200);
print json_encode($store->get_product($_GET["product_hash"]));