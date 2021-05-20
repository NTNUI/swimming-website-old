<?php
global $t;
$t->load_translation("not_found");
?>

<div class="box">
    <h2>
        404 - Page not found<?php $t->get_translation("header", "not_found"); ?>
    </h2>
    <p>
        <?php $t->get_translation("content", "not_found"); ?>
    </p>
</div>