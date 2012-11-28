<?php

class ItemPageController {
	// --------------------------------------------------
	// Item proof/review handler
	// URL: /projects/PROJECT/items/ITEM/(proof|review|edit) OR /users/USER/projects/PROJECT/items/ITEM/proof/(proof|review|edit)
	// Optional username at end for proof|review to view existing proof/review
	// Methods: GET = get item proof page
	// Format:  HTML

	static public function itemProof($params) {
		$i18n = Settings::getProtected('i18n');

		$format = Utils::getFormat($params['args'], 0, 2);
		$projectType = Utils::getProjectType($params['args']);

		$projectSlugIndex = ($projectType == 'system') ? 0 : 2;
		$projectSlug = $params['args'][$projectSlugIndex];
		$project = new Project($projectSlug);

		$itemIndex = ($projectType == 'system') ? 1 : 3;
		$itemId = $params['args'][$itemIndex];

		$proofTypeIndex = ($projectType == 'system') ? 2 : 4;
		$proofType = $params['args'][$proofTypeIndex];
		$role = $proofType . "er";

		$proofUserIndex = ($projectType == 'system') ? 3 : 5;
		$proofUser = (array_key_exists($proofUserIndex, $params['args'])) ? $params['args'][$proofUserIndex] : '';

		$owner = ($projectType == 'user') ? $params['args'][1] : '';

		$user = User::getAuthenticatedUser();

		switch ($params['method']) {
			// GET: Get proof/review/edit page for this item
			case 'GET':
				// Make sure they have access to the item
				if ($proofType == 'edit' || $proofUser != '') {
					// For editing an item or a specific proof/review, user must be project admin or site admin
					RoleController::forceClearance(
						array('project.admin', 'project.owner', 'system.admin'),
						$user,
						array('project' => $project)
					);
				} else { 
					// User has to be a member of the project
					if (!$user->isMember($projectSlug, $role)) {
						Utils::redirectToDashboard("", $i18n->t("error.not_a_member"));
						return;
					}
				}

				// If we're looking at an existing proof/review, load it for that user
				// Otherwise load it for the existing user
				$username = ($proofUser != '') ? $proofUser : $user->username;

				// Load the item
				$itemObj = new Item($itemId, $projectSlug, $username, $proofType);

				// Make sure it exists (if it fails, it'll return a boolean)
				if ($itemObj->item_id == -1) {
					Utils::redirectToDashboard("", $i18n->t("error.nonexistent_item"));
					return;
				}

				$alreadyFinished = false;
				$moreToProof = false;

				if ($proofType != 'edit' && $proofUser == '') {
					// If it's not in their current queue, they're editing it after finishing it
					// TODO: Make this part more elegant
					$userCurrentQueue = new Queue("user.$proofType:{$user->username}", false);
					$userCurrentQueueItems = $userCurrentQueue->getItems();
					if (!in_array($itemObj, $userCurrentQueueItems)) {
						$alreadyFinished = true;
					}

					// And if it's not in their full queue, they never had it and shouldn't be allowed to proof it
					$userQueue = new Queue("user.$proofType:{$user->username}", false, array('include-removed' => true));
					$userQueueItems = $userQueue->getItems();
					if (!in_array($itemObj, $userQueueItems)) {
						Utils::redirectToDashboard("", $i18n->t("error.insufficient_rights"));
						return;
					}

					// See if there are any items left for us to proof
					$queue = new Queue("project.$proofType:$projectSlug");

					foreach ($queue->getItems() as $item) {
						if (!in_array($item, $userQueueItems)) {
							$moreToProof = true;
							break;
						}	
					}
				}

				$item = array();
				$item['id'] = $itemId;
				$item['title'] = $itemObj->title;

				// If the user has a transcript for this item, load it instead
				if ($itemObj->userTranscript && trim($itemObj->userTranscript['transcript']) != '') {
					$transcript = trim($itemObj->userTranscript['transcript']);
				} else {
					$transcript = trim($itemObj->transcript);
				}

				$item['transcript'] = stripslashes($transcript);

				// Get fields, if any
				if ($itemObj->userTranscript && trim($itemObj->userTranscript['fields']) != '') {
					$itemFields = json_decode(trim($itemObj->userTranscript['fields']), true);
				} else {
					$itemFields = array();
				}
				$item['fields'] = $itemFields;

				// Prepare the URL
				$appUrl = Settings::getProtected('app_url');

				if ($projectType == 'system') {
					$projectUrl = "projects/$projectSlug";
				} else if ($projectType == 'user') {
					$projectUrl = "users/$owner/$projectSlug";
				}
				$item['href'] = $projectUrl . "/" . $itemObj->href;

				// Get template type
				$templateType = $itemObj->type;

				// Get project fields and parse out
				$fieldsText = trim($project->fields);
				$fieldsLines = explode("\n", $fieldsText);
				$fields = array();
				foreach ($fieldsLines as $line) {
					$fieldLabel = '';
					$fieldType = '';
					$fieldValues = array();

					// Split it by label and type/parameters
					list($fieldLabel, $fieldSettings) = array_map('trim', explode(":", $line));
					if (strpos($fieldSettings, ' - ') == FALSE) {
						$fieldType = trim($fieldSettings);
					} else {
						list($fieldType, $fieldValueStr) = array_map('trim', explode(" - ", $fieldSettings));
						$fieldValues = explode(" | ", $fieldValueStr);	
					}

					// Reformat the field ID
					$fieldId = str_replace(" ", "_", strtolower($fieldLabel));

					$field = array(
						'id' => $fieldId,
						'label' => $fieldLabel,
						'type' => $fieldType,
						'values' => $fieldValues
					);

					array_push($fields, $field);
				}

				// Get any editor-specific config settings
				$editors = Settings::getProtected('editors');
				$editorOptions = (array_key_exists($templateType, $editors)) ? $editors[$templateType] : array();

				$pageTitle = ucfirst($proofType) . " " . $item['title'];
				if ($proofUser) $pageTitle .= " ($proofUser)";
				$pageTitle .= " | " . $project->title;
				
				// Display the template
				$options = array(
					'page_title' => $pageTitle,
					'user' => $user->getResponse(),
					'item' => $item,
					'project' => $project->getResponse(),
					'more_to_proof' => $moreToProof,
					'already_finished' => $alreadyFinished,
					'editor_options' => $editorOptions,
					'editor_type' => $templateType,
					'proof_user' => $proofUser,
					'proof_type' => $proofType,
					'fields' => $fields,
					'css' => array("editors/$templateType/$templateType.css"),
					'js' => array("editors/$templateType/$templateType.js"),
				);

				Template::render("editors/$templateType", $options);

				break;
		}
	}


	// --------------------------------------------------
	// Item transcript handler
	// URL: /projects/PROJECT/items/ITEM/transcript OR /users/USER/projects/PROJECT/items/ITEM/transcript
	// Methods: POST = post transcript for item

	static public function transcript($params) {
		$format = Utils::getFormat($params['args'], 0, 2);
		$projectType = Utils::getProjectType($params['args']);

		$projectSlugIndex = ($projectType == 'system') ? 0 : 2;
		$projectSlug = $params['args'][$projectSlugIndex];

		$itemIndex = ($projectType == 'system') ? 1 : 3;
		$itemId = $params['args'][$itemIndex];

		$owner = ($projectType == 'user') ? $params['args'][1] : '';

		$user = User::getAuthenticatedUser();

		switch ($params['method']) {
			// POST: Post transcript for item
			case 'POST':
				$proofType = Utils::POST('proofType');
				$proofUser = Utils::POST('proofUser');
				$transcriptText = Utils::POST('transcript');
				$transcriptStatus = Utils::POST('status');		// draft, completed, reviewed
				$fields = Utils::POST('fields');
				$role = $proofType . "er";

				// Make sure they have access to the item
				if ($proofType == 'edit' || $proofUser != '') {
					// For editing an item or a specific proof/review, user must be project admin or site admin
					RoleController::forceClearance(
						array('project.admin', 'project.owner', 'system.admin'),
						$user,
						array('project' => $project)
					);
				} else { 
					// User has to be a member of the project
					if (!$user->isMember($projectSlug, $role, $owner)) {
						Utils::redirectToDashboard("", $i18n->t("error.not_a_member"));
						return;
					}
				}

				// If we're looking at an existing proof/review, load it for that user
				// Otherwise load it for the existing user
				$username = ($proofUser != '') ? $proofUser : $user->username;

				// Load the item
				$itemObj = new Item($itemId, $projectSlug, $username, $proofType);

				// Make sure item exists (if it fails, it'll return a boolean)
				if ($itemObj->item_id == -1) {
					Utils::redirectToDashboard("", $i18n->t("error.nonexistent_item"));
					return;
				}

				// Make sure the user has this item in their queue
				// TODO: Finish

				if ($proofType == 'edit') {
					// Set the transcript and save the item
					$itemObj->transcript = $transcriptText;
					$itemObj->save();
				} else {
					// Save transcript to database
					$transcript = new Transcript();
					$transcript->load(array('item' => $itemObj, 'type' => $proofType));
					$transcript->setText($transcriptText);
					$transcript->setFields($fields);
					$transcript->save(array('item' => $itemObj, 'status' => $transcriptStatus, 'type' => $proofType));

					// If we're looking at a user's proof or review, or if the transcript has already been
					// completed/reviewed, then we don't want to update scoring and move the item through
					// the workflow index
					if ($proofUser == '' && $transcriptStatus != 'draft') {
						$scoring = Settings::getProtected('scoring');

						// Notifications
						if ($transcriptStatus == 'reviewed') {
							// Bump user's score up if they haven't already reviewed this item 
							$user->updateScoreForItem($itemObj->item_id, $itemObj->project_id, $scoring['review'], 'review');

							// Notify project owner that review is complete
						} else if ($transcriptStatus == 'completed') {
							// Bump user's score up if they haven't already proofed this item
							$user->updateScoreForItem($itemObj->item_id, $itemObj->project_id, $scoring['proof'], 'proof');
						}

						// Remove from user's queue
						$userQueue = new Queue("user.$proofType:{$user->username}");
						$userQueue->remove($itemObj);
						$userQueue->save();

						// Increase item's workflow index
						$itemObj->workflow_index += 1;

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
				}

				echo json_encode(array("statuscode" => "success"));

				break;
		}
	}


	// --------------------------------------------------
	// Item handler
	// URL: /projects/PROJECT/items/ITEM
	// Methods: GET = get item info
	//          POST = save item info

	static public function item($params) {
		// TODO: write
	}


	// --------------------------------------------------
	// Item delete handler
	// URL: /projects/PROJECT/items/ITEM/delete
	// Methods: POST = delete item

	static public function deleteItem($params) {
		$format = Utils::getFormat($params['args'], 2, 4);
		$projectType = Utils::getProjectType($params['args']);

		$projectSlugIndex = ($projectType == 'system') ? 0 : 2;
		$projectSlug = $params['args'][$projectSlugIndex];
		$project = new Project($projectSlug);

		$itemIndex = ($projectType == 'system') ? 1 : 3;
		$itemId = $params['args'][$itemIndex];

		$user = User::getAuthenticatedUser();

		switch ($params['method']) {
			// POST: Delete an item
			case 'POST':
				$status = 'success';
				$message = '';

				// Make sure the user is project admin or site admin
				RoleController::forceClearance(
					array('project.admin', 'project.owner', 'system.admin'),
					$user,
					array('project' => $project)
				);

				// Load item to make sure it exists
				$item = new Item($itemId, $projectSlug);

				// Delete the file
				Media::removeFileForItem($item);

				// Delete from project proof queue
				$queue = new Queue("project.proof:{$project->slug}", false);
				$queue->remove($item);
				$queue->save();

				// Delete from project review queue (if it's there)
				$queue = new Queue("project.review:{$project->slug}", false);
				$queue->remove($item);
				$queue->save();

				// Delete from database
				if (!$item->deleteFromDatabase()) {
					$status = 'error';
					$message = 'errors.deleting_item';
				}

				echo json_encode(array('status' => $status, 'message' => $message));

				break;
		}
	}


	// --------------------------------------------------
	// Get new item handler
	// URL: /projects/PROJECT/items/get OR /users/USER/projects/PROJECT/items/get
	// Methods: POST = get next available item

	static public function getNewItem($params) {
		$format = Utils::getFormat($params['args'], 0, 2);
		$projectPage = Utils::getProjectType($params['args']);
		$projectSlugIndex = ($projectPage == 'system') ? 0 : 2;
		$projectSlug = $params['args'][$projectSlugIndex];

		$user = User::getAuthenticatedUser();

		switch ($params['method']) {
			// POST: Get next available item
			case 'POST':
				$type = Utils::POST('type');		// proof or review

				$dispatch = Settings::getProtected('dispatch');
				$dispatch->init(array('username' => $user->username, 'projectSlug' => $projectSlug, 'type' => $type));
				$response = $dispatch->next();

				if ($response['status'] == true) {
					$itemId = $response['code'];

					// Load the item to make sure it's real
					$item = new Item('', $itemId, $projectSlug, $user->username, $type);

					// Verification check
					if ($item->status == 'available') {
						// Put it in the user's queue
						$queue = new Queue("user.$type:{$user->username}", true);
						$queue->add($item);
						$queue->save();
					}
				}

				echo json_encode($response);

				break;
		}
	}


	// --------------------------------------------------
	// Items handler
	// URL: /projects/PROJECT/items or /users/USER/projects/PROJECT/items
	// Methods: GET = get list of items for project
	//          POST = send uploaded files through item type uploader

	static public function items($params) {
		$format = Utils::getFormat($params['args'], 0, 2);
		$projectType = Utils::getProjectType($params['args']);

		$projectSlugIndex = ($projectType == 'system') ? 0 : 2;
		$projectSlug = $params['args'][$projectSlugIndex];

		switch ($params['method']) {
			// POST: Run uploaded files through item type uploader modules
			case 'POST':
				$fileList = Utils::POST('fileList');

				$items = array();

				foreach ($fileList as $file) {
					// Get extension
					$ext = pathinfo($file, PATHINFO_EXTENSION);

					// Default uploader type 
					$uploaderType = "Page";

					// Get the uploader type from the settings
					$uploaders = Settings::getProtected('uploaders');
					foreach ($uploaders as $type=>$data) {
						if (in_array($ext, $data['extensions'])) {
							$uploaderType = $type;
							break;
						}
					}

					// Load the appropriate class
					require_once "../modules/uploaders/{$uploaderType}Uploader.class.php";

					$uploaderClass = "{$uploaderType}Uploader";
					$uploader = new $uploaderClass($projectSlug);

					// Call the uploader (it takes an array)
					$returnedItems = $uploader->upload(array($file));

					// Merge the arrays
					$items = array_merge($items, $returnedItems);
				}

				// Create a JSON-ready version
				$finalItems = array();
				foreach ($items as $item) {
					$newItem = array(
						"id" => $item->item_id,
						"title" => $item->title,
						"project_id" => $item->project_id,
						"transcript" => $item->transcript,
						"type" => $item->type,
						"href" => $item->href
					);

					array_push($finalItems, $newItem);
				}

				$uploader = new ItemTypeUploader($projectSlug);
				$uploader->cleanup();

				echo json_encode(array('status' => 'success', 'items' => $finalItems));

				break;
		}
	}
}

?>
