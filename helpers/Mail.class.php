<?php

class Mail {
	static public function sendMessage($to, $subject, $message) {
		$admin_email = Settings::getProtected('admin_email');

		$headers = "From: $admin_email" . "\r\n";
		$headers .= "Reply-To: $admin_email" . "\r\n";
		$headers .= "X-Mailer: PHP/" . phpversion();

		return mail($to, $subject, $message, $headers);
	}
}
