<?php
declare(strict_types=1);

global $settings;
require_once("library/templates/modal.php");

?>

<link rel="stylesheet" href="<?php print $settings['baseurl']; ?>/css/admin/cin.css">
<script type="module" src="<?php print $settings['baseurl']; ?>/js/admin/cin.js"> </script>

<div class="box">
    <h2>Generate <a href="https://ui.vision">UI Vision</a> script for bank transfer</h2>

    <label for="CIN_numbers">Following customer identification numbers has not been forwarded yet</label>
    <textarea readonly id="CIN_numbers"></textarea>

    <textarea readonly id="output"></textarea>

    Total pending payments: <span id="paymentsGenerated">0</span><br>
    <button disabled id="clipboard">Copy script 📋</button>
    <button disabled id="btn-cin-mark-forwarded">Mark as forwarded ✅</button>
	<button disabled id="btn-cin-options">Options ⚙</button>
</div>

<div hidden class="box cin-options">
	<h3>Options</h3>
	<label for="amount">Amount</label>
	<input id="amount" value="600" type="number"></input>

	<label for="account_number">Receiver account</label>
	<input id="account_number" value="78740670025"></input>

	<label for="sleep_duration">Delay between payments</label>
	<input id="sleep_duration" type="number" value="2000"></input>

	<label for="url">Url</label>
	<input id="url" type="text" value="https://district.danskebank.no/#/app?app=payments&path=%2FBN%2FBetaling-BENyInit-GenericNS%2FGenericNS%3Fq%3D1569587951868"></input>

	<label for="label">Transfer message</label>
	<input id="label" type="text" value="Lisens NSF"></input>
</div>

<div class="box">
	<h2>NSF Customer identification number (CIN) registration</h2>
	<p>View, modify members CIN properties. Get customer identification number from <a href="https://sa.nif.no" target="_blank">Sports Admin</a>.</p>
</div>

<div class="box">

	<table id="members" class="max-width">
		<tr>
			<th scope="col" class="header-name">Name</th>
			<th scope="col" class="header-gender">Gender</th>
			<th scope="col" class="header-birth_date">Birth date</th>
			<th scope="col" class="header-Email">E-mail</th>
			<th scope="col" class="header-phone">Phone</th>
			<th scope="col" class="header-address">Address</th>
			<th scope="col" class="header-zip">Zip</th>
			<th scope="col" class="header-cin">CIN</th>
			<th scope="col" class="header-actions">Actions</th>
		</tr>

	</table>

	<template id="member">
		<tr>
			<td class="name"></td>
			<td class="gender"></td>
			<td class="birth_date"></td>
			<td class="email"></td>
			<td class="phone"></td>
			<td class="address"></td>
			<td class="zip"></td>
			<td class="cin"><input type="number" pattern="/^\d{8}$/gm" /></td>
			<td class="actions"><button class="save">Save</button></td>
		</tr>
	</template>


</div>