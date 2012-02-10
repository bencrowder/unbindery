<?php

class Dispatch {
	private $function;
	private $params;


	// Register dispatch
	// --------------------------------------------------

	public function register($function) {
		$this->function = $function;
	}


	// Init dispatch
	// --------------------------------------------------

	public function init($params) {
		$this->params = $params;
	}


	// Get next available object
	// --------------------------------------------------

	public function next() {
		if (isset($this->function) && isset($this->params)) {
			$object = call_user_func($this->function, $this->params);
		}

		if (isset($object)) {
			return $object;
		} else {
			return null;
		}
	}
}

?>
