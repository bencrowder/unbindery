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
		/*
		// TODO: decide if this is necessary (can users have more than one item in their queue by default?)
		if ($user->hasProjectItem($projectSlug)) {
			$errorCode = "has-unfinished-item";
			return array('status' => $success, 'code' => $errorCode);
		}
		 */

		// Load the user's queue
		$userQueue = new Queue("user.proof:$username");
		$userQueueItems = $userQueue->getItems();

		foreach ($userQueueItems as $item) {
			error_log("User item: " . $item->item_id . " | " . $item->project_id);
		}

		// Load the project's proof queue and pop the first item on the stack
		$queue = new Queue("project.proof:$projectSlug");
		$queueItems = $queue->getItems();

		// Go through the project queue and get the first item the user hasn't yet done
		foreach ($queueItems as $item) {
			error_log("Item: " . $item->item_id . " | " . $item->project_id);
			if (!in_array($item, $userQueueItems)) {
				$nextItem = $item;
				break;
			}	
		}			

		if (isset($nextItem) && $nextItem->item_id != -1) {
			// Remove it from the project queue
			$queue->remove($nextItem);
			$queue->save();

			// Add it to the user's queue
			$userQueue->add($nextItem);
			$userQueue->save();

			$success = true;
			$code = $nextItem->item_id;
		} else {
			$code = "error-retrieving-item-from-db";
		}

		return array('status' => $success, 'code' => $code);
	}
}

?>
