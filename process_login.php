<?php

include_once("include/config.php");
include_once("include/Alibaba.class.php");

$username = $_POST["username"];
$password = $_POST["password"];

if (Alibaba::login($username, $password)) {
	header("Location: $SITEROOT/dashboard/");
} else {
	Alibaba::redirectToLogin("Login failed");
}
