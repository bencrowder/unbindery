<?php

class Mail {
	static public function sendMessage($to, $subject, $message) {
		$headers = "From: Unbindery <$ADMINEMAIL>" . "\r\n";
		$headers .= "Reply-To: Unbindery <$ADMINEMAIL>" . "\r\n";
		$headers .= "X-Mailer: PHP/" . phpversion();

		return mail($to, $subject, $message, $headers);
	}
}
