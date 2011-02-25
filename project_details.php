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
$project->loadStatus();

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
			<div class="project_author">Author: <?php echo $project->author; ?></div>
			<div class="project_desc"><?php echo $project->language; ?>. <?php echo $project->description; ?></div>

			<?php if ($project->guidelines): ?>
				<h4>Guidelines</h4>
				<?php echo $project->guidelines; ?>
			<?php endif; ?>

			<?php if ($project->thumbnails): 
				$thumbnails = explode(",", $project->thumbnails);
			?>
				<h4>Preview</h4>
				<ul class="thumbnails">
				<?php foreach ($thumbnails as $thumbnail) { ?>
					<li><img src="<?php echo $SITEROOT; ?>/images/<?php echo $project->slug; ?>/thumbnails/<?php echo $thumbnail; ?>" /></li>
				<?php } ?>
				</ul>
			<?php endif; ?>
		</div>

		<div class="sidebar proj_details">
			<div class="percentage">
				<div class="percentage_container">
					<div class="percent" style="width: <?php echo $project->percentage * 2; ?>px;"></div>
				</div> 
				<p><?php echo round($project->percentage, 0) . "% (" . $project->completed . "/" . $project->total . ")";?></p>
			</div>

			<?php if (!$user->isMember($project_slug)) { ?>
			<a href="<?php echo $SITEROOT; ?>/projects/<?php echo $project->slug; ?>/join" class="right_button join button">Join this project</a>
			<?php } ?>
		</div>
	<?php } // else (if guidelines != true) ?>
	</div>

<?php include('include/footer.php'); ?>
