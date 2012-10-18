<?php

include '../EventManager.php';
include '../NotificationManager.php';

$eventManager = new EventManager();
$notify = new NotificationManager();
$notify->setEventManager($eventManager);

$notifications = array('user.new', 'transcript.save', 'item.new');

$notify->registerNotifications($notifications, array('NotificationController', 'send'));

class NotificationController {
	static public function send($params) {
		switch ($params['notification']) {
			case 'user.new':
				$username = $params['username'];
				$adminemail = $params['adminemail'];
				$useremail = $params['useremail'];
				echo "New user $username, sending to $adminemail and $useremail\n";
				break;
			case 'transcript.save':
				$username = $params['username'];
				$item_id = $params['item_id'];
				echo "Saving user $username's transcript for item $item_id\n";
				break;
		}
	}
}

$username = 'userbob';
$adminemail = 'test@gmail.com';
$useremail = 'user@gmail.com';

$notify->trigger('user.new', array('username' => $username, 'adminemail' => $adminemail, 'useremail' => $useremail));

$notify->trigger('transcript.save', array('username' => $username, 'item_id' => 523));

?>
