<?php

include_once("include/config.php");
include_once("include/Alibaba.class.php");
include_once("include/Database.class.php");

Alibaba::forceAuthentication();

$username = $_POST["username"];
$user_name = $_POST["user_name"];
$user_email = $_POST["user_email"];

$user_oldpassword = $_POST["user_oldpassword"];
$user_newpassword1 = $_POST["user_newpassword1"];
$user_newpassword2 = $_POST["user_newpassword2"];

$sql = g
// update name/email
// if newpassword1 exists and it's equal to newpassword2 and oldpassword is the real password, change the password
// else redirect to settings with an error

if ($error) {
	header("Location: $SITEROOT/settings?message=Passwords didn't match. Try again.");
} else {
	header("Location: $SITEROOT/settings");
}
