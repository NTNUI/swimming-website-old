		</div>
	</div>
	<script src="<?php print $base_url; ?>/js/base.js"></script>
	<div id="covidbox" style="position: fixed; background-color: red; width: 100%; bottom: 0; left: 0; text-align: center">
		COVID-19: Please see updated guidelines, practical information and news on our facebook group: <a href="https://www.facebook.com/groups/2250060697">https://www.facebook.com/groups/2250060697</a>.<br>
		You currently have to sign up <strong>before each practice</strong>.<br>
		<button id="hidecovid">hide this message</button>
	</div>
	<script type="text/javascript">
		const covidbox = document.getElementById("covidbox");
		let should_hide = localStorage.getItem("hide_covid");
		if (should_hide) covidbox.classList.add("hidden");
		document.getElementById("hidecovid").addEventListener("click", () =>  {
			covidbox.classList.add("hidden");
			localStorage.setItem("hide_covid", true);
		});
	</script>
</body>
</html>
