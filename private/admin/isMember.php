<?php
global $t, $settings;
$base_url = $settings['baseurl'];
include_once("library/templates/modal.php");
?>

<div class='box'>
	<h2>Member search</h2>
</div>

<div class='box'>

	<div>

		<input id='searchBox' type='text' placeholder='Surname'>
		<button id='searchButton' type='button'>Search</button>

		<div id='failureBox' class='box error' style='display: none;'>
			<p>No results</p>
		</div>
		<div id='successBox' class='box' style='display: none;'>
			<ul id='names'></ul>
		</div>
		<div id='searchingBox' class='box' style='display: none;'>
			<p>Searching...</p>
		</div>
	</div>

</div>
<script src='<?php print "$base_url/js/admin/isMember.js" ?>' type='module'></script>