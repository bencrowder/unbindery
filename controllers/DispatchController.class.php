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
		$userQueue = new Queue("user.proof:$username", false, array('include-removed' => true));
		$userQueueItems = $userQueue->getItems();

		// Load the project's proof queue
		$queue = new Queue("project.proof:$projectSlug");
		$queueItems = $queue->getItems();

		// Go through the project queue and get the first item the user hasn't yet done
		foreach ($queueItems as $item) {
			if (!in_array($item, $userQueueItems)) {
				$nextItem = $item;
				break;
			}	
		}			

		if (isset($nextItem) && $nextItem->item_id != -1) {
			// Add it to the user's queue
			$userQueue->add($nextItem);
			$userQueue->save();

			// Remove it from the project queue
			$queue->remove($nextItem);
			$queue->save();

			$success = true;
			$code = $nextItem->item_id;
		} else {
			$code = "no-item-available";
		}

		return array('status' => $success, 'code' => $code);
	}
}

?>
