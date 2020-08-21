<?php
class Translator {
	private $translations; 
	private $page, $language;
	//Path to translation files
	private $directory = "translations";

	function __construct($page, $lang = "no") {
		$this->page = $page;
		$this->language = $lang;
		$this->load_translation($page);
	}

	public function load_translation($page) {
		$file = "$this->directory/$page.json";
		
		if (file_exists($file)) {
			$this->translations->$page = json_decode(file_get_contents($file));
		}
	}
	
	/*
	
	public function load_translation($page) {
		$file = "$this->directory/$page.json";
		if (file_exists($file)) {
			$this->translations->$page = json_decode(file_get_contents($file));
			
		}
	}
	
	
	
	
	
	*/
	

	public function get_translation($key, $page = "") {
		$language = $this->language;
		if ($page == "") $page = $this->page;
		$ret = "";
		//Page not loaded
		if (!array_key_exists($page, $this->translations)) return "";
		$trans = $this->translations->$page;
		//If page is loaded in language, and language has translation for key
		if (array_key_exists($language, $trans)
			and array_key_exists($key, $trans->$language)) $ret = $trans->$language->$key;
		//Fallback to norwegian if possible
		else if (array_key_exists("no", $trans)
			and array_key_exists($key, $trans->no)) $ret = $trans->no->$key;
		//Return nothing if neither language has the key
		else return "";

		//Expand array keys
		if (is_array($ret)) return implode("\n", $ret);
		return $ret;
	}

	public function get_url($url) {
		global $base_url;
		$ret = $base_url . "/";
		if ($this->language != "no") $ret .= $this->language . "/";
		return $ret . ( $url == "mainpage" ? "" : $url);
	}
}
