<?php
class Translator
{
	private $page; 			// page name
	private $language; 		// language code. Currently supportig "no" and "en"
	private $directory;		// path to translation directory
	private $translations; 	// global array of all translations

	function __construct(string $page, string $lang = "no", string $dir="translations")
	{
		$this->page = $page;
		$this->language = $lang;
		$this->directory = $dir;
		$this->load_translation($page);
	}

	public function load_translation($page)
	{
		$file = "$this->directory/$page.json";

		if (!file_exists($file)) {
			return;
		}
	
		$decoded = json_decode(file_get_contents($file));
		if ($decoded === NULL){
			log_message("Warning: Could not decode json file: " . $file, __FILE__, __LINE__);
			return;
		}
		$this->translations[$page] = $decoded;

	}

	public function get_translation($key, $page = "")
	{
		$ret = "";

		$language = $this->language;
		if ($page == "") {
			$page = $this->page;
		}

		// if translations for this page is not loaded, try to load them.
		if (!isset($this->translations[$page])) {
			$this->load_translation($page);
		}

		// if translatons for this page is still not loaded return.
		if (!isset($this->translations[$page])) {
			// Bug here: We want logs when we are trying to use keys but not loaded the page. That means all illegal requests are logged here. They should be dropped.
			log_message("Warning: requesting a translation for a page: ". $page . " that has not been loaded yet", __FILE__, __LINE__);
			return "";
		}

		$translations_this_page = $this->translations[$page];

		// try to get requested language
		if(property_exists($translations_this_page->$language, $key)){
			$ret = $translations_this_page->$language->$key;
		}

		// if requested language is not set use norwegian as fallback 
		if(!isset($ret)){
			if(property_exists($translations_this_page->no, $key)){
				$ret = $translations_this_page->no->$key;
			}
		}

		if(!isset($ret)){
			$ret = "";
		}

		// Expand array keys
		if (is_array($ret)) {
			return implode("\n", $ret);
		}
		return $ret;
	}

	public function get_url($url)
	{
		global $base_url;
		$ret = $base_url . "/";
		if ($this->language != "no") $ret .= $this->language . "/";
		return $ret . ($url == "mainpage" ? "" : $url);
	}
}
