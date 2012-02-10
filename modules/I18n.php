<?php

require_once 'lib/sfyaml/sfYaml.php';

class I18n {
	private $translations = array();


	// Constructor
	// --------------------------------------------------

	public function I18n($locale='en') {
		// Default to en.php if the locale doesn't exist
		$filename = "../translations/en.yaml";
		if (file_exists("../translations/$locale.yaml")) {
			$filename = "../translations/$locale.yaml";
		}

		// Load the YAML file
		$this->translations = sfYaml::load($filename);
	}


	// Translate
	// --------------------------------------------------

	public function translate($key) {
		if (isset($this->translations[$key])) {
			return $this->translations[$key];
		} else {
			// TODO: throw error
			return '[MISSING TRANSLATION]';
		}
	}
}

?>
