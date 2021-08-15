<?php
global $settings;
/*
TODO:
- New feature: onChange() -> validate. if !valid set color as red, else normal.
- New feature: dump database
- New feature: show hidden users (those with a KID number in database)
*/
?>

<link rel="stylesheet" href="<?php print $settings['baseurl']; ?>/css/admin/kid.css">
<script src="<?php print $settings['baseurl']; ?>/js/admin/kid.js"> </script>

<div class="box">
	<h2>Kid nummer registrering</h2>
	<p>Her finner man en liste over medlemmer uten gyldig KID nummer i databasen</p>
</div>

<div class="box">

	<h3 id="title-status">Laster...</h3>

	<table id="members" class="max-width">
		<tr>
			<th scope="col" class="header-name">Navn</th>
			<th scope="col" class="header-Email">E-post</th>
			<th scope="col" class="header-phone-number">Telefonnummer</th>
			<th scope="col" class="header-KID">KID</th>
			<th scope="col" class="header-actions">Actions</th>
		</tr>

	</table>

	<template id="member">
		<tr>
			<td class="name"></td>
			<td class="email"></td>
			<td class="phone-number"></td>
			<td class="KID"><input type="number" pattern="/^\d{8}$/gm"></input></td>
			<td class="actions"><button class="save">Lagre</button></td>
		</tr>
	</template>


</div>

<script>
	addLoadEvent(getMembers);
</script>