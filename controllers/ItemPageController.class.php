<?php

class ItemPageController {
	// --------------------------------------------------
	// Item proof handler
	// URL: /projects/PROJECT/items/ITEM/proof OR /users/USER/projects/PROJECT/items/ITEM/proof
	// Methods: 

	static public function itemProof($params) {
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
			// GET: Get proof page for this item
			case 'GET':
				// Make sure they have access to the project
				if (!$user->isMember($projectSlug)) {
					Utils::redirectToDashboard("", "You're not a member of that project. Sorry.");
					return;
				}

				// Load the item
				$itemObj = new Item($itemId, $projectSlug, $username);

				// Make sure it exists (if it fails, it'll return a boolean)
				if ($itemObj->item_id == -1) {
					Utils::redirectToDashboard("", "Item doesn't exist.");
					return;
				}

				// Make sure the user has this item in their queue
				// TODO: Finish
				$userQueue = new Queue("user.proof:$username", false, array('include-removed' => true));
				$userQueueItems = $userQueue->getItems();
				if (!in_array($itemObj, $userQueueItems)) {
					Utils::redirectToDashboard("", "You don't have that item in your queue.");
					return;
				}

				// See if there are any items left for us to proof
				$moreToProof = false;
				$queue = new Queue("project.proof:$projectSlug");

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
				$item['transcript'] = str_replace(">", "&gt;", str_replace("<", "&lt;", stripslashes($transcript)));

				$item['project_slug'] = $projectSlug;
				$item['project_owner'] = $owner;
				$item['project_type'] = ($owner == '') ? 'public' : 'private';

				// Check to see if there's another item to proof
				// - Load project proof queue
				// - Get count > 0
				// TODO: Finish

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
						),
					'item' => $item,
					'more_to_proof' => $moreToProof,
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
				// Make sure they have access to the project
				if (!$user->isMember($projectSlug, $owner)) {
					$code = "not-a-member";
					// TODO: fail gracefully here, redirect to dashboard with error
					echo "You're not a member of that project. Sorry.";
					return;
				}

				// Load the item
				$itemObj = new Item($itemId, $projectSlug, $username);

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
				$transcript->load(array('item' => $itemObj));
				$transcript->setText($transcriptText);
				$transcript->save(array('item' => $itemObj, 'status' => $transcriptStatus));

				if ($transcriptStatus == 'completed' || $transcriptStatus == 'reviewed') {
					$scoring = Settings::getProtected('scoring');

					// Notifications
					if ($transcriptStatus == 'reviewed') {
						// Bump user's score up if they haven't already reviewed this page
						$user->updateScoreForItem($itemObj->item_id, $itemObj->project_id, $scoring['review']);

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
						$user->updateScoreForItem($itemObj->item_id, $itemObj->project_id, $scoring['proof']);

						if ($user->status == 'training') {
							// Notify project owner with link to review for clearance
						}
					}

					// Remove from user's queue
					$userQueue = new Queue("user.proof:$username");
					$userQueue->remove($itemObj);
					$userQueue->save();

					// Increase item's workflow index
					$itemObj->workflow_index += 1;
					$itemObj->save();

					// Load the project
					$project = new Project($itemObj->project_slug);

					// Get next workflow step
					$workflow = new Workflow($project->workflow);
					$workflow->setIndex($itemObj->workflow_index);

					$workflowQueue = $workflow->getWorkflow();

					if ($itemObj->workflow_index < count($workflowQueue) - 1) {
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
				$dispatch = Settings::getProtected('dispatch');
				$dispatch->init(array('username' => $username, 'projectSlug' => $projectSlug));
				$response = $dispatch->next();

				if ($response['status'] == true) {
					$itemId = $response['code'];

					// Load the item to make sure it's real
					$item = new Item('', $itemId, $projectSlug, $username);

					// Verification check
					if ($item->status == 'available') {
						// Put it in the user's queue
						$queue = new Queue("user.proof:$username", true);
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





	static public function adminUploadHandler($args) {
		$app_url = Settings::getProtected('app_url');
		$auth = Settings::getProtected('auth');

		$slug = $args[0];

		$auth->forceAuthentication();
		$username = $auth->getUsername();
		$user = new User($username);

		$includes = "<link href='$app_url/lib/uploadify/uploadify.css' type='text/css' rel='stylesheet' />\n";
		$includes .= "<script type='text/javascript' src='$app_url/lib/uploadify/swfobject.js'></script>\n";
		$includes .= "<script type='text/javascript' src='$app_url/lib/uploadify/jquery.uploadify.v2.1.4.min.js'></script>\n";
		$includes .= "<script type='text/javascript'>\n";
		$includes .= "	$(document).ready(function() {\n";
		$includes .= "		$('#file_upload').uploadify({\n";
		$includes .= "			'uploader'  : '$app_url/lib/uploadify/uploadify.swf',\n";
		$includes .= "			'script'    : '$app_url/admin/upload_backend/',\n";
		$includes .= "			'cancelImg' : '$app_url/lib/uploadify/cancel.png',\n";
		$includes .= "			'folder'    : '/media/$slug',\n";
		$includes .= "			'fileDataName' : 'items',\n";
		$includes .= "			'removeCompleted' : false,\n";
		$includes .= "			'multi'     : true,\n";
		$includes .= "			'auto'      : true,\n";
		$includes .= "			'onAllComplete' : function(event, data) {\n";
		$includes .= "				load_items_for_editing(event, data);\n";
		$includes .= "			}\n";
		$includes .= "		});\n";
		$includes .= "	});\n";
		$includes .= "</script>\n";

		$options = array(
			'user' => array(
				'loggedin' => true,
				'admin' => $user->admin),
			'includes' => $includes,
			'slug' => $slug
		);

		Template::render('admin_upload', $options);
	}

	static public function adminUploadBackendHandler($args) {
		if (!empty($_FILES)) {
			$tempFile = $_FILES['items']['tmp_name'];
			$targetPath = dirname(__FILE__) . $_REQUEST['folder'] . '/';
			$targetFile = str_replace('//', '/', $targetPath) . $_FILES['items']['name'];

			move_uploaded_file($tempFile, $targetFile);
			echo str_replace(dirname(__FILE__), '', $targetFile);
		}
	}

	static public function adminSavePageHandler($args) {
		$app_url = Settings::getProtected('app_url');
		$db = Settings::getProtected('db');
		$auth = Settings::getProtected('auth');

		$auth->forceAuthentication();
		$username = $auth->getUsername();
		$user = new User($username);

		// Get info from POST
		$page_id = (array_key_exists('page_id', $_POST)) ? $_POST['page_id'] : '';
		$project_slug = (array_key_exists('project_slug', $_POST)) ? $_POST['project_slug'] : '';
		$page_title = (array_key_exists('page_title', $_POST)) ? $_POST['page_title'] : '';
		$page_text = (array_key_exists('page_text', $_POST)) ? $_POST['page_text'] : '';
		$next = (array_key_exists('next', $_POST)) ? $_POST['next'] : '';

		// Load the page from the database
		$page = new Item($db, $page_id, $project_slug);

		// Update the values
		$page->title = $page_title;
		$page->transcript = $page_text;

		// Save it to the database
		$retval = $page->save();

		// Check for success
		if ($retval) {
			if ($next) {
				// serve up next page
				$nextpage_id = $page->getNextItem();
				if ($nextpage_id) {
					// we go to new_page/ instead of edit/ so that we keep getting next
					header("Location: $app_url/admin/new_page/$project_slug/$nextpage_id");
				} else {
					// run out of pages, go back to the admin project page
					header("Location: $app_url/admin/projects/$project_slug");
				}
			} else {
				// go back to the admin project page
				header("Location: $app_url/admin/projects/$project_slug");
			}
		} else {
			// redirect to error page
			Utils::redirectToDashboard("", "Error saving page.");
		}
	}

	static public function adminEditPageHandler($args, $next = false) {
		$app_url = Settings::getProtected('app_url');
		$db = Settings::getProtected('db');
		$auth = Settings::getProtected('auth');

		$auth->forceAuthentication();

		$project_slug = $args[0];
		$page_id = $args[1];
		if (!$page_id || !$project_slug) {
			Utils::redirectToDashboard("", "Invalid page/project ID");
		}

		$username = $auth->getUsername();
		$user = new User($username);
		// make sure they're an admin
		if (!$user->admin) {
			Utils::redirectToDashboard("", "You're not an administrator.");
		}

		// get the page from the database
		$pageObj = new Item($db);
		$pageObj->load($page_id, $project_slug);
		$page = array();
		$page['id'] = $page_id;
		$page['stripped_itemtext'] = stripslashes($pageObj->transcript);
		$page['title'] = $pageObj->title;
		$page['href'] = $pageObj->href;

		if ($next) { 
			$savepage = "Save and Go to Next";
		} else {
			$savepage = "Save Page";
		}

		$options = array(
			'user' => array(
				'loggedin' => true,
				'admin' => $user->admin),
			'project_slug' => $project_slug,
			'page' => $page,
			'next' => $next,
			'savepage' => $savepage
		);

		Template::render('admin_edit_page', $options);
	}

	static public function adminNewPageHandler($args) {
		Handlers::adminEditPageHandler($args, true);
	}

	static public function adminReviewPageHandler($args, $next = false) {
		$app_url = Settings::getProtected('app_url');
		$db = Settings::getProtected('db');
		$auth = Settings::getProtected('auth');

		$auth->forceAuthentication();

		$project_slug = $args[0];
		$page_id = $args[1];
		$proofer_username = $args[2];	// the user who proofed the text

		if (!$page_id || !$project_slug || !$proofer_username) {
			Utils::redirectToDashboard("", "Invalid item/project ID or username");
		}

		// get the current user's role on the project and make sure they're owner or admin
		$username = $auth->getUsername();
		$user = new User($username);
		$role = $user->getRoleForProject($project_slug);

		if (!$user->admin && $role != "owner") {
			Utils::redirectToDashboard("", "You don't have rights to review that item.");
		}

		// get the proofer's user object so we can see their status
		$proofer = new User($proofer_username);

		if ($proofer->status == "") {
			Utils::redirectToDashboard("", "That user doesn't exist.");
		}

		// get the item from the database
		$item = new Item($db);
		$item->load($page_id, $project_slug, $proofer_username);
		$item->stripped_itemtext = stripslashes($item->transcript);

		$options = array(
			'user' => array(
				'loggedin' => true,
				'admin' => $user->admin),
			'project_slug' => $project_slug,
			'proofer' => $proofer,
			'item' => $item
		);

		Template::render('admin_review_page', $options);
	}

	static public function savePageHandler($args) {
		$app_url = Settings::getProtected('app_url');
		$db = Settings::getProtected('db');
		$auth = Settings::getProtected('auth');

		$auth->forceAuthentication();
		$username = $auth->getUsername();
		$user = new User($username);

		// Get info from POST
		$page_id = (array_key_exists('page_id', $_POST)) ? $_POST['page_id'] : '';
		$project_slug = (array_key_exists('project_slug', $_POST)) ? $_POST['project_slug'] : '';
		$page_title = (array_key_exists('page_title', $_POST)) ? $_POST['page_title'] : '';
		$page_text = (array_key_exists('page_text', $_POST)) ? $_POST['page_text'] : '';
		$next = (array_key_exists('next', $_POST)) ? $_POST['next'] : '';

		// Load the page from the database
		$page = new Item($db, $page_id, $project_slug);

		// Update the values
		$page->title = $page_title;
		$page->transcript = $page_text;

		// Save it to the database
		$retval = $page->save();

		// Check for success
		if ($retval) {
			if ($next) {
				// serve up next page
				$nextpage_id = $page->getNextItem();
				if ($nextpage_id) {
					// we go to new_page/ instead of edit/ so that we keep getting next
					header("Location: $app_url/admin/new_page/$project_slug/$nextpage_id");
				} else {
					// run out of pages, go back to the admin project page
					header("Location: $app_url/admin/projects/$project_slug");
				}
			} else {
				// go back to the admin project page
				header("Location: $app_url/admin/projects/$project_slug");
			}
		} else {
			// redirect to error page
			Utils::redirectToDashboard("", "Error saving page.");
		}
	}

	static public function getProjectPageType($args) {
		if ($args[0] == 'users') {
			return 'user';
		} else {
			return 'system';
		}
	}

	static public function getFormat($args, $systemIndex, $userIndex) {
		$projectPage = self::getProjectPageType($args);
		$formatIndex = ($projectPage == 'system') ? $systemIndex : $userIndex;
		return $args[$formatIndex] != '' ? $args[$formatIndex] : 'html';
	}
}

?>
