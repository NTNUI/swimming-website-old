<?php

include_once("library/util/store.php");
$store = new Store($language);
$source = $_REQUEST["source"]; 

if ($source != "") print $store->get_status($source);


