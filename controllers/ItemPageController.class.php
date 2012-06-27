<?php

class ItemPageController {
	// --------------------------------------------------
	// Item proof handler
	// URL: /projects/PROJECT/items/ITEM/proof
	// Methods: 

	static public function itemProof($params) {
		$format = self::getFormat($params['args'], 0, 2);
		$projectPage = self::getProjectPageType($params['args']);

		$projectSlugIndex = ($projectPage == 'system') ? 0 : 2;
		$projectSlug = $params['args'][$projectSlugIndex];

		$itemIndex = ($projectPage == 'system') ? 1 : 3;
		$itemId = $params['args'][$itemIndex];

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
					$code = "not-a-member";
				}

				// Make sure the user has this item in their queue
				// TODO: Finish
				$userQueue = new Queue("user.proof:$username");

				// Load the item
				$itemObj = new Item($itemId, $projectSlug, $username);

				$item = array();
				$item['id'] = $itemId;
				$item['title'] = $itemObj->title;
				$item['href'] = $itemObj->href;

				$stripped = stripslashes($itemObj->transcript);
				$escaped = str_replace("<", "&lt;", $stripped);
				$item['transcript'] = str_replace(">", "&gt;", $escaped);

				$item['project_slug'] = $projectSlug;

				// Check to see if there's another item to proof
				// - Load project proof queue
				// - Get count > 0
				// TODO: Finish
				$moreToProof = true;

				// Get template type
				$templateType = $itemObj->type;

				// Set up CSS/JS arrays
				$css = array("editor_$templateType.css");
				$js = array("editor_$templateType.js");

				// TODO: figure out a more modular way to specify and prepare these
				$templateSpecificOptions = array();
				switch ($templateType) {
					case 'page':
						break;
					case 'audio':
						array_push($js, 'editor_audio/soundmanager2-nodebug-jsmin.js');
						break;
				}

				// Display the template
				$options = array(
					'user' => array(
						'loggedin' => true,
						'admin' => $user->admin,
						),
					'item' => $item,
					'more_to_proof' => $moreToProof,
					'template_specific_options' => $templateSpecificOptions,
					'css' => $css,
					'js' => $js,
				);

				Template::render("editor_$templateType", $options);

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
	// URL: /projects/PROJECT/items/ITEM/transcript
	// Methods: 

	static public function transcript($params) {
		echo "Item transcript (" . $params['method'] . "): ";
		print_r($params['args']);
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
			redirectToDashboard("", "Error saving page.");
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
			redirectToDashboard("", "Invalid page/project ID");
		}

		$username = $auth->getUsername();
		$user = new User($username);
		// make sure they're an admin
		if (!$user->admin) {
			redirectToDashboard("", "You're not an administrator.");
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
			redirectToDashboard("", "Invalid item/project ID or username");
		}

		// get the current user's role on the project and make sure they're owner or admin
		$username = $auth->getUsername();
		$user = new User($username);
		$role = $user->getRoleForProject($project_slug);

		if (!$user->admin && $role != "owner") {
			redirectToDashboard("", "You don't have rights to review that item.");
		}

		// get the proofer's user object so we can see their status
		$proofer = new User($proofer_username);

		if ($proofer->status == "") {
			redirectToDashboard("", "That user doesn't exist.");
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

	static public function editPageHandler($args, $next = false) {
		$app_url = Settings::getProtected('app_url');
		$db = Settings::getProtected('db');
		$editor = Settings::getProtected('editor');
		$auth = Settings::getProtected('auth');

		$auth->forceAuthentication();

		$project_slug = $args[0];
		$page_id = $args[1];
		if (!$page_id || !$project_slug) {
			redirectToDashboard("", "Invalid page/project ID");
		}

		$username = $auth->getUsername();
		// make sure they're assigned to this page
		$user = new User($username);
		if (!$user->isAssigned($page_id, $project_slug)) {
			redirectToDashboard("", "You're not assigned to that page.");
		}

		// get the item from the database
		$itemObj = new Item($db);
		$itemObj->load($page_id, $project_slug, $username);

		$item = array();
		$item['id'] = $page_id;
		$item['title'] = $itemObj->title;
		$item['href'] = $itemObj->href;

		$stripped = stripslashes($itemObj->transcript);
		$escaped = str_replace("<", "&lt;", $stripped);
		$item['escaped_stripped_itemtext'] = str_replace(">", "&gt;", $escaped);

		$options = array(
			'user' => array(
				'loggedin' => true,
				'admin' => $user->admin),
			'project_slug' => $project_slug,
			'item' => $item
		);

		Template::render('edit_page', $options);
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
			redirectToDashboard("", "Error saving page.");
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
