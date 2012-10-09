<?php

class DispatchController {
	// --------------------------------------------------
	// Get next available item handler

	static public function getNextAvailableItem($params) {
		$username = $params['username'];
		$projectSlug = $params['projectSlug'];

		$success = false;
		$errorCode = '';

		$db = Settings::getProtected('db');
		$auth = Settings::getProtected('auth');

		// Make sure we're authenticated as the user we say we are
		$auth->forceAuthentication();
		$loggedInUsername = $auth->getUsername();

		if ($username != $loggedInUsername) {
			$code = "not-authenticated-as-correct-user";
		}

		// Load user
		$user = new User($username);

		// Is user still in training mode?
		if ($user->status == 'training') {
			$code = "not-cleared";
		}

		// Does this user belong to the project? If it's a private project, are they on the whitelist? (Or are they the owner?
		if (!$user->isMember($projectSlug)) {
			$code = "not-a-member";
		}

		// Does this user already have an item from this project?
		if ($user->hasProjectItem($projectSlug)) {
			$code = "has-unfinished-item";
		}

		// Load the user's queue
		$userQueue = new Queue("user.proof:$username", false, array('include-removed' => true));
		$userQueueItems = $userQueue->getItems();

		foreach ($userQueueItems as $item) {
			error_log("User queue: " . $item->item_id . " | " . $item->title);
		}

		// Load the project's proof queue
		$queue = new Queue("project.proof:$projectSlug");
		$queueItems = $queue->getItems();

		foreach ($queueItems as $item) {
			error_log("Project queue: " . $item->item_id . " | " . $item->title);
		}

		// Go through the project queue and get the first item the user hasn't yet done
		foreach ($queueItems as $item) {
			if (!in_array($item, $userQueueItems)) {
				error_log("Next item: " . $item->item_id . " | " . $item->title);
				$nextItem = $item;
				break;
			}	
		}			

		if (isset($nextItem) && $nextItem->item_id != -1) {
			error_log("Adding to user queue");
			// Reload the user's queue, this time ignoring items they've already done
			// Add it to the user's queue
			$userQueue = new Queue("user.proof:$username", false);
			$userQueue->add($nextItem);
			$userQueue->save();

			$userQueueItems = $userQueue->getItems();
			foreach ($userQueueItems as $item) {
				error_log("User queue: " . $item->item_id . " | " . $item->title);
			}

			error_log("Removing from project queue");
			// Remove it from the project queue
			$queue->remove($nextItem);
			$queue->save();

			$queueItems = $queue->getItems();
			foreach ($queueItems as $item) {
				error_log("Project queue: " . $item->item_id . " | " . $item->title);
			}

			$success = true;
			$code = $nextItem->item_id;
		} else {
			$code = "no-item-available";
		}

		error_log("returning status $success and code $code");
		return array('status' => $success, 'code' => $code);
	}
}

?>
