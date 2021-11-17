<head>
	<meta property="og:title" content="NTNUI Svømming"/>
	<meta name="description" content="NTNUI Svømming er en svømmeklubb for studenter ved universitetet NTNU i Trondheim."/>
	<meta name="title" content="NTNUI Svømming"/>
</head>

<link rel="stylesheet" type="text/css" href="<?php print $settings["baseurl"]."/css/slideshow.css" ?>"></link>
<div class="box">

	<h1 padding="10px" border="0px" margin="0px" offset="10px">
		<?php print $t->get_translation("mainHeader"); ?>
	</h1>
	<h2 padding="10px" border="0px" margin="0px" offset="10px">
		<?php print $t->get_translation("subHeader"); ?>
	</h2>
</div>

<div class="box">

	<div class="slideshow-container">
<?php
		//Make image containers
		$image_count = 9;
		for ($i = 1; $i <= $image_count; $i++) { ?>
		<div class="mySlides fade">
			<div class="numbertext"><?php print "$i / $image_count" ?></div>
			<img src="<?php print $settings["baseurl"]."/img/slideshow/bilde_$i.jpg"; ?>" style="width:100%">
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

<script type="text/javascript" src="<?php print "$base_url/js/slideshow.js" ?>"></script>
