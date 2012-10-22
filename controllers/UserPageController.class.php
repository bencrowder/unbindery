<?php

class UserPageController {

	// --------------------------------------------------
	// Users handler
	// URL: /users
	// Methods: GET = get list of users
	//          POST = create new user

	static public function users($params) {
		$format = $params['args'][0] != '' ? $params['args'][0] : 'html';

		switch ($params['method']) {
			// GET: Get list of users
			case 'GET':
				echo "<h2>Getting list of users</h2>";
				echo "(" . $format . ")";
				break;

			// POST: Create new user
			case 'POST':
				echo "<h2>Creating new user</h2>";
				echo "(" . $format . ")";
				break;
		}
	}


	// --------------------------------------------------
	// User page handler
	// URL: /users/[USERNAME]
	// Methods: GET = get user info
	//          PUT = save user info
	//          DELETE = delete user

	static public function userPage($params) {
		echo "User page (" . $params['method'] . "): ";
		print_r($params['args']);
	}


	// --------------------------------------------------
	// User settings handler
	// URL: /users/[USERNAME]/settings
	// Methods: GET = get user settings

	static public function userSettings($params) {
		$user = self::authenticate();

		$options = array(
			'user' => array(
				'loggedin' => true,
				'admin' => $user->admin,
				'name' => $user->name,
				'email' => $user->email),
		);

		Template::render('settings', $options);
	}


	// --------------------------------------------------
	// User dashboard handler
	// URL: /users/[USERNAME]/dashboard
	// Methods: GET = get user dashboard info

	static public function userDashboard($params) {
		$format = $params['args'][1] != '' ? $params['args'][1] : 'html';

		$app_url = Settings::getProtected('app_url');
		$auth = Settings::getProtected('auth');

		$auth->forceAuthentication();
		$username = $auth->getUsername();
		$user = new User($username);

		// Put it in the settings cache
		Settings::setProtected('username', $username);

		// Get user's stats (score, # proofed, etc.)
		$user->getStats();

		// Set up proofing and reviewing objects
		$proofing = array();
		$reviewing = array();

		// Load the user's proofing queue
		$proofQueue = new Queue("user.proof:$username");
		$proofing['items'] = array();
		foreach ($proofQueue->getItems() as $item) {
			array_push($proofing['items'], array('title' => $item->title, 'status' => $item->status, 'project_slug' => $item->project_slug, 'project_type' => $item->project_type, 'project_owner' => $item->project_owner, 'item_id' => $item->item_id));
		}

		// Load the user's reviewing queue
		$reviewQueue = new Queue("user.review:$username");
		$reviewing['items'] = array();
		foreach ($reviewQueue->getItems() as $item) {
			array_push($reviewing['items'], array('title' => $item->title, 'status' => $item->status, 'project_slug' => $item->project_slug, 'project_type' => $item->project_type, 'project_owner' => $item->project_owner, 'item_id' => $item->item_id));
		}

		// Add extra info (edit link and slug) to each item
		$prooflist = array();
		foreach ($proofing['items'] as &$item) {
			if ($item['project_type'] == 'system') {
				$item['editlink'] = $app_url . '/projects/' . $item['project_slug'] . '/items/' . $item['item_id'] . '/proof';
			} else if ($item['project_type'] == 'user') {
				$item['editlink'] = $app_url . '/users/' . $item['project_owner'] . '/projects/' . $item['project_slug'] . '/items/' . $item['item_id'] . '/proof';
			}

			if (!in_array($item['project_slug'], $prooflist)) {
				$prooflist[] = $item['project_slug'];
			}
		}	

		$reviewlist = array();
		foreach ($reviewing['items'] as &$item) {
			if ($item['project_type'] == 'system') {
				$item['editlink'] = $app_url . '/projects/' . $item['project_slug'] . '/items/' . $item['item_id'] . '/review';
			} else if ($item['project_type'] == 'user') {
				$item['editlink'] = $app_url . '/users/' . $item['project_owner'] . '/projects/' . $item['project_slug'] . '/items/' . $item['item_id'] . '/review';
			}

			if (!in_array($item['project_slug'], $reviewlist)) {
				$reviewlist[] = $item["project_slug"];
			}
		}	

		// Add link and percentages to each project
		$projects = $user->getProjectSummaries();
		$projectInfo = array();
		$proofing['projects'] = array();
		$reviewing['projects'] = array();

		foreach ($projects as &$project) {
			$roles = $user->getRolesForProject($project['slug']);

			// If the project is available for proofing or reviewing (with no items already claimed),
			// then add it to the appropriate list
			if (!in_array($project["slug"], $prooflist) && ($project["available_to_proof"] > 0) && (in_array('proofer', $roles))) {
				array_push($proofing['projects'], $project['slug']);
			}

			if (!in_array($project["slug"], $reviewlist) && ($project["available_to_review"] > 0) && (in_array('reviewer', $roles))) {
				array_push($reviewing['projects'], $project['slug']);
			}

			// Set up percentage bars
			if ($project['num_items'] == 0) {
				$project['percent_proofed'] = 0;
				$project['percent_reviewed'] = 0;
			} else {
				$project['percent_proofed'] = round($project['num_proofed'] / $project['num_items'] * 100, 0);
				$project['percent_reviewed'] = round($project['num_reviewed'] / $project['num_items'] * 100, 0);
			}

			// And the project link
			if ($project['type'] == 'public') {
				$project['link'] = $app_url . '/projects/' . $project['slug'];
			} else if ($project['type'] == 'private') {
				$project['link'] = $app_url . '/users/' . $project['owner'] . '/projects/' . $project['slug'];
			}

			$projectInfo[$project['slug']] = $project;
		}

		// Blank slate condition if there are no items and no projects
		$proofing['blankslate'] = (count($proofing['items']) == 0 && count($proofing['projects']) == 0) ? true : false;
		$reviewing['blankslate'] = (count($reviewing['items']) == 0 && count($reviewing['projects']) == 0) ? true : false;

		// Get the user's history and the top proofers information
		$history = $user->getHistory();
		$topusers = User::getTopUsers();

		// Prepare user history
		foreach ($history as &$event) {
			if ($event['project_type'] == 'system') {
				$event['editlink'] = "$app_url/projects/" . $event['project_slug'] . '/items/' . $event['item_id'] . '/proof';
			} else if ($project['type'] == 'user') {
				$event['editlink'] = "$app_url/users/" . $event['project_owner'] . '/projects/' . $event['project_slug'] . '/items/' . $event['item_id'] . '/proof';
			}

			$event['title'] = $event['item_title'];
		}

		$response = array(
			'user' => array(
				'loggedin' => true,
				'admin' => $user->admin,
				'score' => $user->score,
				'proofed' => $user->proofed,
				'proofed_past_week' => $user->proofed_past_week,
				),
			'projects' => $projectInfo,
			'proofing' => array(
				'items' => $proofing['items'],
				'projects' => $proofing['projects'],
				'blankslate' => $proofing['blankslate'],
				),
			'reviewing' => array(
				'items' => $reviewing['items'],
				'projects' => $reviewing['projects'],
				'blankslate' => $reviewing['blankslate'],
				),
			'history' => $history,
			'history_count' => count($history),
			'registered_methods' => array(
				'/users/' . $username,
				),	
			'topusers' => $topusers,
		);

		switch ($format) {
			case 'json':
				echo json_encode($response);
				break;
			case 'html':
				Template::render('dashboard', $response);
				break;
		}
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
