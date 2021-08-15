<?php
global $settings;
?>

<link rel="stylesheet" type="text/css" href="<?php print $settings['baseurl'];?>/css/admin_dugnad.css">
<script src="<?php print $settings['baseurl'] ?>/js/dugnad.js"></script>

<div class="box">
	<h2>Dugnadsliste</h2>
	<p>Her kan du hente ut dine personlige slaver. Søk etter ønsket person eller antall personer du ønsker for ditt oppdrag.</p>
</div>

<div class="box">
	<div class="flex">
		<div class="sameRow">
			<label for="name">Navn</label>
			<input name="name" type="text" onkeyup="search()" />
		</div>
		<div class="sameRow" style="width:30%;"></div>
		<div class="sameRow">
			<label for="getRandom">Antall personer</label>
			<input name="getRandom" type="number" min="1" max="100" value="5" onchange="randomClick()" />
		</div>
	</div>

	<div class="flex">
		<div class="sameRow"></div>
		<div class="sameRow" style="width:30%;"></div>
		<div class="sameRow">
			<button onclick="randomClick()">Hent</button>
		</div>
	</div>

	<div>
		<table id="members" class="max-width">
			<tr>
				<th scope="col" class="header-name">Navn</th>
				<th scope="col" class="header-email">E-post</th>
				<th scope="col" class="header-phone">Telefonnummer</th>
				<th scope="col" class="header-status">Status</th>
				<th scope="col" class="header-actions">Actions</th>
			</tr>
		</table>
	</div>

</div>

<template id="member">
	<tr class="member-row">
		<td class="name"></td>
		<td><a class="email" href=""></a></td>
		<td class="phone"></td>
		<td class="status"></td>
		<td class="actions"><button class="approve">Godkjenn</button><button class="reject red">Avslå</button></td>
	</tr>
</template>
