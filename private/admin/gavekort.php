<?php 
declare(strict_types=1);

global $settings;
?>

<form id="form">
	<label for="name">Mottakers navn</label>
	<input type="text" name="name" id="name" placeholder="Ola Nordmann"/>
	<label for="email">Epostaddresse</label>
	<input type="email" name="email" id="email" placeholder="me@example.biz"/>
	<label for="amount">Beløp på gavekort (sjekk selv at det stemmer med koden></label>
	<input type="number" name="amount" id="amount" value="50"/>
	<label for="code">Gavekortkode</label>
	<input type="text" name="code" id="code"/>
	<label for="extra">Ekstra tekst (f. eks hvor man går for å bruke gavekortet</label>
	<textarea name="extra" id="extra"></textarea>
	<button id="getPreview">Get preview</button>

<div id="preview">
</div>
<button id="sendData" disabled="true">Send eposten</button>

</form>

<link href="<?php print $settings['baseurl'];?>/css/admin/gavekort.css">
<script type="text/javascript" src="<?php print $settings['baseurl'];?>/js/admin/gavekort.js"><script>