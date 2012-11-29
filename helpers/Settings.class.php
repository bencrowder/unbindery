<?php

abstract class Settings
{
	static private $protected = array();	// For database info, etc.
	static private $public = array();		// Public

	public static function getProtected($key) {
		return isset(self::$protected[$key]) ? self::$protected[$key] : false;
	}

	public static function getPublic($key) {
		return isset(self::$public[$key]) ? self::$public[$key] : false;
	}

	public static function setProtected($key, $value) {
		self::$protected[$key] = $value;
	}

	public static function setPublic($key, $value) {
		self::$public[$key] = $value;
	}

	// $this->key returns public->key
	public function __get($key) {
		return isset(self::$public[$key]) ? self::$public[$key] : false;
	}

	public function __isset($key) {
		return isset(self::$public[$key]);
	}

	public static function loadFromArray($array) {
		foreach ($array as $key => $value) {
			self::setProtected($key, $value);
		}
	}

	public static function loadFromYAML() {
		require_once '../lib/sfyaml/sfYaml.php';

		if (!file_exists("../config.yaml")) {
			echo "config.yaml doesn't exist. Exiting now.";
			die(-1);
		}

		$array = sfYaml::load("../config.yaml");

		foreach ($array as $key => $value) {
			self::setProtected($key, $value);
		}
	}
}

?>
