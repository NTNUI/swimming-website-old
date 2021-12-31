<?php
global $settings;
require_once("library/templates/modal.php");
/*
TODO:
- New feature: onChange() -> validate. if !valid set color as red, else normal.
- New feature: dump database
- New feature: show hidden users (those with a KID number in database)
*/
?>

<link rel="stylesheet" href="<?php print $settings['baseurl']; ?>/css/admin/kid.css">
<script type="module" src="<?php print $settings['baseurl']; ?>/js/admin/kid.js"> </script>

<div class="box">
	<h2>NSF Customer identification number registration (CIN)</h2>
	<p>View, modify members CIN properties</p>
</div>

<div class="box">

	<table id="members" class="max-width">
		<tr>
			<th scope="col" class="header-name">Name</th>
			<th scope="col" class="header-Email">E-mail</th>
			<th scope="col" class="header-phone">Phone</th>
			<th scope="col" class="header-CIN">CIN</th>
			<th scope="col" class="header-actions">Actions</th>
		</tr>

	</table>

	<template id="member">
		<tr>
			<td class="name"></td>
			<td class="email"></td>
			<td class="phone"></td>
			<td class="CIN"><input type="number" pattern="/^\d{8}$/gm"></input></td>
			<td class="actions"><button class="save">Save</button></td>
		</tr>
	</template>


</div>