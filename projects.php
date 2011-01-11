<?php

include_once('include/config.php');
include_once('include/Alibaba.class.php');
include_once('Database.class.php');
include_once('Project.class.php');
include_once('unbindery.php');

Alibaba::forceAuthentication();

$username = Alibaba::getUsername();

$message = stripslashes($_GET["message"]);
$error = stripslashes($_GET["error"]);

$project_slug = $_GET["project_slug"];

$project = new Project($db, $project_slug);

?>

<?php include('include/header.php'); ?>

<?php if ($message) { ?>
	<div id="message"><?php echo $message; ?></div>
<?php } ?>

<?php if ($error) { ?>
	<div id="error"><?php echo $error; ?></div>
<?php } ?>

	<div id="main">
		<h2><?php echo $project->title; ?></h2>

		<div class="bigcol">
			<h3>Project Details</h3>
			<ul>
				<li>Title: <?php echo $project->title; ?></li>
				<li>Slug: <?php echo $project->slug; ?></li>
				<li>Description: <?php echo $project->description; ?></li>
				<li>Owner: <?php echo $project->owner; ?></li>
				<li>Status: <?php echo $project->status; ?></li>
				<li>Deadline: <?php echo $project->deadline_days; ?> days</li>
				<li># proofs: <?php echo $project->num_proofs; ?></li>
			</ul>

			<h3>Guidelines</h3>

			<?php echo $project->guidelines; ?>

			<h3>Intro Email</h3>

			<?php echo $project->intro_email; ?>

			<h3>Project Items</h3>
			...
		</div>

		<div class="sidebar">
			<a href="edit/">Edit this project</a>
		</div>
	</div>
</body>
</html>
