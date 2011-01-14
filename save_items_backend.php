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

$items = array();

// go through the POST variables
foreach ($_POST as $key => $value) {
	if ($key == "project_slug") {
		$slug = $value;
	} else {
		$item_id = substr($key, 0, strpos($key, '_')); // key == "189_text" or such
		$item_text = mysql_real_escape_string($value);
		array_push($items, array($item_id, $item_text));		
	}
}

if ($slug) {
	$project = new Project($db, $slug);

	$project->saveItems($items);

	header("Location: $SITEROOT/projects/$slug");
} else {
	// redirect to error page
	header("Location: $SITEROOT/dashboard/");
}

?>
