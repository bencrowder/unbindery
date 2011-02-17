<?php

include_once('include/config.php');
include_once('include/Alibaba.class.php');
include_once('Database.class.php');
include_once('unbindery.php');

if (Alibaba::authenticated()) {
	header("Location: $SITEROOT/dashboard/");
}

?>

<?php include('include/header_index.php'); ?>

<?php if (isset($_GET["message"])) { ?>
	<div id="message" style="background: #fee; padding: 5px; border: solid 1px #f00;"><?php echo $_GET["message"]; ?></div>
<?php } ?>

<div class="container">
	<div id="logo_box"><img src="<?php echo $SITEROOT; ?>/img/logo_white.png" /></div>
	<div id="login_box">
		<h2>Login</h2>

		<form action="process_login.php" method="post" accept-charset="utf-8">
			<label>Username:</label>
			<input type="text" id="username" name="username" />

			<label>Password:</label>
			<input type="password" id="password" name="password" />

			<input type="submit" value="Log In" class="button" />

			<a href="signup/">Sign up</a>
		</form>
	</div>
</div>

</body>
</html>
