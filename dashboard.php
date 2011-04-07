<?php

include_once('include/config.php');
include_once('include/Alibaba.class.php');
include_once('Database.class.php');
include_once('Project.class.php');
include_once('Server.class.php');
include_once('Item.class.php');
include_once('User.class.php');

Alibaba::forceAuthentication();

$username = Alibaba::getUsername();
$user = new User($db, $username);

$server = new Server($db);

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

	<div id="main" class="dashboard">
		<h2>Dashboard</h2>

		<div class="container">
			<div class="bigcol">
				<h3 class="action_header">Current Assignments</h3>
				<ul class="action_list">
					<?php 
					$items = $user->getAssignments();
					$projects = $user->getProjects();

					if (count($items) == 0 && count($projects) == 0) { ?>
						<li class="blankslate">Welcome. <a href="<?php echo $SITEROOT; ?>/projects">Join a project</a> to get started proofing.</li>
					<?php }

					foreach ($items as $item) {
						$editlink = $SITEROOT . '/edit/' . $item["project_slug"] . '/' . $item["item_id"];
						$projectlist[] = $item["project_slug"];

						$days_left = $item["days_left"];
						$deadline = $item["deadline"];
						if ($days_left <= 2 && $days_left >= 0) {
							$deadlineclass = " impending";
							$deadline = "in $days_left day";
							if ($days_left != 1) { $deadline .= "s"; }
						} else if ($days_left < 0) {
							$deadlineclass = " overdue";
							$deadline = ($days_left * -1) . " days ago";
						} else {
							$deadlineclass = "";
						}
					?>
					<li>
						<div class="right_button"><a href="<?php echo $editlink; ?>" class="button">Proof</a></div>
						<div class="title"><a href="<?php echo $editlink; ?>"><?php echo $item["item_title"]; ?></a> <span class="deadline<?php echo $deadlineclass; ?>">Due <?php echo $deadline; ?></span></div>
						<div class="sub">Project: <?php echo $item["project_title"]; ?></div>
					</li>
					<?php } ?>
					<?php 
					foreach ($projects as $project) {
						if (!in_array($project["slug"], $projectlist) && ($project["available_pages"] > 0)):
							$projectlink = $SITEROOT . '/projects/' . $project["slug"];
							$percentage = round($project["completed"] / $project["total"] * 100, 0);
					?>
					<li>
						<div class="right_button"><span class="button getnewitem" data-project-slug="<?php echo $project["slug"]; ?>">Get new page</span></div>

						<div class="title"><a href="<?php echo $projectlink; ?>"><?php echo $project["title"]; ?></a></div>
						<div class="sub">By <?php echo $project["author"]; ?></div>
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

		<div id="lower_dash">
			<div class="group">
				<h3>Recent History</h3>
				<ul class="list">
					<?php 
					$history = $user->getHistory();
					if (count($history) == 0) { ?>
					<li>Nothing so far -- you must be new here. :)</li>
					<?php }

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

			<div class="group">
				<h3>Current Projects</h3>
				<ul class="list">
					<?php 
					$projects = $user->getProjects();
					if (count($projects) == 0) { ?>
					<li>No current projects.</li>
					<?php
					}

					foreach ($projects as $project) {
						$projectlink = $SITEROOT . '/projects/' . $project["slug"];
						$percentage = round($project["completed"] / $project["total"] * 100, 0);
						$proof_percentage = round($project["proof_percentage"]);
					?>
					<li>
						<div class="percentage">
							<div class="percentage_container">
								<div class="percent" style="width: <?php echo $percentage; ?>px;"></div>
								<div class="percent_proofs" style="width: <?php echo $proof_percentage; ?>px;"></div>
							</div> 
							<p><?php echo $percentage . "% (" . $project["available_pages"] . " left)";?></p>
						</div>

						<div class="project_title"><a href="<?php echo $projectlink; ?>"><?php echo $project["title"]; ?></a></div>
					</li>
					<?php } ?>
				</ul>
			</div>

			<div class="group leaderboard">
				<h3>Top Proofers</h3>
				<ol id="stats">
				<?php
					$users = $server->getTopUsers();
					foreach ($users as $user) { ?>
					<li><label><?php echo $user["username"]; ?></label> <span class="stat"><?php echo $user["score"]; ?></span></li>
				<?php } ?>
				</ol>
			</div>
		</div>
	</div>

<?php include('include/footer.php'); ?>
