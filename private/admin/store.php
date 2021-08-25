<?php
global $settings;
// TODO: remove unused html classes
// TODO: add as much php code to storeadmin.php and rewrite calls to backend in js
// TODO: implement translations
// TODO: on load get items from db
// TODO: on double click on item, hide all items, get purchases, show purchases box, enable a back link or button
// TODO: on set delivered send a POST request to backend.
// TODO: add drag & drop support for image uploads
?>

<link href="<?php print($settings['baseurl']); ?>/css/admin/store.css" />
<link href="https://unpkg.com/tabulator-tables@4.5.3/dist/css/tabulator.min.css" rel="stylesheet" />
<script type="text/javascript" src="https://unpkg.com/tabulator-tables@4.5.3/dist/js/tabulator.min.js"></script>
<script type="text/javascript" src="https://momentjs.com/downloads/moment.min.js"></script>
<script type="text/javascript" src="<?php print($settings['baseurl']); ?>/js/admin/store.js"></script>

<div class="hidden">
	<div class="box">
		<h3>Purchases</h3>
	</div>
	<div id="purchases_list"></div>
</div>

<div class="box">
	<h3>
		Store selection
	</h3>
	<label for="">Double click to see purchases</label>
	<div id="items"></div>
</div>

<div id="add_container">
	<div class="box">
		<h3>Price Calculator</h3>
		<div>
			<label for="">Price without fees</label>
			<input type="number" name="" value="0" id="price-input" style="width: 50%;">
		</div>
		<div>
			<label for="">Price with fees</label>
			<label for="" id="price-output"></label>
		</div>
	</div>
	<div class="box">
		<h3>Add an item to the store</h3>
		<form id="form-add-store-item" action="<?php print $settings["baseurl"]; ?>/api/storeadmin" method="POST">
			<div>
				<label for="name_no">Title in Norwegian</label>
				<input name="name_no" type="text" required />
				<label for="name_en">Title in English</label>
				<input name="name_en" type="text" />
			</div>
			<div>
				<label for="description_no">Description in Norwegian (HTML support enabled)</label>
				<textarea name="description_no"></textarea>
				<label for="description_en">Description in English (HTML support enabled)</label>
				<textarea name="description_en"></textarea>
			</div>
			<div>
				<label for="price">Price (in øre)</label>
				<input name="price" type="number" min="100" required />
				<label for="amount">Units / Spots available (leave blank for unlimited)</label>
				<input name="amount" type="number" min="1" />
			</div>
			<div>
				<label for="time_start">Available in time frame (leave blank for always available)</label>
				<div class="form-row">
					<div>
						from
						<input type="date" name="date_start" placeholder="mm-dd-yyyy" style="width: unset" />
						<input type="time" name="time_start" placeholder="13:00" style="width: unset" />
					</div>
					<div>
						to
						<input type="date" name="date_end" placeholder="mm-dd-yyyy" style="width: unset" />
						<input type="time" name="time_end" placeholder="13:00" style="width: unset" />
					</div>
				</div>
			</div>
			<label for="image">Image</label>
			<input name="image" id="form-image" type="file" accept="image/*" />
			<button name="add" type="submit">Add</button>
		</form>
	</div>
</div>

<!-- customer row with a purchase -->
<template id="purchase-row">
	<div id="box-id" class="box row">
		<div>
			<label>Name: </label>
			<label class="purchase-row-name">Name</label>
		</div>
		<div>

			<label>Contact:</label>
			<a class="purchase-row-email" href='mailto:email'>e-mail</a>
			<a class="purchase-row-phone" href="tel:1234567">Phone number</a>
		</div>
		<div>

			<label class="purchase-row-comment">Comment</label>
		</div>
		<div>
			<button class="purchase-row-button-deliver">Deliver / Delivered</button>

		</div>
	</div>
</template>