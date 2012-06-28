<?php

class WorkflowController {

	// --------------------------------------------------
	// Parse workflow handler

	static public function parse($item, $action) {
		error_log("Parsing workflow for " . $item->item_id . ": $action");

		$auth = Settings::getProtected('auth');
		$auth->forceAuthentication();

		$username = $auth->getUsername();
		$user = new User($username);

		// Parse the action
		switch (trim($action)) {
			case '@proofer':
				$destinationQueue = "project.proof:" . $item->project_slug;
				break;
			case '@reviewer':
				$destinationQueue = "project.review:" . $item->project_slug;
				break;
			default: // username (defaults to proof, TODO: allow review as well)
				$destinationQueue = "user.proof:" . $action;
				break;
		}

		error_log("Sending to $destinationQueue");
		$queue = new Queue($destinationQueue);
		$queue->add($item);
		$queue->save();
	}
}

?>
