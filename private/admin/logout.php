<?php

//check to make sure the session variable is registered
if ($_SESSION['innlogget'] == 1) {
	//session variable is registered, the user is ready to logout
	session_unset();
	session_destroy();?>

	<div class="box green">
		<h1>Du har nå logget ut!</h1>
		Velkommen tilbake!
	</div>
<?php
} else { ?>
	<div class="box error">
		Du kan ikke logge ut uten å ha logget inn!
	</div>
<?php }

?>
