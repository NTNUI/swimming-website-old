<?php
function print_folder($folder) {
	//setter riktig directory for filene og sjekker at det er gyldig
	if ($handle = opendir($folder)) {
		//itererer igjennom filene i mappen
		while (false !== ($file = readdir($handle))) {
			//tester om fila i mappa er av typen *.pdf
			if (substr(strrchr($file, "."), 1) == 'pdf'){
				//lager HTMl linje med link med klikkbart link til hvert dokument
				print "<a href='$base_url/svommer/$folder/$file'>$file</a><br>";
			} else if (is_dir("$folder/$file")) {
				if ($file == "." or $file == "..") continue;
				print "<h3>$file</h3>";
				print_folder("$folder/$file");
			}
		}		
		closedir($handle);
	}
}

function publish_folder($folder, $title) {

	print "<div class='box'>";
	print "<h2>$title</h3>";
	print_folder($folder);
	print "</div>";
}

publish_folder("src/info", $t->get_translation("info"));
publish_folder("src/forms", $t->get_translation("forms"));
publish_folder("src/board_meetings", $t->get_translation("board_meetings"));
