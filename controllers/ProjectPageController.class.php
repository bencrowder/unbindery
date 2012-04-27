<?php

class ProjectPageController {

	// --------------------------------------------------
	// Projects handler
	// URL: /projects OR /users/[USER]/projects
	// Methods: GET = get list of projects
	//          POST = create new project

	static public function projects($params) {
		$format = self::getFormat($params['args'], 0, 2);

		switch ($params['method']) {
			// GET: Get list of projects
			case 'GET':
				echo "<h2>Getting list of projects</h2>";
				echo "(" . $format . ")";

				// Verify user access to the list

				if ($projectPage == 'system') {
					$projects = Project::getProjects();
				} else {
					// get user projects
				}

				switch ($format) {
					case 'json':
						echo json_encode($projects);
						break;
					case 'html':
						print_r($projects);
						break;
				}

				break;

			// POST: Create new project
			// Required parameters:
			// - name (string)
			// - type (public/private)
			// - owner (string)
			case 'POST':
				// Verify the POST elements
				// See what type it is (public or private)
				// Verify that the user is who they say they are
					// How are we going to do this?
				// Verify that the user can actually create the project
				// Verify that there isn't already a project with the same name/slug in that scope

				// Create the project
				$project = new Project();
				$project->title = Utils::POST('project_name');
				$project->type = Utils::POST('project_type');
				$project->description = Utils::POST('project_desc');
				$project->language = Utils::POST('project_lang');
				$project->workflow = Utils::POST('project_workflow');
				$project->fields = Utils::POST('project_fields');
				$project->whitelist = Utils::POST('project_whitelist');
				$project->guidelines = Utils::POST('project_guidelines');
				$project->owner = Utils::POST('project_owner');
				$project->status = 'pending';

				$project->slug = str_replace(' ', '-', strtolower($project->title));
				$project->slug = preg_replace('/[^a-z0-9-]+/i', '', $project->slug);

				// And add it to the database
				$status = $project->save();

				if ($status == true) {
					switch ($project->type) {
						case 'public':
							$project->url = "projects/" . $project->slug;
							$project->admin_url = "projects/" . $project->slug . "/admin";
							break;
						case 'private':
							$project->url = "users/" . $project->owner . "/projects/" . $project->slug;
							$project->admin_url = "users/" . $project->owner . "/projects/" . $project->slug . "/admin";
							break;
					}
				}

				$response = array("code" => $status, "project" => array("url" => $project->url, "admin_url" => $project->admin_url));

				switch ($format) {
					case 'json':
						// Return JSON
						$response["project"]["url"] .= ".json";
						$response["project"]["admin_url"] .= ".json";
						echo json_encode($response);
						break;
					case 'html':
						// Return HTML

						if ($status) {
							$app_url = Settings::getProtected('app_url');

							header("Location: $app_url/{$project->admin_url}");
						} else {
							echo "Error";
						}
						break;
				}

				break;
		}
	}


	// --------------------------------------------------
	// New project handler
	// URL: /projects/new-project OR /users/[USER]/projects/new-project
	// Methods: GET = get new project page
	// Formats: HTML

	static public function newProject($params) {
		// Parse parameters
		$format = self::getFormat($params['args'], 0, 1);

		// Authenticate
		$user = self::authenticate();

		// Verify clearance
		// TODO: add this

		// Output data

		switch ($params['method']) {
			// GET: Get new project page
			case 'GET':
				$options = array(
					'user' => array(
						'loggedin' => true,
						'admin' => $user->admin,
					),
				);

				Template::render('new_project', $options);
				break;
		}
	}


	// --------------------------------------------------
	// Project page handler
	// URL: /projects/[PROJECT] or /users/[USER]/projects/[PROJECT]
	// Methods: GET = get project info
	//          PUT = save project info
	//          DELETE = delete project

	static public function projectPage($params) {
		$format = self::getFormat($params['args'], 0, 2);

		switch ($params['method']) {
			case 'GET':
				break;

			case 'PUT':
				break;

			case 'DELETE':
				break;
		}

		echo "Project page (" . $params['method'] . "): ";
		print_r($params['args']);
	}


	// --------------------------------------------------
	// Project membership handler
	// URL: /projects/[PROJECT]/membership OR /users/[USER]/projects/[PROJECT]/membership
	// Methods: POST = join project
	//          DELETE = leave project

	static public function membership($params) {
		// Parse parameters
		$format = self::getFormat($params['args'], 1, 3);
		$projectPage = self::getProjectPageType($params['args']);
		$projectSlugIndex = ($projectPage == 'system') ? 0 : 2;
		$projectSlug = $params['args'][$projectSlugIndex];

		//$user = self::authenticate();
		$user = new User("bencrowder");

		switch ($params['method']) {
			// POST: join project
			case 'POST':
				// Load project
				$project = new Project($projectSlug);

				// If the project is public OR the user is on the whitelist, let them join
				if ($project->type == 'public' || $project->allowedToJoin($user->username)) {
					$status = ($user->assignToProject($projectSlug)) ? 'success' : 'error';
				} else {
					$status = 'access-denied';
				}

				echo json_encode(array('status' => $status));
				break;
			case 'DELETE':
				echo "Leaving project";

				// If we're a member
				// Delete membership record
				// Return status
				break;
		}
	}


	// --------------------------------------------------
	// Project admin handler
	// URL: /projects/[PROJECT]/admin OR /users/[USER]/projects/[PROJECT]/admin
	// Methods: GET = show admin page

	static public function admin($params) {
		$format = self::getFormat($params['args'], 1, 3);

		$user = self::authenticate();

		// Verify clearance
		// TODO: add this

		// Load the project
		$project_slug = (self::getProjectPageType($params['args']) == 'system') ? $params['args'][0] : $params['args'][2];
		$project = new Project($project_slug);

		if ($project->title == '') {
			redirectToDashboard('', 'Error loading project.');
		}

		// Load the project

		switch ($params['method']) {
			// GET: Get new project page
			case 'GET':
				$options = array(
					'user' => array(
						'loggedin' => true,
						'admin' => $user->admin,
					),
					'project' => array(
						'title' => $project->title,
						'id' => $project->project_id,
						'slug' => $project->slug,
						'type' => 'public',
						'language' => $project->language,
						'description' => $project->description,
						'owner' => $project->owner,
						'status' => $project->status,
						'thumbnails' => $project->thumbnails,
						'workflow' => $project->workflow,
						'whitelist' => $project->whitelist,
					),
				);

				Template::render('project_admin', $options);
				break;
		}
	}


	static public function projectHandler($args) {
		$app_url = Settings::getProtected('app_url');
		$db = Settings::getProtected('db');

		$project_slug = $args[0];
		$guidelines = false;
		if (array_key_exists(1, $args)) {
			if ($args[1] == 'guidelines') {
				$guidelines = true;
			}
		}

		$user = self::authenticate();

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

		Template::render('project', $options);
	}

	static public function joinProjectHandler($args) {
		$app_url = Settings::getProtected('app_url');

		$user = self::authenticate();

		$slug = (array_key_exists('slug', $_GET)) ? $_GET['slug'] : '';

		$retval = $user->assignToProject($slug);

		if (!$retval) {
			$_SESSION['ub_error'] = "Error joining project";
		}

		header("Location: $app_url/users/{$user->getUsername()}/dashboard/");
	}

	static public function projectsHandler($args) {
		$app_url = Settings::getProtected('app_url');
		$db = Settings::getProtected('db');

		$user = self::authenticate();

		$projects = Project::getProjects();
		foreach ($projects as &$project) {
			$project['link'] = $app_url . '/projects/' . $project['slug'];
			$project['proof_percentage_rounded'] = round($project['proof_percentage'], 0);
			$project['percentage_rounded'] = round($project['percentage'], 0);
		}

		$completedprojects = Project::getCompletedProjects();
		foreach ($completedprojects as &$project) {
			$project['link'] = $app_url . '/projects/' . $project['slug'];
		}	

		$options = array(
			'user' => array(
				'loggedin' => true,
				'admin' => $user->admin),
			'projects' => $projects
		);

		Template::render('projects', $options);
	}

	static public function adminProjectHandler($args) {
		$app_url = Settings::getProtected('app_url');
		$db = Settings::getProtected('db');

		if (array_key_exists(0, $args)) {
			$slug = $args[0];
			$mode = 'edit';
		} else {
			$slug = '';
			$mode = 'new';
		}

		$user = self::authenticate();

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

		Template::render('admin_project', $options);
	}

	static public function adminSaveProjectHandler($args) {
		$app_url = Settings::getProtected('app_url');
		$db = Settings::getProtected('db');

		$user = self::authenticate();

		$title = $_POST['project_title'];
		$author = $_POST['project_author'];
		$slug = $_POST['project_slug'];
		$language = $_POST['project_language'];
		$description = $_POST['project_desc'];
		$owner = $user->getUsername();
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

	static public function authenticate() {
		$auth = Settings::getProtected('auth');
		$auth->forceAuthentication();

		$username = $auth->getUsername();
		$user = new User($username);

		return $user;
	}
}

?>
