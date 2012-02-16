<?php

include_once('include/config.php');
include_once('Database.class.php');
include_once('Server.class.php');
include_once('Mail.class.php');

// run once a day

$server = new Server($db);

// Go through all assignments where the deadline is < NOW() and decrease points by one
$server->decrementTardies();

// Go through all assignments where deadline is in one day and email the user
$server->emailTardies();

?>
