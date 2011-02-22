<?php

include_once('include/config.php');
include_once('include/Alibaba.class.php');
include_once('Database.class.php');
include_once('Project.class.php');
include_once('Mail.class.php');
include_once('User.class.php');

Alibaba::forceAuthentication();

$username = Alibaba::getUsername(); 
$user = new User($db, $username);

$slug = $_GET['slug'];

$retval = $user->assignToProject($slug);

if ($retval) {
	header("Location: $SITEROOT/dashboard/");
} else {
	// redirect to error page
	header("Location: $SITEROOT/dashboard/?message=Error joining project.");
}

?>
