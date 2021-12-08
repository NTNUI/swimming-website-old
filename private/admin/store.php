<?php
global $settings;
// TODO: remove unused html classes
// TODO: implement translations
?>

<link href="<?php print($settings['baseurl']); ?>/css/admin/store.css" />
<link href="https://unpkg.com/tabulator-tables@4.5.3/dist/css/tabulator.min.css" rel="stylesheet" />
<script defer type="text/javascript" src="https://unpkg.com/tabulator-tables@4.5.3/dist/js/tabulator.min.js"></script>
<script defer type="text/javascript" src="https://momentjs.com/downloads/moment.min.js"></script>
<script defer type="text/javascript" src="<?php print($settings['baseurl']); ?>/js/admin/store.js"></script>

<div class="hidden">
	<div class="box">
		<h3>Orders</h3>
	</div>
	<div id="order_list"></div>
</div>

<div class="box">
	<h3>
		Store selection
	</h3>
	<label for="">Double click to see orders</label>
	<div id="products"></div>
</div>

<div id="add_container">
	<div class="box">
		<h3>Price Calculator</h3>
		<div>
			<label for="">Price without fees</label>
			<input type="number" name="" value="0" id="price-input">
		</div>
		<div>
			<label for="">Price with fees</label>
			<label for="" id="price-output"></label>
		</div>
	</div>
	<div class="box">
		<form id="form-add-store-item" action="<?php print $settings["baseurl"]; ?>/api/storeadmin" method="POST">
		<h3>Add a product to the store</h3>
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
						<input type="date" name="date_start" placeholder="mm-dd-yyyy"/>
						<input type="time" name="time_start" placeholder="13:00"/>
					</div>
					<div>
						to
						<input type="date" name="date_end" placeholder="mm-dd-yyyy"/>
						<input type="time" name="time_end" placeholder="13:00"/>
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
			<button class="purchase-row-button-deliver">Deliver</button>

		</div>
	</div>
</template>