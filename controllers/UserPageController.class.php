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
		$app_url = Settings::getProtected('app_url');
		$db = Settings::getProtected('db');
		$auth = Settings::getProtected('auth');

		$auth->forceAuthentication();
		$username = $auth->getUsername();
		$user = new User($username);

		// Put it in the settings cache
		Settings::setProtected('username', $username);

		// Get user's stats (score, # proofed, etc.)
		$user->getStats();

		// Load the user's proofing queue
		$proofQueue = new Queue("user.proof:$username");
		$proofItems = array();
		foreach ($proofQueue->getItems() as $item) {
			array_push($proofItems, array('title' => $item->title, 'status' => $item->status, 'project_slug' => $item->project_slug, 'project_type' => $item->project_type, 'project_owner' => $item->project_owner, 'item_id' => $item->item_id));
		}

		// Load the user's reviewing queue
		$reviewQueue = new Queue("user.review:$username");
		$reviewItems = array();
		foreach ($reviewQueue->getItems() as $item) {
			array_push($reviewItems, array('title' => $item->title, 'status' => $item->status, 'project_slug' => $item->project_slug, 'project_type' => $item->project_type, 'project_owner' => $item->project_owner, 'item_id' => $item->item_id));
		}

		// Get the user's history and the top proofers information
		$history = $user->getHistory();
		$topusers = User::getTopUsers();

		// Get the user's projects
		$projects = $user->getProjectSummaries();

		// Add extra info (edit link and slug) to each item
		$prooflist = array();
		foreach ($proofItems as &$item) {
			if ($item['project_type'] == 'public') {
				$item['editlink'] = $app_url . '/projects/' . $item['project_slug'] . '/items/' . $item['item_id'] . '/proof';
			} else if ($item['project_type'] == 'private') {
				$item['editlink'] = $app_url . '/users/' . $item['project_owner'] . '/projects/' . $item['project_slug'] . '/items/' . $item['item_id'] . '/proof';
			}

			if (!in_array($item['project_slug'], $prooflist)) {
				$prooflist[] = $item['project_slug'];
			}
		}	

		$reviewlist = array();
		foreach ($reviewItems as &$item) {
			if ($item['project_type'] == 'public') {
				$item['editlink'] = $app_url . '/projects/' . $item['project_slug'] . '/items/' . $item['item_id'] . '/review';
			} else if ($item['project_type'] == 'private') {
				$item['editlink'] = $app_url . '/users/' . $item['project_owner'] . '/projects/' . $item['project_slug'] . '/items/' . $item['item_id'] . '/review';
			}

			if (!in_array($item['project_slug'], $reviewlist)) {
				$reviewlist[] = $item["project_slug"];
			}
		}	

		// Add link and percentages to each project
		$projectSummaries = array();

		foreach ($projects as &$project) {
			if (!in_array($project["slug"], $prooflist) && ($project["available_to_proof"] > 0)) {
				$project["available_for_proofing"] = true;
			} else {
				$project["available_for_proofing"] = false;
			}

			if (!in_array($project["slug"], $reviewlist) && ($project["available_to_review"] > 0)) {
				$project["available_for_reviewing"] = true;
			} else {
				$project["available_for_reviewing"] = false;
			}

			if ($project['num_items'] == 0) {
				$project['percent_proofed'] = 0;
				$project['percent_reviewed'] = 0;
			} else {
				$project['percent_proofed'] = round($project['num_proofed'] / $project['num_items'] * 100, 0);
				$project['percent_reviewed'] = round($project['num_reviewed'] / $project['num_items'] * 100, 0);
			}

			if ($project['type'] == 'public') {
				$project["link"] = $app_url . '/projects/' . $project["slug"];
			} else if ($project['type'] == 'private') {
				$project["link"] = $app_url . '/users/' . $project['owner'] . '/projects/' . $project["slug"];
			}

			$projectSummaries[$project['slug']] = $project;
		}

		/* Prepare user history */
		foreach ($history as &$event) {
			$event["editlink"] = "$app_url/FIXME/" . $event["project_slug"] . "/" . $event["item_id"];	
			$event["title"] = $event["item_title"];
		}

		/* See if this is a new user */
		if (count($proofItems) == 0 && count($projectSummaries) == 0) {
			$blankslate = true;
		} else {
			$blankslate = false;
		}

		$options = array(
			'user' => array(
				'loggedin' => true,
				'admin' => $user->admin,
				'score' => $user->score,
				'proofed' => $user->proofed,
				'proofed_past_week' => $user->proofed_past_week,
				),
			'proofItems' => $proofItems,
			'reviewItems' => $reviewItems,
			'projects' => $projectSummaries,
			'history' => $history,
			'registered_methods' => array(
				'/users/' . $username,
				),	
			'topusers' => $topusers,
			'blankslate' => $blankslate,
			'history_count' => count($history)
		);

		Template::render('dashboard', $options);
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
