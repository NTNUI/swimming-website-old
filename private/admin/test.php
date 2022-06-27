<?php
declare(strict_types=1);

require_once("library/templates/content.php");
require_once("library/templates/modal.php");
require_once("library/templates/store.php");

print_content_header(
    "Testing page",
    "This page includes a lot of elements that can be tested easily on their own"
);
global $settings;
print("<script type='module' src='${settings['baseurl']}/js/admin/test.js'></script>");
?>
<div class="box">
    <div class="contents">
        <h2>Modal test</h2>
        <p>Test modal popups</p>
    </div>
    <div class="bottom">
        <button class="checkout">checkout</button>
        <button class="alert">alert</button>
        <button class="question">question</button>
        <button class="wait">wait</button>
        <button class="success">success</button>
        <button class="failure">failure</button>

    </div>
</div>
