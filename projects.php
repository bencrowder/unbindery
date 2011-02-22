<?php

include_once('include/config.php');
include_once('include/Alibaba.class.php');
include_once('Database.class.php');
include_once('Project.class.php');
include_once('unbindery.php');

Alibaba::forceAuthentication();

$username = Alibaba::getUsername();
$user = new User($db, $username);

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

		<div class="bigcol proj_details">
			<div class="project_title"><?php echo $project->title; ?></div>
			<div class="project_author">By <?php echo $project->author; ?></div>
			<div class="project_desc"><?php echo $project->description; ?></div>

			<h4>Guidelines</h4>

			<?php echo $project->guidelines; ?>
		</div>

		<div class="sidebar">
			<?php if (!$user->isMember($project_slug)) { ?>
			<a href="<?php echo $SITEROOT; ?>/projects/<?php echo $project->slug; ?>/join" class="join button">Join this project</a>
			<?php } ?>
		</div>
	<?php } // else (if guidelines != true) ?>
	</div>
</body>
</html>
