<!DOCTYPE HTML>
<html>
<head>
	<meta charset="utf-8">
	<title>Unbindery</title>

	<link rel="shortcut icon" href="<?php echo $SITEROOT; ?>/img/favicon.png" />

	<link rel="stylesheet" href="<?php echo $SITEROOT; ?>/css/unbindery.css" type="text/css" media="screen" charset="utf-8" />

	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.min.js"></script>
	<script type="text/javascript" src="<?php echo $SITEROOT; ?>/lib/jquery.hotkeys.js"></script>

	<?php // this is for the upload page ?>
	<?php if (isset($includes) && $includes != "") { echo $includes; } ?>

	<script type="text/javascript" src="<?php echo $SITEROOT; ?>/js/config.js"></script>
	<script type="text/javascript" src="<?php echo $SITEROOT; ?>/js/unbindery.js"></script>
</head>
