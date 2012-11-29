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
		} else {
			error_log("Failed to load $transdir/$locale.yaml");
		}
	}


	// Translate
	// --------------------------------------------------

	public function translate($key, $vars = array()) {
		if (isset($this->translations[$key])) {
			return self::replaceVariables($this->translations[$key], $vars);
		} else {
			// TODO: throw error
			return '[MISSING TRANSLATION]';
		}
	}


	// Translate shortcut
	// --------------------------------------------------

	public function t($key, $vars = array()) {
		return $this->translate($key, $vars);
	}


	// --------------------------------------------------
	// Variable substitution helper function

	static public function replaceVariables($string, $vars) {
		// Go through the template and swap out variables

		$matches = array();
		preg_match_all("/{{ (.+?) }}/", $string, $matches);

		foreach ($matches[1] as $match) {
			if (array_key_exists($match, $vars)) {
				$string = preg_replace("/{{ $match }}/", $vars[$match], $string);
			}
		}

		return $string;
	}
}

?>
