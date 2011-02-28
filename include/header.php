<?php include_once('header_shared.php'); ?>

<body>
	<div id="header_container">
		<div id="header">
			<div id="logo"><a href="<?php echo $SITEROOT; ?>/"><img src="<?php echo $SITEROOT; ?>/img/logo.jpg" alt="Unbindery" /></a></div>
			<ul id="nav">
				<?php if (Alibaba::authenticated()) { 
					$username = Alibaba::getUsername();
					$user = new User($db, $username);
				?>
				<li>Logged in as <span class="username"><?php echo $username; ?></span></li>
				<li><a href="<?php echo $SITEROOT; ?>/dashboard">Dashboard</a></li>
				<li><a href="<?php echo $SITEROOT; ?>/projects">Projects</a></li>
					<?php if ($user->admin): ?>
				<li><a href="<?php echo $SITEROOT; ?>/admin">Admin</a></li>
					<?php endif; ?>
				<li><a href="<?php echo $SITEROOT; ?>/settings">Settings</a></li>
				<li><a href="<?php echo $SITEROOT; ?>/logout">Logout</a></li>
				<?php } else { ?>
				<li><a href="<?php echo $SITEROOT; ?>/signup">Sign Up</a></li>
				<li><a href="<?php echo $SITEROOT; ?>/login">Login</a></li>
				<?php } ?>
			</ul>
		</div>
	</div>
