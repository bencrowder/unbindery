<?php

class DispatchController {
	// --------------------------------------------------
	// Get next available item handler

	static public function getNextAvailableItem($params) {
		$username = $params['username'];
		$projectSlug = $params['projectSlug'];
		$type = $params['type'];
		$role = $type . "er";

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

		// Does this user belong to the project?
		if (!$user->isMember($projectSlug, $role)) {
			$code = "not-a-member";
		}

		// Does this user already have an item from this project?
		if ($user->hasProjectItem($projectSlug)) {
			$code = "has-unfinished-item";
		}

		// Load the user's queue
		$userQueue = new Queue("user.$type:$username", false, array('include-removed' => true));
		$userQueueItems = $userQueue->getItems();

		// Load the project's queue
		$queue = new Queue("project.$type:$projectSlug");
		$queueItems = $queue->getItems();

		// Go through the project queue and get the first item the user hasn't yet done
		foreach ($queueItems as $item) {
			if (!in_array($item, $userQueueItems)) {
				$nextItem = $item;
				break;
			}	
		}			

		if (isset($nextItem) && $nextItem->item_id != -1) {
			// Concatenate proofed transcripts
			if ($type == 'review') {
				// Get proofed transcripts for the new item
				$transcripts = $db->loadItemTranscripts($nextItem->project_id, $nextItem->item_id, 'proof');

				// Only diff them if there's more than one
				if (count($transcripts) > 1) {
					$transcriptText = Transcript::diff($transcripts);
				} else {
					$transcriptText = $transcripts[0]['transcript'];
				}

				// Only get the fields for the first transcript
				$transcriptFields = $transcripts[0]['fields'];

				// Create transcript and add to the database
				$transcript = new Transcript();
				$transcript->setText($transcriptText);
				$transcript->setFields($transcriptFields);
				$transcript->save(array('item' => $nextItem, 'status' => 'draft', 'type' => 'review'));
			}

			// Reload the user's queue, this time ignoring items they've already done
			// Add it to the user's queue
			$userQueue = new Queue("user.$type:$username", false);
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
