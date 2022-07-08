<?php

declare(strict_types=1);

// This class is hard to debug because when there are issues this class returns empty values.
// that means valid space and invalid space (errors) overlap.
// TODO: create a custom Exception class than can be caught by users of this class.
// fatal errors can throw regular \Exception exceptions when stuff really does not work

class Translator
{
	private array $translations = [];

	function __construct(
		string $page = "",
		private string $language = "no",
		private string $directory = "translations"
	) {
		$this->page = lcfirst($page);
		$this->load_translation($this->page);
	}

	public function load_translation(string $page): void
	{
		$page = lcfirst($page);
		if ($page === "api") {
			return;
		}
		if (!empty($this->translations[$page])) {
			log::message("translations for $page is already loaded", __FILE__, __LINE__);
			// translations already loaded
			return;
		}
		$file = "$this->directory/$page.json";

		if (!file_exists($file)) {
			log::message("Warning: Requesting a non existing page: $page", __FILE__, __LINE__);
			return;
		}
		$file_content = file_get_contents($file);
		if ($file_content === false) {
			log::message("Warning: Could not read file contents", __FILE__, __LINE__);
			return;
		}
		// json_decode needs increased depth. Otherwise some translations will not load
		$decoded = json_decode($file_content, true, depth: 2024, flags: JSON_OBJECT_AS_ARRAY);
		if ($decoded === NULL) {
			log::message(json_last_error_msg(), __FILE__, __LINE__);
			return;
		}
		$this->translations[$page] = $decoded;
	}

	public function get_translation(string $key, ?string $page = NULL): string
	{
		if (empty($page)) {
			$page = $this->page;
		}
		$page = lcfirst($page);

		$language = $this->language;
		$ret = "";

		// if translations for this page is not loaded, try to load them.
		if (empty($this->translations[$page])) {
			$this->load_translation($page);
		}

		// if translations for this page is still not loaded throw an exception.
		if (empty($this->translations[$page])) {
			throw new Exception("Could not load translation for page $page");
		}

		$translations_this_page = $this->translations[$page];

		// try to get requested language
		if (array_key_exists($key, $translations_this_page[$language])) {
			$ret = $translations_this_page[$language][$key];
		} elseif (array_key_exists($key, $translations_this_page["no"])) {
			// norwegian as fallback 
			$ret = $translations_this_page["no"][$key];
		} else {
			log::message("Warning: page: $page does not have translations for $key", __FILE__, __LINE__);
			$ret = "";
		}
		return $ret;
	}

	public function get_url(string $page_name): string
	{
		$ret = Settings::get_instance()->get_baseurl() . "/";
		if ($this->language != "no") {
			$ret .= $this->language . "/";
		}
		return $ret . ($page_name === "mainpage" ? "" : $page_name);
	}
}
