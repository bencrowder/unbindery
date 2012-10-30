<?php

class TranscriptController {

	// --------------------------------------------------
	// Save transcript handler

	static public function save($params) {
		$item = $params['item'];
		$status = $params['status'];
		$type = $params['type'];
		$transcript = $params['transcript'];

		$user = User::getAuthenticatedUser();

		// Make sure user has access (member of project, item is in queue)
		// TODO: Finish

		// Update it if it exists; add it if it doesn't
		$transcriptObj = $user->loadTranscript($item, $type);

		if ($transcriptObj) {
			$user->updateTranscript($item, $status, $transcript->getText(), $type);
		} else {
			$user->addTranscript($item, $status, $transcript->getText(), $type);
		}

		// Load the project
		$project = new Project($item->project_slug);
		$projectOwner = new User($project->owner);
		// TODO: load any users who have admin rights

		// Trigger notifications
		$notify = Settings::getProtected('notify');
		$notify->trigger("user_save_transcript_$status", array('user' => $user, 'username' => $user->username, 'item' => $item->title));
		$notify->trigger("admin_save_transcript_$status", array('admins' => array($projectOwner), 'username' => $user->username, 'item' => $item->title));
	}


	// --------------------------------------------------
	// Load transcript handler

	static public function load($params) {
		$item = $params['item'];
		$type = $params['type'];

		$auth = Settings::getProtected('auth');
		$auth->forceAuthentication();

		$username = $auth->getUsername();
		$user = new User($username);

		// Make sure user has access (member of project, item is in queue)
		// TODO: Finish

		return $user->loadTranscript($item, $type);
	}


	// --------------------------------------------------
	// Diff transcript handler

	static public function diff($transcripts) {
		$str = '';

		$transcriptA = $transcripts[0]['transcript'];
		$transcriptB = $transcripts[1]['transcript'];

		$opcodes = FineDiff::getDiffOpcodes($transcriptA, $transcriptB, FineDiff::$characterGranularity);
		$str = FineDiff::renderDiffToUnbinderyFromOpcodes($transcriptA, $opcodes);

		// TODO: UTF-8 encoding
		// mb_convert_encoding($from_text_utf8, 'HTML-ENTITIES', 'UTF-8');

		return $str;
	}


	// --------------------------------------------------
	// Variable substitution helper function

	static public function replaceVariables($template, $vars) {
		// Go through the template and swap out variables

		$matches = array();
		preg_match_all("/{{ (.+?) }}/", $template, $matches);

		foreach ($matches[1] as $match) {
			// Get the first part (up to the first period)
			if (strpos($match, ".") > 0) {
				$head = (strpos($match, ".") != -1) ? substr($match, 0, strpos($match, ".")) : $match;
				$tail = substr($match, strpos($match, ".") + 1);
			} else {
				$head = $match;
				$tail = '';
			}

			$replacement = '';

			switch ($head) {
				case 'transcript':
					$replacement = $vars['transcript'];
					break;

				case 'proofers':
					$replacement = $vars['proofers'];
					break;

				case 'reviewers':
					$replacement = $vars['reviewers'];
					break;

				case 'user':
					$user = $vars['user'];
					$replacement = $user[$tail];
					break;

				case 'project':
					$project = $vars['project'];
					$replacement = $project[$tail];
					break;

				case 'item':
					$item = $vars['item'];
					$replacement = $item[$tail];
					break;
			}

			$template = preg_replace("/{{ $match }}/", $replacement, $template);
		}

		error_log("Template now: $template");

		return $template;
	}
}

?>
