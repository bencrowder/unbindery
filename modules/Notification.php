<?php

class NotificationManager {
	static protected $functions = array();


	// Constructor: loads the specified queue
	// --------------------------------------------------

	public function __construct() {

	}


	// exists: determines if a function to be registered actually exists
	// --------------------------------------------------

	static private function exists($function) {
		$rtn = false;

		if (is_array($function) && count($function) > 1 && method_exists($function[0], $function[1])) {
			$rtn = true;
		} elseif (is_string($function) && trim($function) != '' && function_exists($function)) {
			$rtn = true;
		}

		return $rtn;
	}


	// send: delegates to the send function specified
	// --------------------------------------------------

	public function send($notification, $params) {
		$rtn = false;

		if (!array_key_exists('send', self::$functions)) {
			return $rtn;
		}

		if (self::$functions['send'] != null) {
			call_user_func(self::$functions['send'], $notification, $params);
			$rtn = true;
		}

		return $rtn;
	}


	// register: registers a function
	// --------------------------------------------------

	static public function register($action, $function) {
		$rtn = false;

		if (!self::exists($function)) {
			throw new Exception('Function does not exist.');
		} else {
			self::$functions[$action] = $function;
			$rtn = true;
		}

		return $rtn;
	}
}

?>
