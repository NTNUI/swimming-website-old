<?php
global $t;
$title = $t->get_translation("page_title");
if ($title == "") $title = $t->get_translation($frm_side);
if ($title == "") $title = ucwords($frm_side);
?>
<!DOCTYPE html>
<html>	<div class="textboks">
	
