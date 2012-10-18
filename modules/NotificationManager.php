<?php

class NotificationManager {
	static protected $functions = array();
	static private $eventManager;


	// Constructor: nothing
	// --------------------------------------------------

	public function __construct() {

	}


	// Register notifications with the events 
	// --------------------------------------------------

	static public function registerNotifications($notifications, $handler) {
		foreach ($notifications as $notification) {
			self::$eventManager->register($notification, "notification", $handler);
		}
	}


	// Set event manager
	// --------------------------------------------------

	static public function setEventManager($eventManager) {
		self::$eventManager = $eventManager;
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


	// trigger: delegates to the trigger function specified
	// --------------------------------------------------

	static public function trigger($notification, $params) {
		self::$eventManager->trigger($notification, "notification", array_merge(array('notification' => $notification), $params));

		/*
		$rtn = false;

		if (!array_key_exists('trigger', self::$functions)) {
			return $rtn;
		}

		if (self::$functions['trigger'] != null) {
			call_user_func(self::$functions['trigger'], $notification, $params);
			$rtn = true;
		}

		return $rtn;
		 */
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
