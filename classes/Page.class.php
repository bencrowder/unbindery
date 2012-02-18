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

		$auth = Settings::getProtected('auth');
		$username = $auth->getUsername();
		if (isset($username)) { $options['username'] = $auth->getUsername(); }

		echo $twig->render("$page.html", $options);
	}
}

?>
