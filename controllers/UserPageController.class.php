<?php

class UserPageController {
	static public function dashboardHandler($args) {
		$app_url = Settings::getProtected('app_url');
		$db = Settings::getProtected('db');
		$auth = Settings::getProtected('auth');

		$auth->forceAuthentication();

		$username = $auth->getUsername();
		$user = new User($username);
		$user->getStats();

		$items = $user->getAssignments();
		$projects = $user->getProjects();
		$projectlist = array();
		$history = $user->getHistory();
		$topusers = User::getTopUsers();

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

		Template::render('dashboard', $options);
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

		Template::render('settings', $options);
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
}

?>
