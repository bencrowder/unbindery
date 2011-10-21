<?php

include_once("include/config.php");
include_once('Database.class.php');
include_once('User.class.php');

$hash = $_GET["hash"];

$user = new User();
$status = $user->validateHash($hash);

if ($status) {
	header("Location: $SITEROOT/?message=Confirmed. Go ahead and log in.");
} else {
	header("Location: $SITEROOT/?message=Invalid confirmation code.");
}

?>
