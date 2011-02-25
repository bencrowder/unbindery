<?php

include_once("include/config.php");
include_once("include/Alibaba.class.php");
include_once("Database.class.php");
include_once("User.class.php");

$username = $_POST["username"];
$password = $_POST["password"];

if (Alibaba::login($username, $password)) {
	$user = new User($db, $username);
	$user->updateLogin();						// updates last_login time in database

	header("Location: $SITEROOT/dashboard/");
} else {
	Alibaba::redirectToLogin("Login failed");
}
