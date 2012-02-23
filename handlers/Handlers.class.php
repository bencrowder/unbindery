<?php

class Handlers {
	static public function indexHandler($args) {
		$app_url = Settings::getProtected('app_url');
		$auth = Settings::getProtected('auth');
		$externalLogin = Settings::getProtected('external_login');
		$allowSignup = Settings::getProtected('allow_signup');

		if ($auth->authenticated()) {
			$username = $auth->getUsername();

			// Check to see if they have an account
			if ($auth->hasAccount($username)) {
				header("Location: $app_url/dashboard");
			} else {
				// Create account and email confirmation link to user
				$user = new User($username);
				$auth->createAccount($user);

				// Redirect back to index with message
				$options = array(
					'user' => array(
						'loggedin' => false
						),
				);

				Page::render('confirmation', $options);
			}
		} else {
			if ($externalLogin) {
				$auth->redirectToLogin();
			} else {
				$options = array(
					'user' => array(
						'loggedin' => false
						),
					'allow_signup' => $allowSignup,
					'includes' => "<script src='$app_url/js/index.js' type='text/javascript'></script>\n"
				);

				Page::render('index', $options);
			}
		}
	}

	static public function loginHandler($args) {
		$app_url = Settings::getProtected('app_url');
		$db = Settings::getProtected('db');
		$auth = Settings::getProtected('auth');

		$username = $_POST["username"];
		$password = $_POST["password"];

		if ($auth->login($username, $password)) {
			$user = new User($username);
			$user->updateLogin();						// updates last_login time in database

			header("Location: $app_url/dashboard");
		} else {
			$auth->redirectToLogin("Login failed");
		}
	}

	static public function logoutHandler($args) {
		$app_url = Settings::getProtected('app_url');
		$auth = Settings::getProtected('auth');

		$auth->logout($app_url);
	}

	static public function signupHandler($args) {
		$app_url = Settings::getProtected('app_url');
		$db = Settings::getProtected('db');
		$auth = Settings::getProtected('auth');

		$email = $_POST["email_signup"];
		$username = $_POST["username_signup"];
		$password = $_POST["password_signup"];

		// Add user to database with "pending" as status
		$user = new User();
		$user->username = $username;
		$user->password = md5($password);
		$user->email = $email;
		$user->save();

		// Now go back to the home page to create the account
		if ($auth->login($username, $password)) {
			header("Location: $app_url");
		} else {
			error_log("Login didn't work.");
		}

		// return "done" (so Ajax can replace the div)
		//echo json_encode(array("statuscode" => "done", "username" => $user->username));
	}

	static public function dashboardHandler($args) {
		$app_url = Settings::getProtected('app_url');
		$db = Settings::getProtected('db');
		$auth = Settings::getProtected('auth');

		$auth->forceAuthentication();

		$username = $auth->getUsername();
		$user = new User($username);
		$user->getStats();

		$server = new Server($db);

		$items = $user->getAssignments();
		$projects = $user->getProjects();
		$projectlist = array();
		$history = $user->getHistory();
		$topusers = $server->getTopUsers();

		foreach ($items as &$item) {
			$item["editlink"] = $app_url . '/edit/' . $item["project_slug"] . '/' . $item["item_id"];
			$projectlist[] = $item["project_slug"];
			
			$days_left = $item["days_left"];
			$deadline = $item["deadline"];

			if ($days_left <= 2 && $days_left >= 0) {
				$deadlineclass = " impending";
				$deadline = "in $days_left day";
				if ($days_left != 1) { $deadline .= "s"; }
			} else if ($days_left < 0) {
				$deadlineclass = " overdue";
				$deadline = ($days_left * -1) . " days ago";
			} else {
				$deadlineclass = "";
			}

			$item["deadline"] = $deadline;
			$item["deadlineclass"] = $deadlineclass;

			$item["title"] = $item["item_title"];
		}	

		foreach ($projects as &$project) {
			if (!in_array($project["slug"], $projectlist) && ($project["available_pages"] > 0)) {
				$project["available"] = true;
			}
			$project["link"] = $app_url . '/projects/' . $project["slug"];
			$project["percentage"] = round($project["completed"] / $project["total"] * 100, 0);
			$project["proof_percentage"] = round($project["proof_percentage"]);
		}

		foreach ($history as &$event) {
			$event["editlink"] = "$app_url/edit/" . $event["project_slug"] . "/" . $event["item_id"];	
			$event["title"] = $event["item_title"];
		}

		$options = array(
			'user' => array(
				'loggedin' => true,
				'admin' => $user->admin,
				'score' => $user->score,
				'proofed' => $user->proofed,
				'proofed_past_week' => $user->proofed_past_week),
			'items' => $items,
			'projects' => $projects,
			'history' => $history,
			'topusers' => $topusers,
			'item_count' => count($items),
			'project_count' => count($projects),
			'history_count' => count($history)
		);

		Page::render('dashboard', $options);
	}

	static public function settingsHandler($args) {
		$app_url = Settings::getProtected('app_url');
		$db = Settings::getProtected('db');
		$auth = Settings::getProtected('auth');

		$auth->forceAuthentication();

		$username = $auth->getUsername();
		$user = new User($username);

		$options = array(
			'user' => array(
				'loggedin' => true,
				'admin' => $user->admin,
				'name' => $user->name,
				'email' => $user->email),
		);

		Page::render('settings', $options);
	}

	static public function saveSettingsHandler($args) {
		$app_url = Settings::getProtected('app_url');
		$db = Settings::getProtected('db');
		$auth = Settings::getProtected('auth');

		$auth->forceAuthentication();

		$username = (array_key_exists('username', $_POST)) ? stripslashes($_POST['username']) : '';
		$user_name = (array_key_exists('user_name', $_POST)) ? stripslashes($_POST['user_name']) : '';
		$user_email = (array_key_exists('user_email', $_POST)) ? stripslashes($_POST['user_email']) : '';
		$user_oldpassword = (array_key_exists('user_oldpassword', $_POST)) ? stripslashes($_POST['user_oldpassword']) : '';
		$user_newpassword1 = (array_key_exists('user_newpassword1', $_POST)) ? stripslashes($_POST['user_newpassword1']) : '';
		$user_newpassword2 = (array_key_exists('user_newpassword2', $_POST)) ? stripslashes($_POST['user_newpassword2']) : '';

		if ($user_newpassword1 != "" && $user_newpassword1 == $user_newpassword2) {
			// verify that md5(oldpassword) == the password in the database
			$change_password = true;

			// else redirect to settings with an error
			//header("Location: $app_url/settings?message=Passwords didn't match. Try again.");
		}

		$db->updateUserSettings($username, $user_name, $user_email, $user_newpassword1);

		$_SESSION['ub_message'] = "Settings saved.";

		header("Location: $app_url/settings");
	}

	static public function projectHandler($args) {
		$app_url = Settings::getProtected('app_url');
		$db = Settings::getProtected('db');
		$auth = Settings::getProtected('auth');

		$project_slug = $args[0];
		$guidelines = false;
		if (array_key_exists(1, $args)) {
			if ($args[1] == 'guidelines') {
				$guidelines = true;
			}
		}

		$auth->forceAuthentication();

		$username = $auth->getUsername();
		$user = new User($username);

		// Load the project (and make sure it exists)
		$project = new Project($project_slug);
		if ($project->title == '') {
			redirectToDashboard('', 'Error loading project.');
		}
		$project->slug = $project_slug;

		$project->loadStatus();

		// find out if the user is admin or project owner so they can see the rest of the details
		$role = $user->getRoleForProject($project_slug);
		if ($role == "owner" || $user->admin) {
			$admin = true;
		} else {
			$admin = false;
		}

		$systemguidelines = Settings::getProtected('systemguidelines');

		$project->days_spent .= ' day' . ($project->days_spent == 1) ? '' : 's';
		if (isset($project->thumbnails)) {
			$project->thumbnails = explode(',', $project->thumbnails);
		}
		$project->proof_percentage_rounded = round($project->proof_percentage, 0);
		$project->percentage_rounded = round($project->percentage, 0);
		$project->total_proofs = $project->total * $project->num_proofs;

		$userismember = $user->isMember($project_slug);

		$proofers = $project->getProoferStats();
		foreach ($proofers as &$proofer) { 
			$proofer["pages"] .= ' page' . ($proofer['pages'] == 1) ? '' : 's';
			$proofer["percentage_rounded"] = round($proofer["percentage"], 0);
		}

		$options = array(
			'user' => array(
				'loggedin' => true,
				'admin' => $user->admin,
				'ismember' => $userismember),
			'project' => array(
				'slug' => $project_slug,
				'title' => $project->title,
				'author' => $project->author,
				'language' => $project->language,
				'description' => $project->description,
				'status' => $project->status,
				'date_started' => $project->date_started,
				'date_completed' => $project->date_completed,
				'date_posted' => $project->date_posted,
				'days_spent' => $project->days_spent,
				'percentage' => $project->percentage,
				'percentage_rounded' => $project->percentage_rounded,
				'proof_percentage' => $project->proof_percentage,
				'proof_percentage_rounded' => $project->proof_percentage_rounded,
				'proofed' => $project->proofed,
				'completed' => $project->completed,
				'total' => $project->total,
				'total_proofs' => $project->total_proofs,
				'guidelines' => $project->guidelines,
				'thumbnails' => $project->thumbnails),
			'proofers' => $proofers,
			'guidelines' => $guidelines,
			'systemguidelines' => $systemguidelines
		);

		Page::render('project', $options);
	}

	static public function joinProjectHandler($args) {
		$app_url = Settings::getProtected('app_url');
		$auth = Settings::getProtected('auth');

		$auth->forceAuthentication();

		$username = $auth->getUsername(); 
		$user = new User($username);

		$slug = (array_key_exists('slug', $_GET)) ? $_GET['slug'] : '';

		$retval = $user->assignToProject($slug);

		if (!$retval) {
			$_SESSION['ub_error'] = "Error joining project";
		}

		header("Location: $app_url/dashboard/");
	}

	static public function projectsHandler($args) {
		$app_url = Settings::getProtected('app_url');
		$db = Settings::getProtected('db');
		$auth = Settings::getProtected('auth');

		$auth->forceAuthentication();

		$username = $auth->getUsername();
		$user = new User($username);

		$server = new Server($db);
		$projects = $server->getProjects();
		foreach ($projects as &$project) {
			$project['link'] = $app_url . '/projects/' . $project['slug'];
			$project['proof_percentage_rounded'] = round($project['proof_percentage'], 0);
			$project['percentage_rounded'] = round($project['percentage'], 0);
		}

		$completedprojects = $server->getCompletedProjects();
		foreach ($completedprojects as &$project) {
			$project['link'] = $app_url . '/projects/' . $project['slug'];
		}	

		$options = array(
			'user' => array(
				'loggedin' => true,
				'admin' => $user->admin),
			'projects' => $projects
		);

		Page::render('projects', $options);
	}

	static public function adminProjectHandler($args) {
		$app_url = Settings::getProtected('app_url');
		$db = Settings::getProtected('db');
		$auth = Settings::getProtected('auth');

		if (array_key_exists(0, $args)) {
			$slug = $args[0];
			$mode = 'edit';
		} else {
			$slug = '';
			$mode = 'new';
		}

		$auth->forceAuthentication();

		$username = $auth->getUsername();
		$user = new User($username);

		if ($mode == "new") {
			$title = "Create New Project";
			$buttontitle = "Create Project";

			$project = array();
			$project['title'] = '';
			$project['author'] = '';
			$project['slug'] = '';
			$project['language'] = '';
			$project['guidelines'] = '';
			$project['thumbnails'] = '';

			$project['deadline'] = 7;
			$project['numproofs'] = 1;
			$project['desc'] = "[Publication date, number of pages, etc.]";
			$project['status'] = "pending";

			$items = array();
		} else {
			$title = "Edit Project Settings";
			$buttontitle = "Save Project";

			$projObj = new Project($slug);

			$project['title'] = stripslashes($projObj->title);
			$project['author'] = stripslashes($projObj->author);
			$project['slug'] = stripslashes($projObj->slug);
			$project['language'] = stripslashes($projObj->language);
			$project['deadline'] = stripslashes($projObj->deadline_days);
			$project['num_proofs'] = stripslashes($projObj->num_proofs);
			$project['desc'] = stripslashes($projObj->description);
			$project['guidelines'] = stripslashes($projObj->guidelines);
			$project['thumbnails'] = stripslashes($projObj->thumbnails);
			$project['status'] = $projObj->status;

			$items = $projObj->getItemsAndAssignments();
			foreach ($items as $item_id => &$item) {
				$item['id'] = $item_id;
			}
		}

		$options = array(
			'user' => array(
				'loggedin' => true,
				'admin' => $user->admin),
			'mode' => $mode,
			'slug' => $slug,
			'title' => $title,
			'buttontitle' => $buttontitle,
			'project' => $project,
			'items' => $items
		);

		Page::render('admin_project', $options);
	}

	static public function adminSaveProjectHandler($args) {
		$app_url = Settings::getProtected('app_url');
		$db = Settings::getProtected('db');
		$auth = Settings::getProtected('auth');

		$auth->forceAuthentication();
		$username = $auth->getUsername(); 

		$title = $_POST['project_title'];
		$author = $_POST['project_author'];
		$slug = $_POST['project_slug'];
		$language = $_POST['project_language'];
		$description = $_POST['project_desc'];
		$owner = $username;
		$guidelines = $_POST['project_guidelines'];
		$deadline_days = $_POST['project_deadline'];
		$num_proofs = $_POST['project_numproofs'];
		$thumbnails = $_POST['project_thumbnails'];
		$status = $_POST['project_status'];

		$mode = $_POST['mode'];

		if ($mode == 'new') {
			$project = new Project();

			$retval = $project->create($title, $author, $slug, $language, $description, $owner, $guidelines, $deadline_days, $num_proofs, $thumbnails);

			if ($retval == 'success') {
				// make project directory for media
				$dir = dirname(__FILE__) . "/media/$slug";

				$rs = @mkdir($dir, 0775, true);
				if ($rs) {
					// success! now create the media directory
					chmod($dir, 0775);
					// redirect to upload page
					header("Location: $app_url/admin/upload/$slug");
				} else {
					// redirect to error page
					redirectToDashboard("", "Error creating media directory. Check your file permissions.");
				}

			} else {
				// redirect to error page
				redirectToDashboard("", "Error creating project");
			}
		} else {							// editing an existing project
			$project = new Project($slug);

			$project->title = $title;
			$project->author = $author;
			$project->slug = $slug;
			$project->language = $language;
			$project->description = $description;
			$project->guidelines = $guidelines;
			$project->deadline_days = $deadline_days;
			$project->num_proofs = $num_proofs;
			$project->thumbnails = $thumbnails;
			$project->status = $status;

			$project->save();

			header("Location: $app_url/admin/projects/$slug");
		}
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

		Page::render('admin_upload', $options);
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
		$page->itemtext = $page_text;

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
		$page['stripped_itemtext'] = stripslashes($pageObj->itemtext);
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

		Page::render('admin_edit_page', $options);
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

		$includes = "<script type='text/javascript' src='$app_url/lib/ace/src/ace.js' charset='utf-8'></script>\n";
		$includes .= "<script type='text/javascript' src='$app_url/js/theme-unbindery.js' charset='utf-8'></script>\n";
		$includes .= "<script type='text/javascript' src='$app_url/js/edit.js' charset='utf-8'></script>\n";

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
		$item->stripped_itemtext = stripslashes($item->itemtext);

		$options = array(
			'user' => array(
				'loggedin' => true,
				'admin' => $user->admin),
			'project_slug' => $project_slug,
			'proofer' => $proofer,
			'item' => $item
		);

		Page::render('admin_review_page', $options);
	}

	static public function adminHandler($args) {
		$app_url = Settings::getProtected('app_url');
		$db = Settings::getProtected('db');

		$auth = Settings::getProtected('auth');
		$auth->forceAuthentication();

		$username = $auth->getUsername();
		$user = new User($username);

		// Get the current user's role on the project and make sure they're owner or admin
		$roleManager = new Role();
		if ($roleManager->forceClearance(array('role' => 'user:creator', 'user' => $user))) {
			$options = array(
				'user' => array(
					'loggedin' => true,
					'admin' => $user->admin),
			);

			Page::render('admin_dashboard', $options);
		}
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
		$item['escaped_stripped_itemtext'] = escapebrackets(stripslashes($itemObj->itemtext));

		$includes = "";
		if ($editor == "advanced") {
			$includes .= "<script type='text/javascript' src='$app_url/lib/ace/src/ace.js' charset='utf-8'></script>\n";
			$includes .= "<script type='text/javascript' src='$app_url/js/theme-unbindery.js' charset='utf-8'></script>\n";
		}
		$includes .= "<script type='text/javascript' src='$app_url/js/edit.js' charset='utf-8'></script>\n";

		$options = array(
			'user' => array(
				'loggedin' => true,
				'admin' => $user->admin),
			'project_slug' => $project_slug,
			'includes' => $includes,
			'item' => $item
		);

		Page::render('edit_page', $options);
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
		$page->itemtext = $page_text;

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

	static public function activateHandler($args) {
		$app_url = Settings::getProtected('app_url');
		$db = Settings::getProtected('db');
		$i18n = new I18n(Settings::getProtected('language'));

		$hash = $args[0];

		$user = new User();
		$status = $user->validateHash($hash);

		if ($status) {
			$_SESSION['ub_message'] = $i18n->t("signup.activated");
		} else {
			$_SESSION['ub_message'] = $i18n->t("signup.invalid_code");
		}

		header("Location: $app_url");
	}

	static public function fileNotFoundHandler() {
		echo "File not found.";
	}
}

?>
