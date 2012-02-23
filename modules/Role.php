<?php

class Role {
	static private $functions = array();

	static private $roles;


	// Register verify function
	// --------------------------------------------------

	static public function register($type, $function) {
		self::$functions[$type] = $function;
	}


	// Initialize
	// --------------------------------------------------

	static public function init($roleArray) {
		self::$roles = $roleArray;
	}


	// Verify function
	// --------------------------------------------------

	public function verify($params) {
		// Make sure the function's there, then call it with the parameters
		if (array_key_exists('verify', self::$functions)) {
			$response = call_user_func(self::$functions['verify'], $params);
		}

		return $response;
	}


	// Force clearance function (make sure they're verified and redirect if not)
	// --------------------------------------------------

	public function forceClearance($params) {
		// Make sure the function's there, then call it with the parameters
		if (array_key_exists('force_clearance', self::$functions)) {
			$response = call_user_func(self::$functions['force_clearance'], $params);
		}

		return $response;
	}


	// Get roles array
	// --------------------------------------------------

	static public function getRoles() {
		return self::$roles;
	}
}

?>
