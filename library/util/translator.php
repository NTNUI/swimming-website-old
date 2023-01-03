<?php

declare(strict_types=1);

class Translator
{
	private $page; 			// page name
	private $language; 		// language code. Currently supporting "no" and "en"
	private $directory;		// path to translation directory
	private $translations; 	// global array of all translations

	function __construct(string $page, string $lang = "no", string $dir = "translations")
	{
		$this->page = $page;
		$this->language = $lang;
		$this->directory = $dir;
		$this->load_translation($page);
	}

	public function load_translation($page)
	{
		$file = "$this->directory/$page.json";

		if ($page == "api") {
			return;
		}

		if (!file_exists($file)) {
			static $logged = false;
			if (!$logged) {
				// some pages look up translation multiple times resulting in multiple log messages
				log::message("Warning: Requesting a non existing page: $page", __FILE__, __LINE__);
				$logged = true;
			}
			return;
		}

		$decoded = json_decode(file_get_contents($file));
		if ($decoded === NULL) {
			log::message("Warning: Could not decode json file: $file, for page: $page", __FILE__, __LINE__);
			return;
		}
		$this->translations[$page] = $decoded;
	}

	public function get_translation($key, $page = ""): string
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

		// if translations for this page is still not loaded return.
		if (!isset($this->translations[$page])) {
			// loading translation for a file that does not exist
			return "";
		}

		$translations_this_page = $this->translations[$page];

		// try to get requested language
		if (property_exists($translations_this_page->$language, $key)) {
			$ret = $translations_this_page->$language->$key;
		}

		// if requested language is not set use norwegian as fallback 
		if ($ret == "") {
			if (property_exists($translations_this_page->no, $key)) {
				$ret = $translations_this_page->no->$key;
			}
		}

		if ($ret == "") {
			log::message("Warning: page: $page does not have translations for $key", __FILE__, __LINE__);
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
		global $settings;
		$ret = $settings["baseurl"] . "/";
		if ($this->language != "no") $ret .= $this->language . "/";
		return $ret . ($url == "mainpage" ? "" : $url);
	}
}
