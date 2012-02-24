<?php
/* Router												*/
/* by Chad Hansen <chadgh@gmail.com>					*/
/* Originally by Ben Crowder <ben.crowder@gmail.com>	*/

class Router {
	private $defaultHandler;
	private $debug;

	public function Router($defaultHandler='', $debug=false) {
		$this->setDefaultHandler($defaultHandler);
		$this->setDebug($debug);
	}

	public function route($routes, $url='') {
		$found = false;

		$params = array($_SERVER['QUERY_STRING']);

		if (trim($url) == '') {
			$url = (array_key_exists('REQUEST_URI', $_SERVER)) ?$_SERVER['REQUEST_URI'] : $_SERVER['SCRIPT_NAME'];
			$parts = explode('?', $url, 2);
			$url = trim($parts[0]);
		}

		// go through each route and see if it matches; if so, execute the handler
		foreach ($routes as $pattern=>$handler) {
			if (preg_match($pattern, $url, $matches)) {
				$params = array_merge(array_splice($matches, 1), $params);

				if (is_array($handler)) {
					if (count($handler) > 1 && is_object($handler[0])) {
						call_user_func($handler, $params);
					} 
					elseif (count($handler) > 1 && is_string($handler[0])) {
						$class = null;
						if (count($handler) > 2) {
							$class = new $handler[0]($handler[2]);
						} else {
							$class = new $handler[0]();
						}
						call_user_func(array($class, $handler[1]), $params);
					} else {
						throw new Exception("Can't handle handler: " . json_encode($handler), 500);
					}
				} else { // static function call or global function call
					call_user_func($handler, $params);
				}
				$found = true;
				break;
			}
		}

		// call the default handler
		if (!$found) {
			call_user_func($this->getDefaultHandler(), array_merge((array)$url, $params));
		}
		return 1;
	} 

	/* Getters and Setters */
	public function getDefaultHandler() {
		return $this->defaultHandler;
	}

	public function setDefaultHandler($handler) {
		$this->defaultHandler = $handler;
	}

	public function getDebug() {
		return $this->debug;
	}

	public function setDebug($debug) {
		$this->debug = $debug;
	}
}
?>
