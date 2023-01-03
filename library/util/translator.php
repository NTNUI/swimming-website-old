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
		$this->language = $lang;
		$this->directory = $dir;
		if (file_exists("public/$page.php")) {
			// Ignore construction if page does not exist
			$this->page = $page;
			$this->load_translation($page);
		}
	}

	public function load_translation($page)
	{
		$file = "$this->directory/$page.json";

		if ($page == "api") {
			return;
		}

		if (!file_exists($file)) {
			throw new Exception("translation does not exists: $file");
			return;
		}

		$decoded = json_decode(file_get_contents($file), flags: JSON_THROW_ON_ERROR);
		$this->translations[$page] = $decoded;
	}

	public function get_translation($key, $page = ""): string
	{
		$ret = "";

		if ($page == "") {
			$page = $this->page;
		}

		// if translations for this page is not loaded, try to load them.
		if (!isset($this->translations[$page])) {
			$this->load_translation($page);
		}

		// if translations for this page is still not loaded return.
		if (!isset($this->translations[$page])) {
			throw new Exception("Requesting a non existing page: $page");
		}

		$translations_this_page = $this->translations[$page];

		// try to get requested language
		$language = $this->language;
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
			throw new Exception("page: $page does not have translations for $key");
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
