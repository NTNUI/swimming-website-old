<?php
declare(strict_types=1);

require_once("library/util/store.php");
$store = new Store($language);
$source = $_REQUEST["source"]; 

if ($source != "") print $store->get_order_status($source);
