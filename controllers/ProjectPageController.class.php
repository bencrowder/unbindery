<?php

class ProjectPageController {

	// --------------------------------------------------
	// Projects handler
	// URL: /projects OR /users/[USER]/projects
	// Methods: GET = get list of projects
	//          POST = create new project

	static public function projects($params) {
		$format = self::getFormat($params['args'], 0, 2);
		$pageType = self::getProjectPageType($params['args']);

		$auth = Settings::getProtected('auth');
		$auth->forceAuthentication();
		$username = $auth->getUsername();
		$user = new User($username);

		// Get user's stats (score, # proofed, etc.)
		$user->getStats();

		switch ($params['method']) {
			// GET: Get list of projects
			case 'GET':
				$userProjects = Project::getActiveProjectsForUser($username);
				$userProjectSlugList = array();
				foreach ($userProjects as $project) {
					array_push($userProjectSlugList, $project['slug']);
				}

				// Get availalbe projects
				$owner = '';
				if ($pageType == 'system') {
					$projectsList = Project::getPublicActiveProjects();
				} else {
					// Get specified user's available projects
					$owner = $params['args'][1];
					$projectsList = Project::getPublicActiveProjects($owner);
				}

				// If it's in the userProjects list, don't include it in the available list
				$availableProjects = array();
				foreach ($projectsList as $project) {
					if (!in_array($project['slug'], $userProjectSlugList)) {
						array_push($availableProjects, $project);
					}
				}

				switch ($format) {
					case 'json':
						echo json_encode(array('projects' => $projects, 'userprojects' => $userProjects));
						break;
					case 'html':
						$options = array(
							'user' => array(
								'loggedin' => true,
								'admin' => $user->admin,
								'score' => $user->score,
								'proofed' => $user->proofed,
								'proofed_past_week' => $user->proofed_past_week,
								),
							'projects' => $availableProjects,
							'userprojects' => $userProjects,
							'type' => $pageType,
							'owner' => $owner,
						);

						Template::render('projects', $options);
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
				$status = ($user->removeFromProject($projectSlug)) ? 'success' : 'error';

				echo json_encode(array('status' => $status));
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
			Utils::redirectToDashboard('', 'Error loading project.');
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
	

	// --------------------------------------------------
	// Helper function to parse project page type

	static public function getProjectPageType($args) {
		if ($args[0] == 'users') {
			return 'user';
		} else {
			return 'system';
		}
	}


	// --------------------------------------------------
	// Helper function to parse return format type

	static public function getFormat($args, $systemIndex, $userIndex) {
		$projectPage = self::getProjectPageType($args);
		$formatIndex = ($projectPage == 'system') ? $systemIndex : $userIndex;
		return $args[$formatIndex] != '' ? $args[$formatIndex] : 'html';
	}


	// --------------------------------------------------
	// Helper function to check authentication

	static public function authenticate() {
		$auth = Settings::getProtected('auth');
		$auth->forceAuthentication();

		$username = $auth->getUsername();
		$user = new User($username);

		return $user;
	}
}

?>
