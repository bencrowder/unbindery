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

// Get info from POST
$page_id = $_POST["page_id"];
$project_slug = $_POST["project_slug"];
$page_title = $_POST["page_title"];
$page_text = $_POST["page_text"];
$next = $_POST["next"];

// Load the page from the database
$page = new Item($db, $page_id, $project_slug);

// Update the values
$page->title = $page_title;
$page->itemtext = $page_text;

// Save it to the database
$retval = $page->save();

// Check for success
if ($retval) {
	if ($next) {
		// serve up next page
		$nextpage_id = $page->getNextPage();
		if ($nextpage_id) {
			// we go to new_page/ instead of edit/ so that we keep getting next
			header("Location: $SITEROOT/admin/new_page/$project_slug/$nextpage_id");
		} else {
			// run out of pages, go back to the admin project page
			header("Location: $SITEROOT/admin/projects/$project_slug");
		}
	} else {
		// go back to the admin project page
		header("Location: $SITEROOT/admin/projects/$project_slug");
	}
} else {
	// redirect to error page
	redirectToDashboard("", "Error saving page.");
}

?>
