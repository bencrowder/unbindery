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

		// Everything's good, so get the next available item from the database and return it
		$itemId = $db->getNextAvailableItem($username, $projectSlug);
		if ($itemId != -1) {
			$success = true;
			$code = $itemId;
		} else {
			$code = "error-retrieving-item-from-db";
		}

		return array('status' => $success, 'code' => $code);
	}
}
