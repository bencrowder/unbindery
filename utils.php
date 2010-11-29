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

?>
