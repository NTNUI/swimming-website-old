<?php
// Guess new board starts every 1st of february
$genfors = strtotime("first day of february" . (date("m") < 2 ? " last year" : ""));
$conn = connect("web");
$query = $conn->prepare("SELECT users.name, beers.date FROM friday_beer as beers JOIN users ON users.id = beers.user_id WHERE (beers.date > ? AND users.role IN (2, 5, 6)) ORDER BY beers.user_id, beers.date");
$query->bind_param("s", date("Y-m-d", $genfors));
$query->execute();

$query->bind_result($username, $date);
$result = array();
while ($query->fetch()) {
	$result[$username][] = $date;
}
?>
<style>
body {
	cursor: url('img/icons/936_1368087457.png'), auto;
}
</style>
<script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
<div class="box">
<div id="plot"></div>
</div>
<script type="text/javascript">
const data = JSON.parse('<?php print json_encode($result)?>');
const plots = [];
const genfors = new Date("<?php print date("Y-m-d", $genfors);?>");
for (user in data) { 
	const x = data[user]; 
	x.unshift(genfors);
	let yi = 0; 
	let y = []; 
	for (p in x) {
		y.push(yi++);
	} 
	plots.push({
		x: x,
		y: y,
		types: 'lines',
		name: user,
	});
}
const layout = {
	"title": "Friday beers per board member per time",
}

Plotly.newPlot("plot", plots, layout);
</script>
