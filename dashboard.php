<?php

include_once('include/config.php');
include_once('include/Alibaba.class.php');
include_once('Database.class.php');
include_once('Project.class.php');
include_once('Item.class.php');
include_once('unbindery.php');

Alibaba::forceAuthentication();

$username = Alibaba::getUsername();

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

	<div id="main">
		<h2>Dashboard</h2>

		<div class="bigcol">
			<h3>Current Items</h3>
			<table cellpadding="0" cellspacing="0">
				<tr>
					<th>Item</th>
					<th>Project</th>
					<th>Deadline</th>
					<th></th>
				</tr>
				<?php 
				$user = new User($db, $username);
				$items = $user->getAssignments();
				foreach ($items as $item) {
					$editlink = $SITEROOT . '/edit/' . $item["project_slug"] . '/' . $item["item_id"];
					$projectlink = $SITEROOT . '/projects/' . $item["project_slug"];
				?>
				<tr>
					<td><a href="<?php echo $editlink; ?>"><?php echo $item["item_title"]; ?></a></td>
					<td><?php echo $item["project_title"]; ?></td>
					<td><?php echo $item["deadline"]; ?></td>
					<td><a href="<?php echo $editlink; ?>" class="button smallbutton">Proof</a></td>
				</tr>
				<?php } ?>
			</table>

			<h3>Current Projects</h3>
			<table cellpadding="0" cellspacing="0">
				<tr>
					<th>Project</th>
					<th>Owner</th>
					<th>Status</th>
					<th></th>
				</tr>
				<?php 
				$projects = $user->getProjects();
				foreach ($projects as $project) {
					$projectlink = $SITEROOT . '/projects/' . $project["slug"];
					$getitemlink = $SITEROOT . '/get_item/' . $project["slug"];
					$percentage = round($project["completed"] / $project["total"] * 100, 0);
				?>
				<tr>
					<td><a href="<?php echo $projectlink; ?>"><?php echo $project["title"]; ?></a></td>
					<td><?php echo $project["owner"]; ?></td>
					<td><?php echo $percentage . "% (" . $project["completed"] . "/" . $project["total"] . ")";?></td>
					<td><a href="<?php echo $getitemlink; ?>" class="button smallbutton">Get new item</a></td>
				</tr>
				<?php } ?>
			</table>

			<h3>History</h3>
			<table cellpadding="0" cellspacing="0">
				<tr>
					<th>Item</th>
					<th>Project</th>
					<th>Finished</th>
				<tr>
					<td><a href="#">Page 159</a></td>
					<td><a href="projects/hck">The Life of Heber C. Kimball</a></td>
					<td>10 Dec 2010</td>
				</tr>
			</table>
		</div>

		<div class="sidebar">
			<a href="#" class="button">Give me a new item</a>

			<h3>Stats</h3>
			<ul>
				<li>Score: <span class="stat">590</span></li>
				<li>Items proofed this week: <span class="stat">3</span></li>
				<li>Items proofed all time: <span class="stat">742</span></li>
			</ul>
		</div>
	</div>
</body>
</html>
