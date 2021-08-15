<?php
global $settings;
// TODO: remove unused html classes
// TODO: add as much php code to storeadmin.php and rewrite calls to backend in js
// TODO: implement translations
// TODO: on load get items from db
// TODO: on double click on item, hide all items, get purchases, show purchases box, enable a back link or button
// TODO: on set delivered send a POST request to backend.
?>

<link href="<?php print($settings['baseurl']); ?>/css/admin/store.css" />
<link href="https://unpkg.com/tabulator-tables@4.5.3/dist/css/tabulator.min.css" rel="stylesheet" />
<script type="text/javascript" src="https://unpkg.com/tabulator-tables@4.5.3/dist/js/tabulator.min.js"></script>
<script type="text/javascript" src="https://momentjs.com/downloads/moment.min.js"></script>
<script type="text/javascript" src="<?php print($settings['baseurl']); ?>/js/admin/store.js"></script>

<div class="box">
	<h3>
		Salgsvarer
	</h3>
	<label for="">Dobbeltklikk for å se salg</label>
	<div id="items"></div>

</div>

<div class="box hidden">
	<h3>Purchases</h3>
	<div class="purchases_list"></div>
</div>

<div id="add_container">
	<div class="box">
		<h3>Price Calculator</h3>
		<label for="">Price without fees: </label>
		<input type="number" name="" value="0" id="price-input" style="width: 50%;">
		<label for="">Price with fees: </label>
		<label for="" id="price-output"></label>

	</div>

	<div class="box">
		<h3>Legg til ny ting i butikken</h3>
		<form method="POST" enctype="multipart/form-data" action="<?php print($settings['baseurl']);?>/api/storeadmin/add_item">
			<label for="name_no">Tittel (Norsk):</label>
			<input name="name_no" type="text" required />
			<label for="name_en">Tittel (Engelsk):</label>
			<input name="name_en" type="text" />
			<label for="description_no">Beskrivelse (Norsk): (HTML er støttet)</label>
			<textarea name="description_no"></textarea>
			<label for="description_en">Beskrivelse (Engelsk):</label>
			<textarea name="description_en"></textarea>
			<label for="price">Pris (i øre)</label>
			<input name="price" type="number" min="100" required />
			<label for="amount">Maks antall (Tom for evig antall):</label>
			<input name="amount" type="number" min="1" />
			<label for="starttid">Tilgjengelig i tidsrommet (la være blank hvis alltid åpen)</label>
			<div class="form-row">
				<input type="date" name="startdate" placeholder="mm-dd-åååå" style="width: unset" />
				<input type="time" name="starttime" placeholder="13:00" style="width: unset" />
				til
				<input type="date" name="enddate" placeholder="mm-dd-åååå" style="width: unset" />
				<input type="time" name="endime" placeholder="13:00" style="width: unset" />
			</div>
			<label for="image">Bilde</label>
			<input name="image" type="file" accept="image/*" />
			<button name="add" type="submit">Legg til</button>
		</form>
	</div>
</div>
<script type="text/javascript">
	function createTable() {
		const store_data = <?php print json_encode($items); ?>;
		const groups = <?php print json_encode($groups); ?>;
		createTableMatrix(store_data, groups);
	}

	addLoadEvent(createTable);
	addLoadEvent(() => {
		document.getElementById("price-input").addEventListener("change", function() {
			document.getElementById("price-output").innerHTML = (document.getElementById("price-input").value * 1.012 + 1.8);
		});
	});
</script>

<!-- customer row with a purchase -->
<template>
	<div id="box-id" class="box">
		<label>Name</label>
		<label>Contact<a href='mailto:email'>e-mail</a> <a href="tel:1234567">Phone number</a></label>
		<label class="hidden">Comment</label>
		<button id="button-id" onclick="event()">Deliver / Delivered</button>
	</div>
</template>