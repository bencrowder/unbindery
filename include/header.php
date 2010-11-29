<!DOCTYPE HTML>
<html>
<head>
	<meta charset="UTF-8">
	<title>Unbindery</title>

	<link rel="stylesheet" href="<?php echo $SITEROOT; ?>/unbindery.css" type="text/css" media="screen" charset="utf-8" />

	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
	<script type="text/javascript" src="<?php echo $SITEROOT; ?>/lib/jquery.hotkeys.js"></script>
	<script type="text/javascript" src="<?php echo $SITEROOT; ?>/unbindery.js"></script>
</head>
<body>
	<div id="header_container">
		<div id="header">
			<div id="logo"><h1>Unbindery</h1></div>
			<?php if (Alibaba::authenticated()) { ?>
			<ul id="nav">
				<li>Logged in as <span class="username"><?php echo Alibaba::getUsername(); ?></span></li>
				<li><a href="<?php echo $SITEROOT; ?>/dashboard">Dashboard</a></li>
				<li><a href="<?php echo $SITEROOT; ?>/account">Account</a></li>
				<li><a href="<?php echo $SITEROOT; ?>/settings">Settings</a></li>
				<li><a href="<?php echo $SITEROOT; ?>/logout">Logout</a></li>
			</ul>
			<?php } ?>
		</div>
	</div>
