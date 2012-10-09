<?php

class ItemPageController {
	// --------------------------------------------------
	// Item proof/review handler
	// URL: /projects/PROJECT/items/ITEM/proof OR /users/USER/projects/PROJECT/items/ITEM/proof
	// URL: /projects/PROJECT/items/ITEM/review OR /users/USER/projects/PROJECT/items/ITEM/review
	// Methods: 

	static public function itemProof($params) {
		$format = self::getFormat($params['args'], 0, 2);
		$projectType = self::getProjectPageType($params['args']);

		$projectSlugIndex = ($projectType == 'system') ? 0 : 2;
		$projectSlug = $params['args'][$projectSlugIndex];

		$itemIndex = ($projectType == 'system') ? 1 : 3;
		$itemId = $params['args'][$itemIndex];

		$proofTypeIndex = ($projectType == 'system') ? 2 : 4;
		$proofType = $params['args'][$proofTypeIndex];

		$owner = ($projectType == 'user') ? $params['args'][0] : '';

		$db = Settings::getProtected('db');
		$auth = Settings::getProtected('auth');

		$auth->forceAuthentication();
		$username = $auth->getUsername();
		$user = new User($username);

		switch ($params['method']) {
			// GET: Get proof/review page for this item
			case 'GET':
				// Make sure they have access to the project
				if (!$user->isMember($projectSlug)) {
					Utils::redirectToDashboard("", "You're not a member of that project. Sorry.");
					return;
				}

				// Load the item
				$itemObj = new Item($itemId, $projectSlug, $username, $proofType);

				// Make sure it exists (if it fails, it'll return a boolean)
				if ($itemObj->item_id == -1) {
					Utils::redirectToDashboard("", "Item doesn't exist.");
					return;
				}

				// If it's not in their current queue, they're editing it after finishing it
				// TODO: Make this part more elegant
				$alreadyFinished = false;
				$userCurrentQueue = new Queue("user.$proofType:$username", false);
				$userCurrentQueueItems = $userCurrentQueue->getItems();
				if (!in_array($itemObj, $userCurrentQueueItems)) {
					$alreadyFinished = true;
				}

				// And if it's not in their full queue, they never had it and shouldn't be allowed to proof it
				$userQueue = new Queue("user.$proofType:$username", false, array('include-removed' => true));
				$userQueueItems = $userQueue->getItems();
				if (!in_array($itemObj, $userQueueItems)) {
					Utils::redirectToDashboard("", "You don't have that item in your queue.");
					return;
				}

				// See if there are any items left for us to proof
				$moreToProof = false;
				$queue = new Queue("project.$proofType:$projectSlug");

				foreach ($queue->getItems() as $item) {
					if (!in_array($item, $userQueueItems)) {
						$moreToProof = true;
						break;
					}	
				}

				$item = array();
				$item['id'] = $itemId;
				$item['title'] = $itemObj->title;
				$item['href'] = $itemObj->href;

				// If the user has a transcript for this item, load it instead
				if ($itemObj->userTranscript && trim($itemObj->userTranscript) != '') {
					$transcript = trim($itemObj->userTranscript);
				} else {
					$transcript = trim($itemObj->transcript);
				}

				// Strip slashes and replace angle brackets
				//$item['transcript'] = str_replace(">", "&gt;", str_replace("<", "&lt;", stripslashes($transcript)));
				$item['transcript'] = stripslashes($transcript);

				$item['project_slug'] = $projectSlug;
				$item['project_owner'] = $owner;
				$item['project_type'] = ($owner == '') ? 'public' : 'private';

				// Get template type
				$templateType = $itemObj->type;

				// Get any editor-specific config settings
				$editors = Settings::getProtected('editors');
				$editorOptions = (array_key_exists($templateType, $editors)) ? $editors[$templateType] : array();
				
				// Display the template
				$options = array(
					'user' => array(
						'loggedin' => true,
						'admin' => $user->admin,
						'prefs' => $user->prefs,
						),
					'item' => $item,
					'transcript_type' => $proofType,
					'more_to_proof' => $moreToProof,
					'already_finished' => $alreadyFinished,
					'editor_options' => $editorOptions,
					'editor_type' => $templateType,
					'css' => array("editors/$templateType/$templateType.css"),
					'js' => array("editors/$templateType/$templateType.js"),
				);

				Template::render("editors/$templateType", $options);

				break;
		}
	}


	// --------------------------------------------------
	// Item media handler
	// URL: /projects/PROJECT/items/ITEM/media
	// Methods: 

	static public function media($params) {
		echo "Item media (" . $params['method'] . "): ";
		print_r($params['args']);
	}


	// --------------------------------------------------
	// Item transcript handler
	// URL: /projects/PROJECT/items/ITEM/transcript OR /users/USER/projects/PROJECT/items/ITEM/transcript
	// Methods: 

	static public function transcript($params) {
		$format = self::getFormat($params['args'], 0, 2);
		$projectType = self::getProjectPageType($params['args']);

		$projectSlugIndex = ($projectType == 'system') ? 0 : 2;
		$projectSlug = $params['args'][$projectSlugIndex];

		$itemIndex = ($projectType == 'system') ? 1 : 3;
		$itemId = $params['args'][$itemIndex];

		$owner = ($projectType == 'user') ? $params['args'][0] : '';

		$db = Settings::getProtected('db');
		$auth = Settings::getProtected('auth');

		$auth->forceAuthentication();
		$username = $auth->getUsername();
		$user = new User($username);

		switch ($params['method']) {
			// POST: Post transcript for item
			case 'POST':
				$transcriptType = Utils::POST('type');			// proof, review

				// Make sure they have access to the project
				if (!$user->isMember($projectSlug, $owner)) {
					$code = "not-a-member";
					// TODO: fail gracefully here, redirect to dashboard with error
					echo "You're not a member of that project. Sorry.";
					return;
				}

				// Load the item
				$itemObj = new Item($itemId, $projectSlug, $username, $transcriptType);

				// Make sure item exists (if it fails, it'll return a boolean)
				if ($itemObj->item_id == -1) {
					// TODO: fail gracefully here
					echo "Item doesn't exist.";
					return;
				}

				// Make sure the user has this item in their queue
				// TODO: Finish

				// Get the transcript text
				$transcriptText = Utils::POST('transcript');
				$transcriptStatus = Utils::POST('status');		// draft, completed, reviewed

				// Save transcript to database
				$transcript = new Transcript();
				$transcript->load(array('item' => $itemObj, 'type' => $transcriptType));
				$transcript->setText($transcriptText);
				$transcript->save(array('item' => $itemObj, 'status' => $transcriptStatus, 'type' => $transcriptType));

				if ($transcriptStatus == 'completed' || $transcriptStatus == 'reviewed') {
					$scoring = Settings::getProtected('scoring');

					// Notifications
					if ($transcriptStatus == 'reviewed') {
						// Bump user's score up if they haven't already reviewed this page
						$user->updateScoreForItem($itemObj->item_id, $itemObj->project_id, $scoring['review'], 'review');

						// Notify project owner that review is complete

						// Training mode
						if ($user->status == 'training') {
							// Clear the user for proofing
							$user->setStatus('clear');

							// Notify user of clearance

							// Notify admin of clearance
						}
					} else {
						// Bump user's score up if they haven't already proofed this page
						$user->updateScoreForItem($itemObj->item_id, $itemObj->project_id, $scoring['proof'], 'proof');

						if ($user->status == 'training') {
							// Notify project owner with link to review for clearance
						}
					}

					// Remove from user's queue
					$userQueue = new Queue("user.$transcriptType:$username");
					$userQueue->remove($itemObj);
					$userQueue->save();

					// Increase item's workflow index
					// TODO: Make sure this works properly
					if ($transcriptStatus != 'draft') {
						$itemObj->workflow_index += 1;
					}

					// And save it
					$itemObj->save();

					// Load the project
					$project = new Project($itemObj->project_slug);

					// Get next workflow step
					$workflow = new Workflow($project->workflow);
					$workflow->setIndex($itemObj->workflow_index);

					$workflowQueue = $workflow->getWorkflow();

					if ($itemObj->workflow_index < count($workflowQueue)) {
						// Process next step
						$workflow->next($itemObj);
					} else {
						// The item is complete
						$itemObj->setStatus("completed");
					}
				}

				echo json_encode(array("statuscode" => "success"));

				break;
		}
	}


	// --------------------------------------------------
	// Item admin handler
	// URL: /projects/PROJECT/items/ITEM/admin
	// Methods: 

	static public function admin($params) {
		echo "Item admin (" . $params['method'] . "): ";
		print_r($params['args']);
	}



	// --------------------------------------------------
	// Item handler
	// URL: /projects/PROJECT/items/ITEM
	// Methods: 

	static public function item($params) {
		echo "Item (" . $params['method'] . "): ";
		print_r($params['args']);
	}


	// --------------------------------------------------
	// General items transcripts handler
	// URL: /projects/PROJECT/items/transcripts
	// Methods: 

	static public function transcripts($params) {
		echo "Item transcripts (" . $params['method'] . "): ";
		print_r($params['args']);
	}


	// --------------------------------------------------
	// Get new item handler
	// URL: /projects/PROJECT/items/get OR /users/USER/projects/PROJECT/items/get
	// Methods: POST = get next available item

	static public function getNewItem($params) {
		$format = self::getFormat($params['args'], 0, 2);
		$projectPage = self::getProjectPageType($params['args']);
		$projectSlugIndex = ($projectPage == 'system') ? 0 : 2;
		$projectSlug = $params['args'][$projectSlugIndex];

		$db = Settings::getProtected('db');
		$auth = Settings::getProtected('auth');

		$auth->forceAuthentication();
		$username = $auth->getUsername();
		$user = new User($username);

		switch ($params['method']) {
			// POST: Get next available item
			case 'POST':
				$type = Utils::POST('type');		// proof or review

				$dispatch = Settings::getProtected('dispatch');
				$dispatch->init(array('username' => $username, 'projectSlug' => $projectSlug, 'type' => $type));
				$response = $dispatch->next();

				if ($response['status'] == true) {
					$itemId = $response['code'];

					// Load the item to make sure it's real
					$item = new Item('', $itemId, $projectSlug, $username, $type);

					// Verification check
					if ($item->status == 'available') {
						// Put it in the user's queue
						$queue = new Queue("user.$type:$username", true);
						$queue->add($item);
						$queue->save();
					}
				}

				echo json_encode($response);

				break;
		}
	}


	// --------------------------------------------------
	// General items handler
	// URL: /projects/PROJECT/items
	// Methods: 

	static public function items($params) {
		echo "Items (" . $params['method'] . "): ";
		print_r($params['args']);
	}


	// --------------------------------------------------
	// Helper function to parse a project page type

	static public function getProjectPageType($args) {
		if ($args[0] == 'users') {
			return 'user';
		} else {
			return 'system';
		}
	}


	// --------------------------------------------------
	// Helper function to parse the return format type based on the URL

	static public function getFormat($args, $systemIndex, $userIndex) {
		$projectPage = self::getProjectPageType($args);
		$formatIndex = ($projectPage == 'system') ? $systemIndex : $userIndex;
		return $args[$formatIndex] != '' ? $args[$formatIndex] : 'html';
	}
}

?>
