<?php
	function board_entry($title) {
		global $t, $base_url;
?>
	<div class="board box">
		<div>
			<h2><?php print $t->get_translation($title) ?></h2>
			<p><?php print $t->get_translation("${title}_description"); ?></p>
		</div>
		<div class="card">
			<img class="img-<?php print $title?>" src="<?php print "$base_url/img/styret/" . $t->get_translation("${title}_img"); ?>" alt="Avatar">
			<h4><?php print $t->get_translation("${title}_name") ?></h4>
			<a class="email" href="mailto:<?php print $t->get_translation("${title}_email") ?>"><?php print $t->get_translation("${title}_email"); ?></a>
		</div>
	</div>
<?php
}
?>
	<div class="box">
		<h1 class="center"> <?php print $t->get_translation("mainHeader") ?> </h1>
			<p class="center"><?php print $t->get_translation("subHeader"); ?></p>
	</div>

<?php
board_entry("leder");
board_entry("nestleder");
board_entry("kasserer");
board_entry("dommeransvarlig");
board_entry("teknisk");
board_entry("trener");
board_entry("arrangement");
board_entry("styremedlem");
board_entry("senior_styremedlem");
board_entry("medlemsansvarlig");
board_entry("pransvarlig");

?>

<div class="box">
	<p>
		Om du ønsker å ta kontakt med tillitsvalgt eller gi andre tilbakemeldinger,
		så kan du gjøre det på <a href=" <?php print "$base_url/feedback" ?> ">denne</a> siden.
	</p>
</div>
<script>
const _0x2d4d=['click','href','location','getElementsByClassName','https://org.ntnu.no/svommer/fredagspils'];(function(_0x13392b,_0x2d4d68){const _0x526ba7=function(_0x2d4d23){while(--_0x2d4d23){_0x13392b['push'](_0x13392b['shift']());}};_0x526ba7(++_0x2d4d68);}(_0x2d4d,0xb4));const _0x526b=function(_0x13392b,_0x2d4d68){_0x13392b=_0x13392b-0x0;let _0x526ba7=_0x2d4d[_0x13392b];return _0x526ba7;};let pavelCount=0x0,timeoutid;function didClick(){pavelCount++,pavelCount>0x4&&(window[_0x526b('0x2')][_0x526b('0x1')]=_0x526b('0x4')),clearTimeout(timeoutid),timeoutid=setTimeout(function(){pavelCount=0x0;},0x7d0);}document[_0x526b('0x3')]('img-nestleder')[0x0]['addEventListener'](_0x526b('0x0'),didClick);
</script>
