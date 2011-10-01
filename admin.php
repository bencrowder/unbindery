<?php

include_once('include/config.php');
include_once('include/Alibaba.class.php');
include_once('Database.class.php');
include_once('User.class.php');
include_once('Server.class.php');

Alibaba::forceAuthentication();

$username = Alibaba::getUsername();
$user = new User($db, $username);

if (!$user->admin) {
	redirectToDashboard("", "You're not an administrator. Sorry.");
}

$server = new Server($db);

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
				<h4>Current Assignments</h4>
				<ul>
				<?php
					$assignments = $server->getCurrentAssignments();
					foreach ($assignments as $assignment) {
						echo "<li>{$assignment["username"]} ({$assignment["item_id"]}, {$assignment["project_id"]}), assigned {$assignment["date_assigned"]}, deadline {$assignment["deadline"]}</li>";
					}
				?>
				</ul>

				<h4>Projects</h4>
				<ul>
				<?php
					$projects = $server->getProjects();
					foreach ($projects as $project) {
						echo "<li><a href='$SITEROOT/projects/{$project["slug"]}'>{$project["title"]}</a></li>\n";
					}
				?>
				</ul>

				<h4>Users</h4>

				<h4>History</h4>
			</div>

			<div class="sidebar">
				<a href="<?php echo $SITEROOT; ?>/admin/new_project" class="button">Create a new project</a>
			</div>
		</div>
	</div>

<?php include('include/footer.php'); ?>
