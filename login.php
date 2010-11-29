<?php

include("include/config.php");
include_once("include/Alibaba.class.php");

include("include/header.php");
?>

<?php if (isset($_GET["message"])) { ?>
	<div id="message" style="background: #fee; padding: 5px; border: solid 1px #f00;"><?php echo $_GET["message"]; ?></div>
<?php } ?>

	<div id="main">
		<h1>Login</h1>
		<form action="process_login.php" method="post" accept-charset="utf-8">
		<label>Username:</label>
		<input type="text" id="username" name="username" />
		<br/>
		<label>Password:</label>
		<input type="password" id="password" name="password" />
		<br/>
		<input type="submit" value="Log In" />
		</form>
	</div>
</body>
</html>
