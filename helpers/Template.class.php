<?php

class Template {
	static public function render($page, $options, $theme = 'core') {
		$cached = Settings::getProtected('theme_cached');

		// If there's a system-wide theme in config.yaml, use it as the default instead
		$settingsTheme = Settings::getProtected('theme');
		if ($settingsTheme != 'core' && $theme == 'core') {
			$theme = $settingsTheme;
		}

		if ($cached) {
			$twig_opts = array('cache' => '../templates/cache');
		} else {
			$twig_opts = array();
		}

		$loader = new Twig_Loader_Filesystem(array("../templates/$theme", "../templates/core"));
		$twig = new Twig_Environment($loader, $twig_opts);

		$options['title'] = Settings::getProtected('title');
		$options['app_url'] = Settings::getProtected('app_url');
		$options['google_analytics'] = Settings::getProtected('google_analytics');
		$options['theme_root'] = $options['app_url'] . "/themes/$theme";
		$options['i18n'] = new I18n("../translations");

		$options['message'] = Utils::SESSION('ub_message');
		$options['error'] = Utils::SESSION('ub_error');

		$auth = Settings::getProtected('auth');
		if ($auth->authenticated()) {
			$username = $auth->getUsername();
			if (isset($username)) { $options['username'] = $auth->getUsername(); }
		}

		// Prepare the methods they want
		if (array_key_exists('registered_methods', $options)) {
			// TODO: get user token
			$userToken = 'foo';
			$appName = 'unbindery';
			$privateKey = Settings::getProtected('private_key');
			$devKeys = Settings::getProtected('devkeys');
			$devKey = $devKeys['unbindery'];
			
			$options['methods'] = array();
			foreach ($options['registered_methods'] as $method) {
				// Create the signature hash for each method we'll use on the page in Javascript
				$options['methods'][$method] = array("name" => $method, "value" => md5($method . $userToken . $appName . $privateKey . $devKey));
			}
		}

		echo $twig->render("$page.html", $options);

		// Now that we've displayed it, get rid of it
		unset($_SESSION['ub_message']);
		unset($_SESSION['ub_error']);
	}
}

?>
