<?php

include_once('include/config.php');
include_once('include/Alibaba.class.php');
include_once('Database.class.php');
include_once('Project.class.php');
include_once('unbindery.php');
include_once('utils.php');

Alibaba::forceAuthentication();

$username = Alibaba::getUsername();
$user = new User($db, $username);

$message = stripslashes($_GET["message"]);
$error = stripslashes($_GET["error"]);

$project_slug = $_GET["project_slug"];

$project = new Project($db, $project_slug);
if ($project->title == "") {
	redirectToDashboard("", "Error loading project.");
}

$project->loadStatus();

// find out if the user is admin or project owner so they can see the rest of the details
$role = $user->getRoleForProject($project_slug);
if ($role == "owner" || $user->admin) {
	$admin = true;
} else {
	$admin = false;
}

global $SYSTEMGUIDELINES;

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
		
		<div class="bigcol proj_guidelines">
			<div class="project_title"><?php echo $project->title; ?></div>

			<h2>Project-Specific Guidelines</h2>

			<?php echo $project->guidelines; ?>

			<h2>System-wide Guidelines</h2>

			<?php echo $SYSTEMGUIDELINES; ?>
		</div>
	<?php } else { ?>
		<h2>Project Details</h2>

		<div class="bigcol proj_details">
			<div class="project_title"><?php echo $project->title; ?></div>
			<div class="project_author">Author: <?php echo $project->author; ?></div>
			<div class="project_desc"><?php echo $project->language; ?>. <?php echo $project->description; ?></div>

			<ul class="project_dates">
				<li>Started: <label><?php echo $project->date_started; ?></label></li>
				<?php if ($project->status == "completed" || $project->status == "posted"): ?>
				<li>Completed: <label><?php echo $project->date_completed; ?></label></li>
				<li>Time Taken: <label><?php echo $project->days_spent; ?> days</label></li>
				<?php endif; ?>
			</ul>

			<?php if ($project->guidelines): ?>
				<a class="guidelines_link" target="_blank" href="<?php echo $project->slug; ?>/guidelines/">Project Guidelines</a>
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
			<div class="percentage big">
				<div class="percentage_container">
					<div class="percent" style="width: <?php echo $project->percentage * 2; ?>px;"></div>
				</div> 
				<p><?php echo round($project->percentage, 0) . "% (" . $project->completed . "/" . $project->total . ")";?></p>
			</div>

			<?php if (!$user->isMember($project_slug)) { ?>
			<a href="<?php echo $SITEROOT; ?>/projects/<?php echo $project->slug; ?>/join" class="right_button join button">Join this project</a>
			<?php } ?>

			<ul class="proofers">
			<h3>Proofers on This Project</h3>
			<?php 
			$proofers = $project->getProoferStats();
			foreach ($proofers as $proofer) { 
			?>
				<li>
					<div class="percentage">
						<div class="percentage_container">
							<div class="percent" style="width: <?php echo $proofer["percentage"]; ?>px;"></div>
						</div> 
						<p><?php echo round($proofer["percentage"], 0) . "% (" . $proofer["pages"] . " pages)";?></p>
					</div>
					<div class="username"><?php echo $proofer["username"]; ?></div>
				</li>
			<?php } ?>
			</ul>
		</div>
	<?php } // else (if guidelines != true) ?>
	</div>

<?php include('include/footer.php'); ?>
