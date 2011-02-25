<?php

include_once('include/config.php');
include_once('include/Alibaba.class.php');
include_once('Database.class.php');
include_once('Project.class.php');
include_once('Item.class.php');
include_once('User.class.php');
include_once('utils.php');

Alibaba::forceAuthentication();

$item_id = $_GET["item_id"];
$project_slug = $_GET["project_slug"];
$proofer_username = $_GET["user"];				// the user who proofed the text

$username = Alibaba::getUsername();		// the current user (admin or owner)

if (!$item_id || !$project_slug || !$proofer_username) {
	redirectToDashboard("", "Invalid item/project ID or username");
}

// get the current user's role on the project and make sure they're owner or admin
$user = new User($db, $username);

$role = $user->getRoleForProject($project_slug);

if (!$user->admin && $role != "owner") {
	redirectToDashboard("", "You don't have rights to review this item.");
}

// get the proofer's user object so we can see their status
$proofer = new User($db, $proofer_username);

if ($proofer->status == "") {
	redirectToDashboard("", "That user doesn't exist.");
}

// get the item from the database
$item = new Item($db);
$item->load($item_id, $project_slug, $proofer_username);

?>

<?php include('include/header.php'); ?>

	<div id="controls_container" class="review">
		<div id="controls">
			<ul id="controls_left">
				<li><a href="<?php echo $SITEROOT; ?>/dashboard" class="button">Back</a></li>
				<li><a href="<?php echo $SITEROOT; ?>/projects/<?php echo $project_slug; ?>/guidelines" class="button" target="_blank">Project Guidelines</a></li>
			</ul>
			<ul id="controls_right">
				<li><img src="<?php echo $SITEROOT; ?>/snake.gif" id="spinner" /></li>
				<li><span id="finished_review_button" class="button">Finish Review<?php if ($proofer->status == "training") { ?> and Clear<?php } ?></span></li>
			</ul>
		</div>
	</div>

	<div id="main">
		<h2><?php echo $item->title; ?> (proofed by <span id="review_username"><?php echo $proofer_username; ?></span>)</h2>

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
