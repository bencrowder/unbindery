<?php

include_once("include/config.php");
include_once('Database.class.php');
include_once('User.class.php');
include_once('Mail.class.php');

$email = $_POST["email"];
$username = $_POST["username"];
$password = $_POST["password"];

// generate hash
$hash = md5($email . $username . $time);

// add user to database with "pending" as status
$user = new User($db);
$user->username = $username;
$user->password = $password;
$user->email = $email;
$user->addToDatabase($hash);

// send confirmation link to user via email
$message = "Thanks for signing up! Here's the confirmation link to activate your account\n";
$message .= "\n";
$message .= "$SITEROOT/activate/$hash\n";
$message .= "\n";

$status = Mail::sendMessage($email, "[Unbindery] Confirmation link", $message);

if ($status == 1) { 
	$status = "done";
} else {
	$status = "error mailing";
}

$status = Mail::sendMessage($ADMINEMAIL, "[Unbindery] New signup", "New user signed up: $username <$email>");

// return "done" (so Ajax can replace the div)
echo json_encode(array("statuscode" => "done", "username" => $user->username));

?>
