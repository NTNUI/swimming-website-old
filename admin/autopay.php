<script type="text/javascript" src="js/autopay.js"></script>

<div class="box">
    <h2>Generer <a href="https://ui.vision">Kantu</a> script for betaling</h2>
    
    <label for="kidnumbers">Kidnummere (én per linje)</label>
    <textarea id="kidnumbers"></textarea>
    
    
    <label for="output">Output (oppdateres fortløpende)</label>
    <textarea readonly id="output"></textarea>
    
    Betalinger generert: <span id="paymentsGenerated">0</span><br>
    <button id="clipboard">Kopier til utklippstavle</button>
</div>

<div class="box">
    <h3>Avanserte innstillinger</h3>
    <label for="amount">Beløp</label>
    <input id="amount" value="750" type="number"></input>
    
    <label for="kontonr">Kontonummer</label>
    <input id="kontonr" value="78740670025"></input>
    
    <label for="sleepdur">Ventetid mellom hver betaling</label>
    <input id="sleepdur" type="number" value="2000"></input>
    
    <label for="url">Url</label>
    <input id="url" type="text" value="https://district.danskebank.no/#/app?app=payments&path=%2FBN%2FBetaling-BENyInit-GenericNS%2FGenericNS%3Fq%3D1569587951868"></input>
    
    <label for="label">Merknad</label>
    <input id="label" type="text" value="Lisens NSF"></input>
</div>    
