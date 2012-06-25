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
		echo "Settings page (" . $params['method'] . "): ";
		print_r($params['args']);
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

		// Get user's stats (score, # proofed, etc.)
		$user->getStats();

		// Load the user's proofing queue
		$proofQueue = new Queue("user.proof:$username");
		$proofItems = array();
		foreach ($proofQueue->getItems() as $item) {
			array_push($proofItems, array("title" => $item->title, "status" => $item->status, "project_slug" => $item->project_slug, "item_id" => $item->item_id));
		}

		// Load the user's reviewing queue
		$reviewQueue = new Queue("user.review:$username");
		$reviewItems = array();
		foreach ($reviewQueue->getItems() as $item) {
			array_push($reviewItems, array("title" => $item->title, "status" => $item->status, "project_slug" => $item->project_slug, "item_id" => $item->item_id));
		}

		// Get the user's history and the top proofers information
		$history = $user->getHistory();
		$topusers = User::getTopUsers();

		// Get the user's projects
		$projects = $user->getProjectSummaries();

		// Add extra info (edit link and slug) to each item
		$prooflist = array();
		foreach ($proofItems as &$item) {
			$item["editlink"] = $app_url . '/proof/' . $item["project_slug"] . '/' . $item["item_id"];
			if (!in_array($item['project_slug'], $prooflist)) {
				$prooflist[] = $item["project_slug"];
			}
		}	

		$reviewlist = array();
		foreach ($reviewItems as &$item) {
			$item["editlink"] = $app_url . '/review/' . $item["project_slug"] . '/' . $item["item_id"];
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
				$project["available_for_review"] = true;
			} else {
				$project["available_for_reviewing"] = false;
			}

			$project['percent_proofed'] = round($project['num_proofed'] / $project['num_items'] * 100, 0);
			$project['percent_reviewed'] = round($project['num_reviewed'] / $project['num_items'] * 100, 0);

			if ($project['type'] == 'public') {
				$project["link"] = $app_url . '/projects/' . $project["slug"];
			} else if ($project['type'] == 'private') {
				$project["link"] = $app_url . '/users/' . $project['owner'] . '/projects/' . $project["slug"];
			}

			$projectSummaries[$project['slug']] = $project;
		}

		/* Prepare user history */
		foreach ($history as &$event) {
			$event["editlink"] = "$app_url/edit/" . $event["project_slug"] . "/" . $event["item_id"];	
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

	static public function userSettingsHandler($args) {
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

		Template::render('settings', $options);
	}

	/* MOVE TO PUT */
	static public function userSaveSettingsHandler($args) {
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
}

?>
