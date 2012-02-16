<?php

// You need to require_once 'lib/sfyaml/sfYaml.php'; to use this module

class I18n {
	private $translations = array();


	// Constructor
	// --------------------------------------------------

	public function I18n($transdir, $locale='en') {
		if (file_exists("$transdir/$locale.yaml")) {
			$filename = "$transdir/$locale.yaml";

			// Load the YAML file
			$this->translations = sfYaml::load($filename);
		}
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


	// Translate shortcut
	// --------------------------------------------------

	public function t($key) {
		return $this->translate($key);
	}
}

?>
