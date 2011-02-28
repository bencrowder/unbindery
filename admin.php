<?php

include_once('include/config.php');
include_once('include/Alibaba.class.php');
include_once('Database.class.php');
include_once('User.class.php');

Alibaba::forceAuthentication();

$username = Alibaba::getUsername();
$user = new User($db, $username);

if (!$user->admin) {
	redirectToDashboard("", "You're not an administrator. Sorry.");
}

$message = stripslashes($_GET["message"]);
$error = stripslashes($_GET["error"]);

?>

<?php include('include/header.php'); ?>

<?php if ($message) { ?>
	<div id="message"><?php echo $message; ?></div>
<?php } ?>

<?php if ($error) { ?>
	<div id="error"><?php echo $error; ?></div>
<?php } ?>

	<div id="main" class="dashboard">
		<h2>Admin</h2>

		<div class="container">
			<div class="bigcol">
				<p>Users</p>
				<p>Projects</p>
				<p>History</p>
			</div>

			<div class="sidebar">
				<a href="<?php echo $SITEROOT; ?>/new_project" class="button">Create a new project</a>
			</div>
		</div>
	</div>

<?php include('include/footer.php'); ?>
