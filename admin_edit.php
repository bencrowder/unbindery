<?php

include_once('include/config.php');
include_once('include/Alibaba.class.php');
include_once('Database.class.php');
include_once('Project.class.php');
include_once('Item.class.php');
include_once('User.class.php');
include_once('utils.php');

Alibaba::forceAuthentication();

$page_id = $_GET["item_id"];
$project_slug = $_GET["project_slug"];
$username = Alibaba::getUsername(); 

if (!$page_id || !$project_slug) {
	redirectToDashboard("", "Invalid page/project ID");
}

// make sure they're an admin
$user = new User($db, $username);
if (!$user->admin) {
	redirectToDashboard("", "You're not an administrator.");
}

// get the page from the database
$page = new Item($db);
$page->load($page_id, $project_slug);

// see if we're adding a new page or not
$next = $_GET["next"];

?>

<?php include('include/header.php'); ?>

	<div id="controls_container">
		<div id="controls">
			<ul id="controls_left">
				<li><a href="<?php echo $SITEROOT; ?>/admin/projects/<?php echo $project_slug; ?>" class="button">Back</a></li>
			</ul>
			<ul id="controls_right">
				<li><img src="<?php echo $SITEROOT; ?>/snake.gif" id="spinner" /></li>
				<li><a href="<?php echo $SITEROOT; ?>/admin/save_page" class="button">Save Page</a></li>
			</ul>
		</div>
	</div>

	<div id="main" class="edit">
		<div id="metadata">
			<label>Title</label>
			<input type="text" id="page_title" name="page_title" value="<?php echo $page->title; ?>" />
		</div>

		<div id="image_container">
			<img src="<?php echo $SITEROOT; ?>/images/<?php echo $page->href; ?>" width="368" />
		</div>

		<div id="text_container">
			<form id="ub_text">
				<textarea id="itemtext"><?php echo stripslashes($page->itemtext); ?></textarea>
				<input type="hidden" name="item_id" id="item_id" value="<?php echo $page_id; ?>" />
				<input type="hidden" name="project_slug" id="project_slug" value="<?php echo $project_slug; ?>" />
				<input type="hidden" name="next" id="next" value="<?php echo $next; ?>" />
			</form>
		</div>
	</div>

</body>
</html>
