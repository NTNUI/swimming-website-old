<?php

declare(strict_types=1);

global $settings;
print("<link rel='stylesheet' type='text/css' href='${settings["baseurl"]}/css/modal.css'></link>");
?>

<template id="modal_template">
    <div class="modal_background visible">
        <div class="modal_content">
            <h2 class="modal_header"></h2>
            <p class="modal_message"></p>
            <span class="status-graphic"></span>
            <div class="bottom">
                <button class="modal_button modal_accept_button"></button>
                <button class="modal_button modal_decline_button"></button>
            </div>
        </div>
    </div>
</template>