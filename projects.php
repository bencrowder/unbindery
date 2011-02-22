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
	<?php if ($_GET["guidelines"] == "true") { ?>
		<h2>Project Guidelines</h2>
			
		<h3><?php echo $project->title; ?></h3>

		<?php echo $project->guidelines; ?>
	<?php } else { ?>
		<h2>Project Details</h2>

		<div class="bigcol">
			<div class="name"><?php echo $project->title; ?></div>
			<div class="desc"><?php echo $project->description; ?></div>

			<h4>Guidelines</h4>

			<?php echo $project->guidelines; ?>
		</div>

		<div class="sidebar">
			<span class="join button">Join this project</span>
		</div>
	<?php } // else (if guidelines != true) ?>
	</div>
</body>
</html>
