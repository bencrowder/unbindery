<?php

class TranscriptController {

	// --------------------------------------------------
	// Save transcript handler

	static public function save($params) {
		$item = $params['item'];
		$status = $params['status'];
		$transcript = $params['transcript'];

		$auth = Settings::getProtected('auth');
		$auth->forceAuthentication();

		$username = $auth->getUsername();
		$user = new User($username);

		// Make sure user has access (member of project, item is in queue)
		// TODO: Finish

		// Update it if it exists; add it if it doesn't
		$transcriptObj = $user->loadTranscript($item);
		if ($transcriptObj) {
			$user->updateTranscript($item, $status, $transcript->getText());
		} else {
			$user->addTranscript($item, $status, $transcript->getText());
		}
	}


	// --------------------------------------------------
	// Load transcript handler

	static public function load($params) {
		$item = $params['item'];

		$auth = Settings::getProtected('auth');
		$auth->forceAuthentication();

		$username = $auth->getUsername();
		$user = new User($username);

		// Make sure user has access (member of project, item is in queue)
		// TODO: Finish

		return $user->loadTranscript($item);
	}
}

?>
