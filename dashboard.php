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

		<div class="bigcol">
			<h3 id="current">Current</h3>
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
			<h3>Stats</h3>
			<ul>
				<li>Score: <span class="stat">590</span></li>
				<li>Items proofed this week: <span class="stat">3</span></li>
				<li>Items proofed all time: <span class="stat">742</span></li>
			</ul>

			<h3>Current Projects</h3>
			<ul>
				<?php 
				$projects = $user->getProjects();
				foreach ($projects as $project) {
					$projectlink = $SITEROOT . '/projects/' . $project["slug"];
					$getitemlink = $SITEROOT . '/get_item/' . $project["slug"];
					$percentage = round($project["completed"] / $project["total"] * 100, 0);
				?>
				<li>
					<a href="<?php echo $projectlink; ?>"><?php echo $project["title"]; ?></a>
					<p>Owner: <?php echo $project["owner"]; ?></p>
					<p><?php echo $percentage . "% (" . $project["completed"] . "/" . $project["total"] . ")";?></p>
					<p><?php if (!in_array($project["slug"], $projectlist)): ?><span class="button smallbutton getnewitem" data-project-slug="<?php echo $project["slug"]; ?>">Get new item</span><?php endif; ?></p>
				</li>
				<?php } ?>
			</ul>

			<h3>History</h3>
			<ul>
				<li>
					<p>Page 159</p>
					<p><a href="projects/hck">The Life of Heber C. Kimball</a></p>
					<p>10 Dec 2010</p>
				</li>
			</ul>
		</div>

		</div>
	</div>
</body>
</html>
