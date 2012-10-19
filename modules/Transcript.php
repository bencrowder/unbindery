<?php

require_once 'EventManager.php';

class Transcript {
	static private $functions = array();
	static private $eventManager;
	private $text;


	// Set event manager
	// --------------------------------------------------

	static public function setEventManager($eventManager) {
		self::$eventManager = $eventManager;
	}


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
		self::$eventManager->trigger('load', 'transcript', array('transcript' => $this));
	}


	// Save function
	// --------------------------------------------------

	public function save($params) {
		// Trigger the save transcript event
		self::$eventManager->trigger('save', 'transcript', array('transcript' => $this));

		// Populate $params['transcript'] with this so we can access it in the handler
		$params['transcript'] = $this;

		// Make sure the function's there, then call it with the parameters
		if (array_key_exists('save', self::$functions)) {
			$response = call_user_func(self::$functions['save'], $params);
		}

		return $response;
	}


	// Diff function
	// --------------------------------------------------

	static public function diff($params) {
		// Trigger the diff transcript event
		self::$eventManager->trigger('diff', 'transcript');

		// Expects $params['transcripts'] to have an array of transcripts

		// Make sure the function's there, then call it with the parameters
		if (array_key_exists('diff', self::$functions)) {
			$response = call_user_func(self::$functions['diff'], $params);
		}

		return $response;
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
