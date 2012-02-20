<?php

class Page {
	static public function render($page, $options, $theme = 'core') {
		$cached = Settings::getProtected('theme_cached');

		if ($cached) {
			$twig_opts = array('cache' => 'themes/cache');
		} else {
			$twig_opts = array();
		}

		$loader = new Twig_Loader_Filesystem("themes/$theme", "themes/core");
		$twig = new Twig_Environment($loader, $twig_opts);

		$options['app_url'] = Settings::getProtected('app_url');
		$options['theme_root'] = $options['app_url'] . "/themes/$theme";
		$options['i18n'] = new I18n("translations");

		$options['message'] = (array_key_exists('ub_message', $_SESSION)) ? $_SESSION['ub_message'] : '';
		$options['error'] = (array_key_exists('ub_error', $_SESSION)) ? $_SESSION['ub_error'] : '';

		$auth = Settings::getProtected('auth');
		if ($auth->authenticated()) {
			$username = $auth->getUsername();
			if (isset($username)) { $options['username'] = $auth->getUsername(); }
		}

		echo $twig->render("$page.html", $options);

		// Now that we've displayed it, get rid of it
		unset($_SESSION['ub_message']);
		unset($_SESSION['ub_error']);
	}
}

?>
