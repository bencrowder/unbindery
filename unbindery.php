<?php

// Unbindery
// Ben Crowder <ben.crowder@gmail.com>

include_once('include/config.php');
include_once('Database.class.php');
include_once('Project.class.php');
include_once('Item.class.php');
include_once('User.class.php');


///////////////////////////////////////////////////////////////////////
//
// Library functions
//

function getNextItem($db, $username, $project_slug) {
	// if project = "", get one of the user's projects
	// make sure they've finished any existing items for that project (if not, go to next project)
	// get next item from project where
	//		status = available
	//		user hasn't done that item
	//		number of assigned users is < project proof limit (2 reviews per item, etc.)
	// if there's nothing, return a message saying so
	// else assign item to user
}


/* Dispatcher ********************************************************/

$method = $_GET['method'];

switch ($method) {
	case 'get_item': 
		$item_id = $_POST['item_id'];
		$project_slug = $_POST['project_slug'];
		$username = $_POST['username'];

		// username is optional
		if ($item_id && $project_slug) {
			$item = new Item($db, $item_id, $project_slug, $username);

			echo $item->getJSON();
		}
		break;	

	case 'get_project':
		$slug = $_POST['slug'];

		if ($slug) {
			$project = new Project($db, $slug);

			echo $project->getJSON();
		}
		break;

	case 'save_item_text':
		$item_id = $_POST['item_id'];
		$project_slug = $_POST['project_slug'];
		$username = $_POST['username'];
		$itemtext = $_POST['itemtext'];

		// convert to boolean
		$draft = ($_POST['draft'] == "true") ? true : false;

		if ($item_id && $project_slug && $username) {
			$item = new Item($db);
			$item->load($item_id, $project_slug, $username);
			$status = $item->saveText($username, $draft, $itemtext);

			echo json_encode(array("statuscode" => $status));
		}
		break;

	case 'get_user_assignments':
		$username = $_POST['username'];

		if ($username) {
			$user = new User($db, $username);
			$items = $user->getAssignments();

			echo json_encode($items);
		}
		break;

	case 'get_user_projects':
		$username = $_POST['username'];

		if ($username) {
			$user = new User($db, $username);
			$projects = $user->getProjects();

			echo json_encode($projects);
		}
		break;

	case 'check_assignment':
		$username = $_POST['username'];
		$item_id = $_POST['item_id'];
		$project_slug = $_POST['project_slug'];

		if ($username && $item_id && $project_slug) {
			$user = new User($db, $username);
			$result = $user->isAssigned($item_id, $project_slug);

			echo json_encode($result);
		}
		break;

	case 'check_membership':
		$username = $_POST['username'];
		$project_slug = $_POST['project_slug'];

		if ($username && $project_slug) {
			$user = new User($db, $username);
			$result = $user->isMember($project_slug);

			echo json_encode($result);
		}
		break;

	case 'assign_user_to_project':
		$username = $_POST['username'];
		$project_slug = $_POST['project_slug'];

		if ($username && $project_slug) {
			$user = new User($db, $username);
			$result = $user->assignToProject($project_slug);

			echo json_encode($result);
		}
		break;

	case 'assign_item_to_user':
		$username = $_POST['username'];
		$item_id = $_POST['item_id'];
		$project_slug = $_POST['project_slug'];

		if ($username && $item_id && $project_slug) {
			$user = new User($db, $username);
			$result = $user->assignItem($item_id, $project_slug);

			echo json_encode($result);
		}
		break;
}

?>
