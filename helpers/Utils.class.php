<?php

class Utils {
	static public function GET($field) {
		return (array_key_exists($field, $_GET)) ? $_GET[$field] : false;
	}

	static public function POST($field) {
		return (array_key_exists($field, $_POST)) ? $_POST[$field] : false;
	}
}

?>
