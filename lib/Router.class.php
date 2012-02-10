<?php

/* Router                                 */
/* by Ben Crowder <ben.crowder@gmail.com> */
/* http://bencrowder.net/coding/router    */

class Router {
	public static function route($url, $routes, $defaultHandler) {
		$found = false;

		// go through each route and see if it matches; if so, execute the handler
		foreach ($routes as $pattern=>$handler) {
			if (preg_match($pattern, $url, $matches)) {
				call_user_func($handler, array_slice($matches, 1));
				$found = true;
				break;
			}
		}

		// call the default handler
		if (!$found) {
			call_user_func($defaultHandler);
		}
	}
}

?>
