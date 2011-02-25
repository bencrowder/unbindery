<?php

include_once('include/config.php');
include_once('include/Alibaba.class.php');
include_once('Database.class.php');
include_once('Project.class.php');
include_once('Item.class.php');
include_once('User.class.php');
include_once('utils.php');

Alibaba::forceAuthentication();
$username = Alibaba::getUsername(); 

$title = $_POST['project_title'];
$author = $_POST['project_author'];
$slug = $_POST['project_slug'];
$language = $_POST['project_language'];
$description = $_POST['project_desc'];
$owner = $username;
$guidelines = $_POST['project_guidelines'];
$intro_email = $_POST['project_email'];
$deadline_days = $_POST['project_deadline'];
$num_proofs = $_POST['project_numproofs'];

$project = new Project($db);

$retval = $project->create($title, $author, $slug, $language, $description, $owner, $guidelines, $intro_email, $deadline_days, $num_proofs);

if ($retval == "success") {
	// make project directory for images
	$dir = dirname(__FILE__) . "/images/$slug";

	$rs = @mkdir($dir, 0775, true);
	if ($rs) {
		// success! now create the images directory
		chmod($dir, 0775);
		// redirect to upload page
		header("Location: $SITEROOT/upload_items/$slug");
	} else {
		// redirect to error page
		header("Location: $SITEROOT/dashboard/");
	}

} else {
	// redirect to error page
	header("Location: $SITEROOT/dashboard/");
}

?>
