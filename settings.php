<?php

include_once('include/config.php');
include_once('include/Alibaba.class.php');
include_once('Database.class.php');
include_once('User.class.php');
include_once('utils.php');

Alibaba::forceAuthentication();

// get and load the user's data
$username = Alibaba::getUsername(); 
$user = new User($db, $username);

?>

<?php include('include/header.php'); ?>

<?php if (isset($_GET["message"])) { ?>
	<div id="message"><?php echo $_GET["message"]; ?></div>
<?php } ?>

	<div id="main">
		<h2>Settings</h2>

		<form id="user_settings" action="save_settings.php" method="post">
			<h3>Basic Information</h3>

			<label>Name</label>
			<input type="text" id="user_name" name="user_name" value="<?php echo $user->name; ?>" />

			<label>Email address</label>
			<input type="text" id="user_email" name="user_email" value="<?php echo $user->email; ?>" />

			<h3>Change Password</h3>

			<label>Old password</label>
			<input type="password" id="user_oldpassword" name="user_oldpassword" />
			
			<label>New password</label>
			<input type="password" id="user_newpassword1" name="user_newpassword1" />

			<label>New password (again, for good measure)</label>
			<input type="password" id="user_newpassword2" name="user_newpassword2" />

			<input type="submit" value="Save Changes" class="button" />

			<input type="hidden" name="username" id="username" value="<?php echo $username; ?>" />
		</form>
	</div>

<?php include('include/footer.php'); ?>
