<?php

include_once("include/config.php");
include_once("include/Alibaba.class.php");
include_once("Database.class.php");

Alibaba::forceAuthentication();

$username = $_POST["username"];
$user_name = $_POST["user_name"];
$user_email = $_POST["user_email"];

$user_oldpassword = $_POST["user_oldpassword"];
$user_newpassword1 = $_POST["user_newpassword1"];
$user_newpassword2 = $_POST["user_newpassword2"];

if ($user_newpassword1 != "" && $user_newpassword1 == $user_newpassword2) {
	// verify that md5(oldpassword) == the password in the database
	$change_password = true;

	// else redirect to settings with an error
	//header("Location: $SITEROOT/settings?message=Passwords didn't match. Try again.");
}

$db->connect();

$query = "UPDATE users ";
$query .= "SET name = '" . mysql_real_escape_string($user_name) . "', ";
$query .= "email = '" . mysql_real_escape_string($user_email) . "' ";
if ($change_password) {
	$query .= ", password = '" . md5(mysql_real_escape_string($user_newpassword1)) . "' ";
}
$query .= "WHERE username = '" . mysql_real_escape_string($username) . "'";

$result = mysql_query($query) or die ("Couldn't run: $query");

$db->close();

header("Location: $SITEROOT/settings?message=Settings saved.");

?>
