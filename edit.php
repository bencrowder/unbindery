<?php

include_once('include/config.php');
include_once('include/Alibaba.class.php');
include_once('Database.class.php');
include_once('Project.class.php');
include_once('Item.class.php');
include_once('unbindery.php');
include_once('utils.php');

Alibaba::forceAuthentication();

$item_id = $_GET["item_id"];
$project_slug = $_GET["project_slug"];
$username = Alibaba::getUsername(); 

if (!$item_id || !$project_slug) {
	redirectToDashboard("", "Invalid item/project ID");
}

// make sure they're assigned to this item
$assigned = checkUserAssignment($db, $username, $item_id, $project_slug);
if (!$assigned) {
	redirectToDashboard("", "You're not assigned to that item.");
}

// get the item from the database
$item = new Item($db);
$item->load($item_id, $project_slug, $username);

?>

<?php include('include/header.php'); ?>

	<div id="controls_container">
		<div id="controls">
			<ul id="controls_left">
				<li><a href="<?php echo $SITEROOT; ?>/dashboard" class="button">Back</a></li>
				<li><span class="button">Project Help</span></li>
			</ul>
			<ul id="controls_right">
				<li><img src="<?php echo $SITEROOT; ?>/snake.gif" id="spinner" /></li>
				<li><span id="save_as_draft_button" class="button">Save as Draft</span></li>
				<li><span id="finished_button" class="button">I'm Finished</span></li>
			</ul>
		</div>
	</div>

	<div id="main">
		<h2><?php echo $item->title; ?></h2>

		<div id="image_container">
			<img src="<?php echo $SITEROOT; ?>/images/<?php echo $item->href; ?>" width="850" />
		</div>

		<div id="text_container">
			<form id="ub_text">
				<textarea id="itemtext"><?php echo stripslashes($item->itemtext); ?></textarea>
				<input type="hidden" name="item_id" id="item_id" value="<?php echo $item_id; ?>" />
				<input type="hidden" name="project_slug" id="project_slug" value="<?php echo $project_slug; ?>" />
			</form>
		</div>
	</div>
</body>
</html>
