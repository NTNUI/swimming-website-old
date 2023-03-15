<?php
declare(strict_types=1);

global $settings;
require_once("library/templates/modal.php");
?>
<script type="module" src="<?php print $settings['baseurl']; ?>/js/admin/autopay.js"></script>

<div class="box">
    <h2>Generate <a href="https://ui.vision">Kantu</a> script for bank transfer</h2>

    <label for="CIN_numbers">Customer identification numbers. One per line</label>
    <textarea id="CIN_numbers"></textarea>

    <label for="output">Output</label>
    <textarea readonly id="output"></textarea>

    Payments generated: <span id="paymentsGenerated">0</span><br>
    <button id="clipboard">Copy to clipboard</button>
</div>

<div class="box">
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