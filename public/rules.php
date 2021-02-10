<!DOCTYPE html>
<html>

<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body {
    font-family: 'Lato', sans-serif;
}

.overlay {
    height: 70%;
    width: 50%;
    display: none;
    position: fixed;
    z-index: 1;
    top: 15%;
    left: 25%;
    background-color: rgb(0,0,0);
    background-color: rgba(0,0,0, 0.9);
}

.overlay-content {
    position: relative;
    top: 15%;
    width: 100%;
    text-align: center;
    margin-top: 0px;
}

.overlay a {
    padding: 8px;
    text-decoration: none;
    font-size: 36px;
    color: #818181;
    display: block;
    transition: 0.3s;
}
.overlay h1 {
    padding: 8px;
    text-decoration: none;
    font-size: 36px;
    color: #818181;
    display: block;
    transition: 0.3s;
}
.overlay p {
    padding: 8px;
    text-decoration: none;
    font-size: 12px;
    color: #818181;
    display: block;
    transition: 0.3s;
}
.overlay li {
    padding: 8px;
    font-size: 12px;
    color: #818181;
    display: block;
    transition: 0.3s;
}
.overlay a:hover, .overlay a:focus {
    color: #f1f1f1;
}

.overlay .closebtn {
    position: absolute;
    top: 20px;
    right: 45px;
    font-size: 60px;
}

@media screen and (max-height: 450px) {
  .overlay a {font-size: 20px}
  .overlay .closebtn {
    font-size: 40px;
    top: 15px;
    right: 35px;
  }
}
</style>
</head>
<body>

<div id="myNav" class="overlay">
  <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
  <div class="overlay-content">
  	<img src="https://proxy.duckduckgo.com/iu/?u=http%3A%2F%2Fhousing.umn.edu%2Fsites%2Fhousing.umn.edu%2Ffiles%2Fhall_involvement.png&f=1" height="60px" width="60px">
    <h1>Regler NTNUI Svømming</h1>

    <ul>
      <li><p>Man må være medlen i NTNUI for å være med i svømmegruppen.</p></li>
      <li><p>Man må ha NSF lisens for å være medlem i NTNUI Svømming.</p></li>
      <li><p>Man må kunne svømme 50 meter uavbrutt og uten hjelp for å bli med i svømmegruppen.</p></li>
      <li><p>Man er pliktig til å stille på dugnad. Ved nektelse blir medlemskapet fryst ut kalenderåret.</p></li>
      <li><p>Det er strengt forbudt å svømme alene. Uansett om det er treningstid eller ikke.</p></li>
      <li><p>Treningen skal ikke starte før treningstid og skal avsluttes når treningstiden er over.</p></li>
      <li><p>Man er pliktig til å selge sjelen sin til NTNUI Svømming</p></li>
      <li><p>Man er pliktig til å delta på ølsvøm</p></li>
    </ul>



  </div>
</div>

<h2>Regler NTNUI Svømming - TEST</h2>
<span style="font-size:30px;cursor:pointer" onclick="openNav()">&#9776; Åpne reglene</span>

<script>
function openNav() {
  document.getElementById("myNav").style.display = "block";
}

function closeNav() {
  document.getElementById("myNav").style.display = "none";
}
</script>

</body>
</html>
