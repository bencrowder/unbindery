<?php

include_once('include/config.php');
include_once('include/Alibaba.class.php');
include_once('Database.class.php');
include_once('Project.class.php');
include_once('Item.class.php');
include_once('User.class.php');
include_once('utils.php');

Alibaba::forceAuthentication();

$mode = $_GET["mode"];
$slug = $_GET["slug"];

$username = Alibaba::getUsername(); 

if ($mode == "new") {
	$title = "Create New Project";
	$buttontitle = "Create Project";

	$project_deadline = 7;
	$project_numproofs = 1;
	$project_desc = "[Publication date, number of pages, etc.]";
} else {
	$title = "Edit Project Settings";
	$buttontitle = "Save";

	$project = new Project($db, $slug);

	$project_title = stripslashes($project->title);
	$project_author = stripslashes($project->author);
	$project_slug = stripslashes($project->slug);
	$project_language = stripslashes($project->language);
	$project_deadline = stripslashes($project->deadline_days);
	$project_numproofs = stripslashes($project->num_proofs);
	$project_desc = stripslashes($project->description);
	$project_guidelines = stripslashes($project->guidelines);
	$project_thumbnails = stripslashes($project->thumbnails);
}

?>

<?php include('include/header.php'); ?>

	<div id="main">
		<h2><?php echo $title; ?></h2>

		<form id="project_form" action="<?php echo $SITEROOT; ?>/admin/save_project" method="POST">
			<div class="bigcol">
				<label>Title</label>
				<input type="text" id="project_title" name="project_title" value="<?php echo $project_title; ?>" />

				<label>Author</label>
				<input type="text" id="project_author" name="project_author" value="<?php echo $project_author; ?>" />

				<label>Slug</label>
				<input type="text" id="project_slug" name="project_slug" value="<?php echo $project_slug; ?>" />

				<label>Language</label>
				<input type="text" id="project_language" name="project_language" value="<?php echo $project_language; ?>"/>

				<label>Length of Deadline (# days)</label>
				<input type="text" id="project_deadline" name="project_deadline" value="<?php echo $project_deadline; ?>" />

				<label># of Proofs Per Item</label>
				<input type="text" id="project_numproofs" name="project_numproofs" value="<?php echo $project_numproofs; ?>" />

				<label>Description</label>
				<textarea id="project_desc" name="project_desc"><?php echo $project_desc; ?></textarea>

				<label>Guidelines</label>
				<textarea id="project_guidelines" name="project_guidelines"><?php echo $project_guidelines; ?></textarea>

				<label>Thumbnails</label>
				<input type="text" id="project_thumbnails" name="project_thumbnails" value="<?php echo $project_thumbnails; ?>" />
			</div>

			<div class="sidebar">
				<input type="submit" value="<?php echo $buttontitle; ?>" class="button" />

				<?php if ($mode != "new") { ?>
				<a class="button" href="<?php echo $SITEROOT; ?>/admin/upload/<?php echo $slug; ?>">Add pages</a>
				<?php } ?>

				<input type="hidden" id="mode" name="mode" value="<?php echo $mode; ?>" />
			</div>
		</form>
	</div>

<?php include('include/footer.php'); ?>
