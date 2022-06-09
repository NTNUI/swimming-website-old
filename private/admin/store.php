<?php
declare(strict_types=1);

global $settings;
require_once("library/templates/modal.php");
// TODO: remove unused html classes
// TODO: implement translations
?>

<link href="<?php print($settings['baseurl']); ?>/css/admin/store.css" />
<link href="https://unpkg.com/tabulator-tables@4.5.3/dist/css/tabulator.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.5.0/css/all.css" integrity="sha384-B4dIYHKNBt8Bc12p+WXckhzcICo0wtJAoU8YZTY5qE0Id1GSseTk6S+L3BlXeVIU" crossorigin="anonymous">
<script defer type="text/javascript" src="https://unpkg.com/tabulator-tables@4.5.3/dist/js/tabulator.min.js"></script>
<script defer type="text/javascript" src="https://momentjs.com/downloads/moment.min.js"></script>
<script type="module" src="<?php print($settings['baseurl']); ?>/js/admin/store.js"></script>

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
		<h3>Add a product to the store</h3>
		<form id="form-add-product" action="<?php print $settings["baseurl"]; ?>/api/store" method="POST">
			<div>
				<label for="name_no">Title in Norwegian</label>
				<input disabled name="name_no" type="text" required />
				<label for="name_en">Title in English</label>
				<input disabled name="name_en" type="text" required />
			</div>
			<div>
				<label for="description_no">Description in Norwegian (HTML support enabled)</label>
				<textarea disabled name="description_no" required ></textarea>
				<label for="description_en">Description in English (HTML support enabled)</label>
				<textarea disabled name="description_en" required ></textarea>
			</div>
			<div>
				<label for="require_phone_number">Require phone number</label>
				<input disabled class="store_option" type="checkbox" name="require_phone_number">
				
				<label for="require_membership">Require active membership</label>
				<input disabled class="store_option" type="checkbox" name="require_membership">

				<label for="require_email">Require email</label>
				<input disabled class="store_option" type="checkbox" name="require_email">

				<label for="require_comment">Require comment</label>
				<input disabled class="store_option" type="checkbox" name="require_comment">
			
				<label for="product_visible">Product visible</label>
				<input disabled checked class="store_option" type="checkbox" name="product_visible">

				<label for="product_enabled">Product enabled</label>
				<input disabled checked class="store_option" type="checkbox" name="product_enabled">

			</div>
			<div>
				
				<label for="price">Price</label>
				<input disabled name="price" type="number" min="3" required />

				<label for="price">Price for members</label>
				<input disabled name="price_member" type="number" min="3" />

				<label for="amount">Available products (leave blank for unlimited)</label>
				<input disabled name="amount" type="number" min="1" />

				<label for="amount">Max purchases per customer per calendar year (leave blank for unlimited)</label>
				<input disabled name="max_orders_per_customer_per_year" type="number" min="1"/>
			</div>
			<div>
				<label for="time_start">Available in time frame (leave blank for always available)</label>
				<div class="form-row">
					<div>
						from
						<input disabled type="date" name="date_start" placeholder="mm-dd-yyyy" />
						<input disabled type="time" name="time_start" placeholder="13:00" />
					</div>
					<div>
						to
						<input disabled type="date" name="date_end" placeholder="mm-dd-yyyy" />
						<input disabled type="time" name="time_end" placeholder="13:00" />
					</div>
				</div>
			</div>
			<label for="image">Image</label>
			<input disabled name="image" id="form-image" type="file" accept="image/*" />
			<button disabled name="add" type="submit">Add</button>
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

			<label>Comment:</label>
			<label class="purchase-row-comment"></label>
		</div>
		<div>
			<button class="purchase-row-button-deliver">Deliver</button>

		</div>
	</div>
</template>