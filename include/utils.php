<?php

function redirectToDashboard($message = "", $error = "") {
	global $SITEROOT;

	$locstr = "$SITEROOT/dashboard";

	if ($message || $error) { $locstr .= "?"; }

	if ($message) {
		$locstr .= "message=" . urlencode($message);
	}
	if ($error) {
		if ($message) { $locstr .= "&"; }
		$locstr .= "error=" . urlencode($error);
	}

	header("Location: $locstr");
}

function escapebrackets($text) {
	$text = str_replace("<", "&lt;", $text);
	$text = str_replace(">", "&gt;", $text);
	return $text;
}

function renderPage($page, $options, $cached = false, $theme = 'core') {
	if ($cached) {
		$twig_opts = array('cache' => 'themes/cache');
	} else {
		$twig_opts = array();
	}

	$loader = new Twig_Loader_Filesystem("themes/$theme", "themes/core");
	$twig = new Twig_Environment($loader, $twig_opts);

	echo $twig->render("$page.html", $options);
}

?>
