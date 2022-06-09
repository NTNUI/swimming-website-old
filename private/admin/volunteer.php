<?php
declare(strict_types=1);

global $settings;
?>

<link rel="stylesheet" type="text/css" href="<?php print $settings['baseurl'];?>/css/admin/dugnad.css">
<script src="<?php print $settings['baseurl']; ?>/js/admin/dugnad.js"></script>

<div class="box">
	<h2>Volunteering overview</h2>
	<p>
		This is a page where you can get your personal slaves for any reason. Search for a specific member or number of volunteers you need.	
	</p>
</div>

<div class="box">
	<div class="flex">
		<div class="sameRow">
			<label for="name">Name</label>
			<input name="name" type="text" onkeyup="search()" />
		</div>
		<div class="sameRow" style="width:30%;"></div>
		<div class="sameRow">
			<label for="getRandom">Amount of people</label>
			<input name="getRandom" type="number" min="1" max="100" value="5" onchange="randomClick()" />
		</div>
	</div>

	<div class="flex">
		<div class="sameRow"></div>
		<div class="sameRow" style="width:30%;"></div>
		<div class="sameRow">
			<button onclick="randomClick()">Get</button>
		</div>
	</div>

	<div>
		<table id="members" class="max-width">
			<tr>
				<th scope="col" class="header-name">Name</th>
				<th scope="col" class="header-email">E-mail</th>
				<th scope="col" class="header-phone">Phone number</th>
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
		<td class="actions"><button class="approve">Approve</button><button class="reject red">Reject</button></td>
	</tr>
</template>
