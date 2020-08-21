<?php 

/*

lag en side som skal vise:
1. antall gutter og jenter registrert i NTNUI Svømming
2. antall av hvert kjønn av 18 og 19 åringer (alder fastsettes av årstallet og ikke av datoen i dag)
3. Antall av hvert kjønn mellom 20 og 25 år.
4. antall av hvert kjønn over 26 år.

Ekstra (senere):
1. Antall fra triatlon
2. antall funksjonsnedsatte (må legges inn i medlemsregistreringen)
3. 


class accessors:
male-18-19
female-18-19
total-18-19

male-20-25
female-20-25
total-20-25

male-26
female-26
total-26

total-male
total-female
total-total

*/




 ?>
 <style media="screen">
   
 table {
  border-collapse: separate;
  border-spacing: 0;
  border-top: 1px solid grey;
}

td, th {
  margin: 0;
  border: 1px solid grey;
  white-space: nowrap;
  border-top-width: 0px;
}

div .middle {
  width: auto;
  overflow-x: auto;
  margin-left: 10em;
  overflow-y: visible;
  padding: 0;
  
}

.headcol {
  position: absolute;
  width: 10em;
  left: 0;
  top: auto;
  border-top-width: 1px;
  /*only relevant for first row*/
  margin-top: -1px;
  /*compensate for top border*/
}

</style>
 
 <div class="top box">
    <h2>Medlemsstatistikk</h2>
 </div>

 <div class="middle box">
<table>
        <tr style="background-color: darkgray;"><th class="headcol">Alder</th><td class="">Jenter</td><td class="">Gutter</td><td class="">Totalt</td></tr>
        <br>
        <tr><th class="headcol">18, 19 år</th><td class="female-18-19">QWERTYUI</td><td class="male-18-19">QWERTYUI</td><td class="total-18-19">QWERTYUI</td></tr>
        <tr><th class="headcol">20 - 25 år</th><td class="female-20-25">QWERTYUI</td><td class="male-20-25">QWERTYUI</td><td class="total-20-25">QWERTYUI</td></tr>
        <tr><th class="headcol">26 og eldre</th><td class="female-26">QWERTYUI</td><td class="male-26">QWERTYUI</td><td class="total-26">QWERTYUI</td></tr>
        <tr><th class="headcol">Totalt</th><td class="female-total">QWERTYUI</td><td class="male-total">QWERTYUI</td><td class="total-total">QWERTYUI</td></tr>
        
        
</table>
</div>
 
 <div class="middle box">
   
 </div>
