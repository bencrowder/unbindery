<?php

class Utils {
	static public function GET($field) {
		return (array_key_exists($field, $_GET)) ? $_GET[$field] : false;
	}

	static public function POST($field) {
		return (array_key_exists($field, $_POST)) ? $_POST[$field] : false;
	}

	static public function REQUEST($field) {
		return (array_key_exists($field, $_REQUEST)) ? $_REQUEST[$field] : false;
	}

	static public function SESSION($field) {
		return (array_key_exists($field, $_SESSION)) ? $_SESSION[$field] : false;
	}

	static public function redirectToDashboard($message, $error) {
		$app_url = Settings::getProtected('app_url');

		if ($message != '') $_SESSION['ub_message'] = trim($message);
		if ($error != '') $_SESSION['ub_error'] = trim($error);

		header("Location: $app_url");
	}


	// --------------------------------------------------
	// Helper function to parse project page type

	static public function getProjectType($args) {
		if ($args[0] == 'users') {
			return 'user';
		} else {
			return 'system';
		}
	}


	// --------------------------------------------------
	// Helper function to parse return format type

	static public function getFormat($args, $systemIndex, $userIndex) {
		$projectType = self::getProjectType($args);
		$formatIndex = ($projectType == 'system') ? $systemIndex : $userIndex;
		return $args[$formatIndex] != '' ? $args[$formatIndex] : 'html';
	}
}

?>
