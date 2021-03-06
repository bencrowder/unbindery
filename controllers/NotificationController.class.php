<?php

class NotificationController {

	// --------------------------------------------------
	// Send handler

	static public function send($params) {
		$notification = $params['notification'];

		// Get notifications from settings
		$notifications = Settings::getProtected('notifications');
		$notify = $notifications[$notification];
		$emailSubject = Settings::getProtected('email_subject');

		// Go through each target for this notification
		foreach ($notify as $target) {
			if ($target[0] == '@') {
				// User class
				switch ($target) {
					// User class
					// Params:
					//   * user = User object
					case '@user':
						// get the user involved
						$user = $params['user'];

						if (self::notificationIsEnabled($user, $notification)) {
							self::sendNotification($user->email, $notification, $params);
						}

						break;

					// Project admin
					// Params:
					//   * admins = array of User objects
					case '@projectadmin':
						// get the project admins involved
						$admins = $params['admins'];

						foreach ($admins as $user) {
							if (self::notificationIsEnabled($user, $notification)) {
								self::sendNotification($user->email, $notification, $params);
							}
						}

						break;

					// Site admin
					// Params:
					//   * admin = site admin User object
					case '@admin':
						// site admin
						$user = $params['admin'];

						if (self::notificationIsEnabled($user, $notification)) {
							self::sendNotification($user->email, $notification, $params);
						}

						break;
				}
			} else {
				self::sendNotification($target, $notification, $params);
			}
		}
	}


	// --------------------------------------------------
	// Replace variables

	static public function replaceVariables($message, $params) {
		$str = $message;
		foreach ($params as $key=>$value) {
			if (gettype($value) == 'string') {
				$str = preg_replace("/[[" . $key . "]]/", $value, $str);
			}
		}

		return $str;
	}


	// --------------------------------------------------
	// Check if a notification is enabled for the user

	static public function notificationIsEnabled($user, $notification) {
		if (array_key_exists('notifications', $user->prefs)) {
			if (property_exists($user->prefs->notifications, $notification)) {
				return $user->prefs->notifications->$notification;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}


	// --------------------------------------------------
	// Send a notification

	static public function sendNotification($to, $notification, $params) {
		$i18n = new I18n("../translations", Settings::getProtected('language'));

		error_log("Sending $notification.subject / $notification.message");

		$subject = self::replaceVariables($i18n->t("$notification.subject"), $params);
		$email_subject = Settings::getProtected('email_subject');
		if ($email_subject) $subject = "$email_subject $subject";
		
		$message = self::replaceVariables($i18n->t("$notification.message"), $params);

		Mail::sendMessage($to, $subject, $message);
	}
}

?>
