<style>

input[type=button] {
    width: 100%;
    background-color: #4CAF50;
    color: white;
    padding: 14px 20px;
    margin: 8px 0;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

input[type=button]:hover {
    background-color: #45a049;
}

</style>

<div class="box green">

<h1 align="center"><?php print $t->get_translation("mainHeader"); ?></h1>
</div>

<div class="box">

<div style="border-radius: 5px; /*background-color: #f2f2f2;*/ padding: 20px;">

	<label for="lname" style="line-height: 2"><?php print $t->get_translation("search_label"); ?></label>
	<input id="searchBox" type="text" placeholder="<?php print $t->get_translation("search_placeholder") ?>">
	<button id="searchButton" type="button"><?php print $t->get_translation("search_button"); ?></button>
	<h1><?php print $t->get_translation("result_header"); ?></h1>
	<div id="failureBox" class='box error' style='display: none;'>
		<p align='center'><?php print $t->get_translation("no_result"); ?></p>
	</div>
	<div id="successBox" class='box green' style="display: none;">
		<ul id="names"></ul>
	</div>
	<div id="searchingBox" class="box" style="display:none;">
		<h1><?php print $t->get_translation("searching"); ?></h1>
	</div>
</div>

</div>
<script type="text/javascript">var api_src = "<?php print "$base_url/api"?>";</script>
<script src="<?php print "$base_url/js/membercheck.js"?>" type="text/javascript"></script>
