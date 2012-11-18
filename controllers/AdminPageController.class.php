<?php

class AdminPageController {
	static public function adminHandler($params) {
		$format = self::getFormat($params['args'], 0, 2);
		$app_url = Settings::getProtected('app_url');
		$db = Settings::getProtected('db');

		$user = User::getAuthenticatedUser();

		// Get the current user's role and make sure they're at least creator or admin
		$roleManager = new Role();
		$roleManager->forceClearance(array('role' => 'user:creator', 'user' => $user));

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
	
		$response = array(
			'page_title' => 'Admin Dashboard',
			'user' => $user->getResponse(),
			'latest_work' => $latestWork,
			'newest_members' => $newestMembers,
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


	// --------------------------------------------------
	// Helper function to parse project page type

	static public function getProjectType($args) {
		if ($args[0] == 'users') {
			return 'user';
		} else {
			return 'system';
		}
	}


	// --------------------------------------------------
	// Helper function to parse return format type

	static public function getFormat($args, $systemIndex, $userIndex) {
		$projectType = self::getProjectType($args);
		$formatIndex = ($projectType == 'system') ? $systemIndex : $userIndex;
		return $args[$formatIndex] != '' ? $args[$formatIndex] : 'html';
	}
}

?>
