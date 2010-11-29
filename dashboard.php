<?php

include_once('include/config.php');
include_once('Database.class.php');
include_once('Project.class.php');
include_once('Item.class.php');
include_once('unbindery.php');

$username = "ben";

$message = $_GET["message"];
$error = $_GET["error"];

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
				$items = getUserAssignments($db, $username);
				foreach ($items as $item) {
					$editlink = $SITEROOT . '/edit/' . $item["project_slug"] . '/' . $item["item_id"];
					$projectlink = $SITEROOT . '/projects/' . $item["project_slug"];
				?>
				<tr>
					<td><a href="<?php echo $editlink; ?>"><?php echo $item["item_title"]; ?></a></td>
					<td><a href="<?php echo $projectlink; ?>"><?php echo $item["project_title"]; ?></a></td>
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
				<tr>
					<td><a href="projects/hck">The Life of Heber C. Kimball</a></td>
					<td><a href="#">ben</a></td>
					<td>160/392 pages</td>
					<td><a href="#" class="button smallbutton">Get new item</a></td>
				</tr>
				<tr>
					<td><a href="projects/aof">The Articles of Faith</a></td>
					<td><a href="#">ben</a></td>
					<td>20/144 pages</td>
					<td><a href="#" class="button smallbutton">Get new item</a></td>
				</tr>
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
