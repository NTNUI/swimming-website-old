<?php

include_once("library/util/store_helper.php");
$store = new StoreHelper($language);
$source = $_REQUEST["source"]; 

if ($source != "") print $store->get_status($source);


