<?php use Michelf\Markdown; ?>
<head>
	<meta property="og:title" content="NTNUI Svømming"/>
	<meta name="description" content="NTNUI Svømming er en svømmeklubb for studenter ved universitetet NTNU i Trondheim."/>
	<meta name="title" content="NTNUI Svømming"/>
</head>

<link rel="stylesheet" type="text/css" href="<?php print $settings["hosting"]["baseurl"]."/css/slideshow.css" ?>"></link>
<div class="box green backdrop">

	<h1 padding="10px" border="0px" margin="0px" offset="10px">
		<?php print $t->get_translation("mainHeader"); ?>
	</h1>
	<h3 padding="10px" border="0px" margin="0px" offset="10px">
		<?php print $t->get_translation("subHeader"); ?>
	</h3>
</div>

<div class="box">

	<div class="slideshow-container">
<?php
		//Make image containers
		$image_count = 6;
		for ($i = 1; $i <= $image_count; $i++) { ?>
		<div class="mySlides fade">
			<div class="numbertext"><?php print "$i / $image_count" ?></div>
			<img src="<?php print $settings["hosting"]["baseurl"]."/img/slideshow/bilde_$i.jpg"; ?>" style="width:100%">
			<div class="text"><?php print $t->get_translation("caption_$i"); ?></div>
		</div>
<?php		}
		?>
		<a class="prev" onclick="plusSlides(-1)">&#10094;</a>
		<a class="next" onclick="plusSlides(1)">&#10095;</a>
	</div>

	<div style="margin-top: 5px;">
		<?php for ($i = 1; $i <= $image_count; $i++) {?>
			<span class="dot" onclick="currentSlide(<?php print $i ?>)"></span>
<?php		} ?>
	</div>

	<p><?php print $t->get_translation("group_description"); ?></p>

</div>

<div class="news-box">
	<h1><?php print $t->get_translation("news_header");?></h1>
	<h2><?php print $t->get_translation("news_content");?></h2>

	<?php

	$conn = connect("web");
	// Check connection
	if (!$conn) {
	    die("Connection failed: " . mysqli_connect_error());
	}

	$month_limit = 6*30; // 6 months, has to be in days
	$amount_limit = 5; // Maximum amount of posts
	$sql = "SELECT overskrift, innhold, av, tid FROM forside WHERE datediff(now(), tid) < $month_limit ORDER BY nokkel DESC LIMIT $amount_limit";
	$query = $conn->prepare($sql);
	$query->execute();
	$query->store_result();
	if ($query->num_rows > 0) {
		$query->bind_result($header, $content, $author, $time);
		// output data of each row
		while($query->fetch()) {

			$content_html = Markdown::defaultTransform($content);
				echo "<div class='box'><h2>$header</h2><br><p>" . $content_html . "</p><br><small><small> Av: " . $author. " Tid ". $time ."<br> </small></small></div><br>";
			}
	} else {
	    echo "<div class='box'><h2>Ingen nyheter funnet</h2><br><p>Send mail til teknisk leder!</p></div>";
	}

	$query->close();
	mysqli_close($conn);

	?>
</div>

<script type="text/javascript" src="<?php print "$base_url/js/slideshow.js" ?>"></script>
