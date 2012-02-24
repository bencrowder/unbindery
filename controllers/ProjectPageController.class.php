<?php

class ProjectPageController {
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

		Template::render('project', $options);
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

		Template::render('admin_project', $options);
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
}

?>
