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
}

?>
