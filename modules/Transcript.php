<?php

require_once 'Event.php';

class Transcript {
	static private $functions = array();
	private $text;


	// Register load/save functions
	// --------------------------------------------------

	static public function register($type, $function) {
		self::$functions[$type] = $function;
	}


	// Get the text
	// --------------------------------------------------

	public function getText() {
		return $this->text;
	}


	// Set the text
	// --------------------------------------------------

	public function setText($text) {
		$this->text = $text;
	}


	// Load function
	// --------------------------------------------------

	public function load($params) {
		// Make sure the function's there, then call it with the parameters
		if (array_key_exists('load', self::$functions)) {
			$response = call_user_func(self::$functions['load'], $params);
		}

		// Load the text
		$this->text = $response;

		// And trigger the load transcript event
		$eventManager = new EventManager();
		$eventManager->trigger('load', 'transcript', array('transcript' => $this));
	}


	// Save function
	// --------------------------------------------------

	public function save($params) {
		// Trigger the save transcript event
		$eventManager = new EventManager();
		$eventManager->trigger('save', 'transcript', array('transcript' => $this));

		// Make sure the function's there, then call it with the parameters
		if (array_key_exists('save', self::$functions)) {
			$response = call_user_func(self::$functions['save'], $params);
		}

		return $response;
	}


	// Diff (TODO)
	// --------------------------------------------------

	static public function diff($transcripts) {
		$str = '';

		foreach ($transcripts as $transcript) {
			// TODO: finish this
		}

		return $str;
	}


	// Collate transcripts
	// --------------------------------------------------

	static public function collate($transcripts, $delimiter='') {
		$text = array();
		foreach ($transcripts as $transcript) {
			array_push($text, $transcript->getText());
		}

		return implode($delimiter, $text);
	}
}

?>
