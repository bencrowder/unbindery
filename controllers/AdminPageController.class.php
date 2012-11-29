<?php

class AdminPageController {
	static public function admin($params) {
		$format = Utils::getFormat($params['args'], 0, 2);
		$app_url = Settings::getProtected('app_url');
		$db = Settings::getProtected('db');

		$user = User::getAuthenticatedUser();

		// Make sure the user is at least creator or admin
		RoleController::forceClearance(
			array('system.creator', 'system.admin'),
			$user
		);

		// Get latest work for the user's projects
		$latestWorkList = $db->getAdminProjectsLatestWork($user->username, 5);
		$latestWork = array();
		foreach ($latestWorkList as $work) {
			$qn = $work['queue_name'];
			$type = substr($qn, strpos($qn, '.') + 1, strpos($qn, ':') - strpos($qn, '.') - 1);
			$username = substr($qn, strpos($qn, ':') + 1);

			$item = new Item($work['item_id'], $work['project_slug']);
			$project = new Project($work['project_slug']);

			if ($item->project_type == 'system') { 
				$transcriptURL = "$app_url/projects/" . $item->project_slug . "/items/" . $item->item_id . "/$type/$username";
				$editURL = "$app_url/projects/" . $item->project_slug . "/items/" . $item->item_id . "/edit";
			} else {
				$transcriptURL = "$app_url/" . $item->project_owner . "/projects/" . $item->project_slug . "/items/" . $item->item_id . "/$type/$username";
				$editURL = "$app_url/" . $item->project_owner . "/projects/" . $item->project_slug . "/items/" . $item->item_id . "/edit";
			}

			array_push($latestWork, array(
				'item' => $item->getResponse(),
				'project' => $project->getResponse(),
				'type' => $type,
				'username' => $username,
				'date_completed' => $work['date_completed'],
				'transcript_url' => $transcriptURL,
				'edit_url' => $editURL,
				)
			);
		}

		$newestMembers = $db->getNewestProjectMembers($user->username, 5);

		// Only get list of users if they're a site admin
		$users = array();
		if ($user->role == 'admin') {
			$usernameList = $db->getUsers();

			foreach ($usernameList as $username) {
				$tempUser = new User($username['username']);
				$tempUserArray = $tempUser->getResponse();

				// Get list of projects they're working on
				$projects = $db->getUserProjectsWithStats($username['username']);
				$tempUserArray['projects'] = $projects;

				array_push($users, $tempUserArray);
			}
		}
	
		$response = array(
			'page_title' => 'Admin Dashboard',
			'user' => $user->getResponse(),
			'latest_work' => $latestWork,
			'newest_members' => $newestMembers,
			'users' => $users,
		);

		switch ($format) {
			case 'json':
				echo json_encode($response);
				break;
			case 'html':
				Template::render('admin_dashboard', $response);
				break;
		}
	}
}

?>
