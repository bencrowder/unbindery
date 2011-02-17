<!DOCTYPE HTML>
<html>
<head>
	<meta charset="UTF-8">
	<title>Unbindery</title>

	<link rel="stylesheet" href="<?php echo $SITEROOT; ?>/css/unbindery.css" type="text/css" media="screen" charset="utf-8" />

	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
	<script type="text/javascript" src="<?php echo $SITEROOT; ?>/lib/jquery.hotkeys.js"></script>

	<?php // this is for the upload page ?>
	<?php if ($includes != "") { echo $includes; } ?>

	<script type="text/javascript" src="<?php echo $SITEROOT; ?>/js/config.js"></script>
	<script type="text/javascript" src="<?php echo $SITEROOT; ?>/js/unbindery.js"></script>
</head>
<body>
	<div id="header_container">
		<div id="header">
			<div id="logo"><a href="<?php echo $SITEROOT; ?>/"><img src="<?php echo $SITEROOT; ?>/img/logo.jpg" alt="Unbindery" /></a></div>
			<ul id="nav">
				<?php if (Alibaba::authenticated()) { ?>
				<li>Logged in as <span class="username"><?php echo Alibaba::getUsername(); ?></span></li>
				<li><a href="<?php echo $SITEROOT; ?>/dashboard">Dashboard</a></li>
				<li><a href="<?php echo $SITEROOT; ?>/settings">Settings</a></li>
				<li><a href="<?php echo $SITEROOT; ?>/logout">Logout</a></li>
				<?php } else { ?>
				<li><a href="<?php echo $SITEROOT; ?>/signup">Sign Up</a></li>
				<li><a href="<?php echo $SITEROOT; ?>/login">Login</a></li>
				<?php } ?>
			</ul>
		</div>
	</div>
