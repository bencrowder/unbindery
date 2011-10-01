<?php

include_once 'include/config.php';
include_once 'include/Alibaba.class.php';
include_once 'Database.class.php';
include_once 'unbindery.php';

if (Alibaba::authenticated()) {
	header("Location: $SITEROOT/dashboard");
}

$includes = "<script src='$SITEROOT/js/index.js' type='text/javascript'></script>\n";

include('include/header_index.php');

if (isset($_GET["message"])) { ?>
	<div id="message"><?php echo $_GET["message"]; ?></div>
<?php } ?>

<div class="container">
	<div id="logo_box"><img src="<?php echo $SITEROOT; ?>/img/logo_white.png" /></div>
	<div id="login_box">
		<h2>Login</h2>

		<form id="login_form" action="login/process" method="post" accept-charset="utf-8">
			<label>Username:</label>
			<input type="text" id="username" name="username" />

			<label>Password:</label>
			<input type="password" id="password" name="password" />

			<input type="submit" value="Log In" class="button" />

			<span id="signup">Sign up</span>
		</form>

		<form id="signup_form" style="display: none" action="signup" method="post" accept-charset="utf-8">
			<label>Email:</label>
			<input type="text" id="email_signup" name="email_signup" />

			<label>Username:</label>
			<input type="text" id="username_signup" name="username_signup" />

			<label>Password:</label>
			<input type="password" id="password_signup" name="password_signup" />

			<input type="submit" value="Sign Up" class="button" />

			<span id="login">Log in</span>
		</form>

		<div id="thankyou" style="display: none">
			We just sent you a confirmation link to your email address.
		</div>
	</div>
</div>

</body>
</html>
