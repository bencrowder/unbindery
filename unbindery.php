<?php

// Unbindery
// Ben Crowder <ben.crowder@gmail.com>

include_once('include/config.php');
include_once('Database.class.php');
include_once('Project.class.php');
include_once('Item.class.php');


///////////////////////////////////////////////////////////////////////
//
// Library functions
//

function getUserAssignments($db, $username) {
	$db->connect();

	$query = "SELECT item_id, items.title AS item_title, assignments.project_id, projects.title AS project_title, projects.slug AS project_slug, DATE_FORMAT(date_assigned, '%e %b %Y') AS date_assigned, DATE_FORMAT(deadline, '%e %b %Y') AS deadline FROM assignments JOIN items ON assignments.item_id = items.id JOIN projects ON assignments.project_id = projects.id WHERE username='" . mysql_real_escape_string($username) . "' AND date_completed IS NULL ORDER BY deadline ASC;";
	$result = mysql_query($query) or die ("Couldn't run: $query");

	$items = array();

	while ($row = mysql_fetch_assoc($result)) {
		array_push($items, array("item_id" => $row["item_id"], "item_title" => $row["item_title"], "project_id" => $row["project_id"], "project_title" => $row["project_title"], "project_slug" => $row["project_slug"], "date_assigned" => $row["date_assigned"], "deadline" => $row["deadline"]));
	}

	$db->close();

	return $items;
}

function getUserProjects($db, $username) {
	$db->connect();

	$query = "SELECT project_id, projects.title, projects.slug, projects.owner, role FROM membership JOIN projects ON membership.project_id = projects.id WHERE username = '" . mysql_real_escape_string($username) . "';";
	$result = mysql_query($query) or die ("Couldn't run: $query");

	$projects = array();

	while ($row = mysql_fetch_assoc($result)) {
		array_push($projects, array("project_id" => $row["project_id"], "title" => $row["title"], "slug" => $row["slug"], "owner" => $row["owner"], "role" => $row["role"]));
	}

	$db->close();

	return $projects;
}

function checkUserAssignment($db, $username, $item_id, $project_slug) {
	$db->connect();

	$query = "SELECT assignments.id FROM assignments JOIN projects ON assignments.project_id = projects.id WHERE username = '" . mysql_real_escape_string($username) . "' AND assignments.item_id = " . mysql_real_escape_string($item_id) . " AND projects.slug = '" . mysql_real_escape_string($project_slug) . "'";
	$result = mysql_query($query) or die ("Couldn't run: $query");

	if (mysql_numrows($result)) {
		$db->close();
		return true;
	} else {
		$db->close();
		return false;
	}
}

function checkUserMembership($db, $username, $project_slug) {
	$db->connect();

	$query = "SELECT membership.id FROM membership JOIN projects ON membership.project_id = projects.id WHERE username = '" . mysql_real_escape_string($username) . "' AND projects.slug = '" . mysql_real_escape_string($project_slug) . "'";
	$result = mysql_query($query) or die ("Couldn't run: $query");

	if (mysql_numrows($result)) {
		$db->close();
		return true;
	} else {
		$db->close();
		return false;
	}
}

function assignUserToProject($db, $username, $project_slug) {
	// make sure they're not already a member
	if (!checkUserMembership($db, $username, $project_slug)) {
		$project = getProject($db, $project_slug);

		$db->connect();

		// insert into membership (default = proofer)
		$query = "INSERT INTO membership (project_id, username, role) VALUES (" . mysql_real_escape_string($project->project_id) . ", '" . mysql_real_escape_string($username) . "', 'proofer')";
		$result = mysql_query($query) or die ("Couldn't run: $query");

		// send email to user w/ project guidelines, link to unsubscribe, and note that first item will come soon (intro email, pull from project settings)

		$db->close();

		return true;
	} else {
		return false;
	}
}

function assignItemToUser($db, $username, $item_id, $project_slug) {
	// make sure the item exists
	$db->connect();

	$query = "SELECT items.id FROM items JOIN projects ON projects.id = items.project_id WHERE items.id = " . mysql_real_escape_string($item_id) . " AND projects.slug = '" . mysql_real_escape_string($project_slug) . "'";
	$result = mysql_query($query) or die ("Couldn't run: $query");

	if (!mysql_numrows($result)) {
		$db->close();
		return "nonexistent";
	}
	$db->close();

	// make sure they're not already assigned
	if (!checkUserAssignment($db, $username, $item_id, $project_slug)) {
		$project = getProject($db, $project_slug);
		// get $project->deadlinelength at some point
		$deadlinelength = 7;

		$db->connect();

		// insert into assignments
		$query = "INSERT INTO assignments (username, item_id, project_id, date_assigned, deadline) VALUES ('" . mysql_real_escape_string($username) . "', " . $item_id . ", " . mysql_real_escape_string($project->project_id) . ", NOW(), DATE_ADD(NOW(), INTERVAL $deadlinelength DAY))";
		$result = mysql_query($query) or die ("Couldn't run: $query");

		// send email to user w/ edit link, deadline

		$db->close();

		return "success";
	} else {
		return "already_assigned";
	}
}

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

///////////////////////////////////////////////////////////////////////
//
// Web service functions
//

function getItemWS($db) {
	$item_id = $_POST['item_id'];
	$project_slug = $_POST['project_slug'];
	$username = $_POST['username'];

	// make sure we have at least the item ID and the project slug (username is optional)
	if (!$item_id || !$project_slug) { return ""; }

	$item = new Item($db);
	$item->load($item_id, $project_slug, $username);

	echo $item->getJSON();
}

function getProjectWS($db) {
	$slug = $_POST['slug'];

	// make sure we have the slug
	if (!$slug) { return ""; }

	$project = new Project($db);
	$project->load($slug);

	echo $project->getJSON();
}

function saveItemTextWS($db) {
	$item_id = $_POST['item_id'];
	$project_slug = $_POST['project_slug'];
	$username = $_POST['username'];
	$itemtext = $_POST['itemtext'];

	echo "here";
	// convert to boolean
	if ($_POST['draft'] == "true") {
		$draft = true;
	} else {
		$draft = false;
	}

	// make sure we have the required variables
	if (!$item_id || !$project_slug || !$username) { return ""; }

	echo "new Item";
	$item = new Item($db);
	echo "load";
	$item->load($item_id, $project_slug, $username);
	echo "saveText";
	$status = $item->saveText($username, $draft, $itemtext);

	echo json_encode(array("statuscode" => $status));
}

function getUserAssignmentsWS($db) {
	$username = $_POST['username'];

	$items = getUserAssignments($db, $username);

	echo json_encode($items);
}

function getUserProjectsWS($db) {
	$username = $_POST['username'];

	$projects = getUserProjects($db, $username);

	echo json_encode($projects);
}

function checkUserAssignmentWS($db) {
	$username = $_POST['username'];
	$item_id = $_POST['item_id'];
	$project_slug = $_POST['project_slug'];

	$result = checkUserAssignment($db, $username, $item_id, $project_slug);

	echo json_encode($result);
}

function checkUserMembershipWS($db) {
	$username = $_POST['username'];
	$project_slug = $_POST['project_slug'];

	$result = checkUserMembership($db, $username, $project_slug);

	echo json_encode($result);
}

function assignUserToProjectWS($db) {
	$username = $_POST['username'];
	$project_slug = $_POST['project_slug'];

	$result = assignUserToProject($db, $username, $project_slug);

	echo json_encode($result);
}

function assignItemToUserWS($db) {
	$username = $_POST['username'];
	$item_id = $_POST['item_id'];
	$project_slug = $_POST['project_slug'];

	$result = assignItemToUser($db, $username, $item_id, $project_slug);

	echo json_encode($result);
}

///////////////////////////////////////////////////////////////////////
//
// Main
//

$method = $_GET['method'];

switch ($method) {
	case 'get_item': getItemWS($db); break;
	case 'get_project': getProjectWS($db); break;
	case 'save_item_text': saveItemTextWS($db); break;
	case 'get_user_assignments': getUserAssignmentsWS($db); break;
	case 'get_user_projects': getUserProjectsWS($db); break;
	case 'check_assignment': checkUserAssignmentWS($db); break;
	case 'check_membership': checkUserMembershipWS($db); break;
	case 'assign_user_to_project': assignUserToProjectWS($db); break;
	case 'assign_item_to_user': assignItemToUserWS($db); break;
}

?>
