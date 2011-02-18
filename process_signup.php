<?php

include_once("include/config.php");
include_once('Database.class.php');
include_once('User.class.php');

$email = $_POST["email"];
$username = $_POST["username"];
$password = $_POST["password"];

// generate hash
$hash = md5($email . $username);

// add user to database with "pending" as status
$user = new User($db);
$user->username = $username;
$user->password = $password;
$user->email = $email;
$user->addToDatabase($hash);

// send confirmation link to user via email

// return "done" (so Ajax can replace the div)
echo json_encode(array("statuscode" => "done", "username" => $user->username));
