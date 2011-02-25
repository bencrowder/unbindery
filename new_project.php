<?php

include_once('include/config.php');
include_once('include/Alibaba.class.php');
include_once('Database.class.php');
include_once('Project.class.php');
include_once('Item.class.php');
include_once('utils.php');

Alibaba::forceAuthentication();

$username = Alibaba::getUsername(); 

?>

<?php include('include/header.php'); ?>

	<div id="main">
		<h2>Create New Project</h2>

		<form id="project_form" action="<?php echo $SITEROOT; ?>/new_project_backend/" method="POST">
			<div class="bigcol">
				<label>Title</label>
				<input type="text" id="project_title" name="project_title" />

				<label>Author</label>
				<input type="text" id="project_author" name="project_author" />

				<label>Slug</label>
				<input type="text" id="project_slug" name="project_slug" />

				<label>Language</label>
				<input type="text" id="project_language" name="project_language" />

				<label>Length of Deadline (# days)</label>
				<input type="text" id="project_deadline" name="project_deadline" value="7" />

				<label># of Proofs Per Item</label>
				<input type="text" id="project_numproofs" name="project_numproofs" value="2" />

				<label>Description</label>
				<textarea id="project_desc" name="project_desc">[Author, publication date, etc.]</textarea>

				<label>Guidelines</label>
				<textarea id="project_guidelines" name="project_guidelines"></textarea>

				<label>Intro Email</label>
				<textarea id="project_email" name="project_email">[This will get sent to volunteers when they join the project.]</textarea>
			</div>

			<div class="sidebar">
				<input type="submit" value="Create Project" class="button" />
			</div>
		</form>
	</div>
</body>
</html>
