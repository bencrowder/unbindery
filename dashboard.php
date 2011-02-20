<?php

include_once('include/config.php');
include_once('include/Alibaba.class.php');
include_once('Database.class.php');
include_once('Project.class.php');
include_once('Item.class.php');
include_once('User.class.php');

Alibaba::forceAuthentication();

$username = Alibaba::getUsername();

$message = stripslashes($_GET["message"]);
$error = stripslashes($_GET["error"]);

// list of projects the user already has assignments for
$projectlist = array();

?>

<?php include('include/header.php'); ?>

<?php if ($message) { ?>
	<div id="message"><?php echo $message; ?></div>
<?php } ?>

<?php if ($error) { ?>
	<div id="error"><?php echo $error; ?></div>
<?php } ?>

	<div id="main">
		<h2>Dashboard</h2>

		<div class="container">
			<div class="bigcol">
				<h3 id="current">Current Assignments</h3>
				<ul id="current_items">
					<?php 
					$user = new User($db, $username);
					$items = $user->getAssignments();
					foreach ($items as $item) {
						$editlink = $SITEROOT . '/edit/' . $item["project_slug"] . '/' . $item["item_id"];
						$projectlist[] = $item["project_slug"];
					?>
					<li>
						<div class="proof_button"><a href="<?php echo $editlink; ?>" class="button">Proof</a></div>
						<div class="item_title"><a href="<?php echo $editlink; ?>"><?php echo $item["item_title"]; ?></a> <span class="deadline">Due <?php echo $item["deadline"]; ?></span></div>
						<div class="project_title">Project: <?php echo $item["project_title"]; ?></div>
					</li>
					<?php } ?>
					<?php 
					$projects = $user->getProjects();
					foreach ($projects as $project) {
						if (!in_array($project["slug"], $projectlist)):
							$projectlink = $SITEROOT . '/projects/' . $project["slug"];
							$getitemlink = $SITEROOT . '/get_item/' . $project["slug"];
							$percentage = round($project["completed"] / $project["total"] * 100, 0);
					?>
					<li>
						<div class="proof_button"><span class="button getnewitem" data-project-slug="<?php echo $project["slug"]; ?>">Get new item</span></div>

						<div class="item_title"><a href="<?php echo $projectlink; ?>"><?php echo $project["title"]; ?></a></div>
						<div class="project_title">Owner: <?php echo $project["owner"]; ?></div>
					</li>
					<?php endif; ?>
					<?php } ?>
					<li></li>
				</ul>
			</div>

			<div class="sidebar">
				<?php $user->getStats(); ?>
				<ul id="stats">
					<li><label>Score</label> <span class="stat"><?php echo $user->score; ?></span></li>
					<li><label>Proofed</label> <span class="stat"><?php echo $user->proofed; ?></span></li>
					<li><label>Proofed This Past Week</label> <span class="stat"><?php echo $user->proofed_past_week; ?></span></li>
				</ul>
			</div>
		</div>

		<div class="half">
			<h3>Current Projects</h3>
			<ul class="list">
				<?php 
				$projects = $user->getProjects();
				foreach ($projects as $project) {
					$projectlink = $SITEROOT . '/projects/' . $project["slug"];
					$getitemlink = $SITEROOT . '/get_item/' . $project["slug"];
					$percentage = round($project["completed"] / $project["total"] * 100, 0);
				?>
				<li>
					<div class="percentage">
						<div class="percentage_container">
							<div class="percent" style="width: <?php echo $percentage; ?>px;"></div>
						</div> 
						<p><?php echo $percentage . "% (" . $project["completed"] . "/" . $project["total"] . ")";?></p>
					</div>

					<div class="project_title"><a href="<?php echo $projectlink; ?>"><?php echo $project["title"]; ?></a></div>
				</li>
				<?php } ?>
			</ul>
		</div>

		<div class="half">
			<h3>Recent History</h3>
			<ul class="list">
				<?php 
				$history = $user->getHistory();
				foreach ($history as $item) {
					$editlink = "$SITEROOT/edit/" . $item["project_slug"] . "/" . $item["item_id"];
				?>
				<li>
					<?php echo $item["date_completed"]; ?>: <a href='<?php echo $editlink; ?>'><?php echo $item["item_title"]; ?></a>
					<div class="history_projecttitle">Project: <?php echo $item["project_title"]; ?></div>
				</li>
				<?php } ?>
			</ul>
		</div>
	</div>
</body>
</html>
