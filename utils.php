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

?>
