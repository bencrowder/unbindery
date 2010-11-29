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

function getItem($db, $item_id, $project_slug, $username = "") {
	$db->connect();

	$query = "SELECT * FROM items JOIN projects ON items.project_id = projects.id WHERE items.id = " . mysql_real_escape_string($item_id) . " AND projects.slug = '" . mysql_real_escape_string($project_slug) . "'";
	$result = mysql_query($query) or die ("Couldn't run: $query");

	$item = new Item();

	if (mysql_numrows($result)) {
		$item->item_id = trim(mysql_result($result, 0, "id"));
		$item->project_id = trim(mysql_result($result, 0, "project_id"));
		$item->title = trim(mysql_result($result, 0, "title"));
		$item->itemtext = trim(mysql_result($result, 0, "itemtext"));
		$item->status = trim(mysql_result($result, 0, "status"));
		$item->type = trim(mysql_result($result, 0, "type"));
		$item->href = trim(mysql_result($result, 0, "href"));
		$item->width = trim(mysql_result($result, 0, "width"));
		$item->height = trim(mysql_result($result, 0, "height"));
		$item->length = trim(mysql_result($result, 0, "length"));
	}

	if ($username != '') {
		$query = "SELECT itemtext FROM texts WHERE item_id=" . mysql_real_escape_string($item_id) . " AND project_id=" . mysql_real_escape_string($item->project_id) . " AND user='" . mysql_real_escape_string($username) . "'";
		$result = mysql_query($query) or die ("Couldn't run: $query");

		if (mysql_numrows($result)) {
			$item->itemtext = trim(mysql_result($result, 0, "itemtext"));
		}
	}

	$db->close();

	return $item;
}

function getProject($db, $slug) {
	$db->connect();

	$query = "SELECT * FROM projects WHERE slug = '" . mysql_real_escape_string($slug) . "'";
	$result = mysql_query($query) or die ("Couldn't run: $query");

	$project = new Project();

	if (mysql_numrows($result)) {
		$project->project_id = trim(mysql_result($result, 0, "id"));
		$project->title = trim(mysql_result($result, 0, "title"));
		$project->slug = trim(mysql_result($result, 0, "slug"));
		$project->owner = trim(mysql_result($result, 0, "owner"));
		$project->status = trim(mysql_result($result, 0, "status"));
	}

	$db->close();

	return $project;
}

function saveItemText($db, $item_id, $project_slug, $username, $draft, $itemtext) {
	// get the item
	$item = getItem($db, $item_id, $project_slug);

	$db->connect();

	// check and see if we already have a draft
	$query = "SELECT itemtext FROM texts WHERE item_id=" . mysql_real_escape_string($item_id) . " AND project_id=" . mysql_real_escape_string($item->project_id) . " AND user='" . mysql_real_escape_string($username) . "'";
	$result = mysql_query($query) or die ("Couldn't run: $query");

	if (mysql_numrows($result)) {
		$existing_draft = true;
	} else {
		$existing_draft = false;
	}

	if ($draft) { 
		$status = "draft";
	} else {
		$status = "finished";
	}

	if ($existing_draft) {
		// update texts with $draft status
		$query = "UPDATE texts SET itemtext = '" . mysql_real_escape_string($itemtext) . "', date = NOW(), status = '" . mysql_real_escape_string($status) . "' WHERE item_id=" . mysql_real_escape_string($item_id) . " AND project_id=" . mysql_real_escape_string($item->project_id) . " AND user='" . mysql_real_escape_string($username) . "'";
		$result = mysql_query($query) or die ("Couldn't run: $query");
	} else {
		// insert into texts with $draft status
		$query = "INSERT INTO texts (project_id, item_id, user, date, itemtext, status) VALUES (" . mysql_real_escape_string($item->project_id) . ", " . mysql_real_escape_string($item_id) . ", '" . mysql_real_escape_string($username) . "', NOW(), '" . mysql_real_escape_string($itemtext) . "', '" . mysql_real_escape_string($status) . "')";
		$result = mysql_query($query) or die ("Couldn't run: $query");
	}

	if ($draft == false) {
		// we're finished with this item
		// update user score
		// change item status (if # revisions >= # project revisions, change status to closed)
	}

	return "success";
}


function getUserAssignments($db, $username) {
	$db->connect();

	$query = "SELECT item_id, items.title AS item_title, assignments.project_id, projects.title AS project_title, projects.slug AS project_slug, DATE_FORMAT(date_assigned, '%e %b %Y') AS date_assigned, DATE_FORMAT(deadline, '%e %b %Y') AS deadline FROM assignments JOIN items ON assignments.item_id = items.id JOIN projects ON assignments.project_id = projects.id WHERE username='" . mysql_real_escape_string($username) . "' AND date_completed IS NULL;";
	$result = mysql_query($query) or die ("Couldn't run: $query");

	$items = array();

	while ($row = mysql_fetch_assoc($result)) {
		array_push($items, array("item_id" => $row["item_id"], "item_title" => $row["item_title"], "project_id" => $row["project_id"], "project_title" => $row["project_title"], "project_slug" => $row["project_slug"], "date_assigned" => $row["date_assigned"], "deadline" => $row["deadline"]));
	}

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

	return $projects;
}

function checkUserAssignment($db, $username, $item_id, $project_slug) {
	$db->connect();

	$query = "SELECT assignments.id FROM assignments JOIN projects ON assignments.project_id = projects.id WHERE username = '" . mysql_real_escape_string($username) . "' AND assignments.item_id = " . mysql_real_escape_string($item_id) . " AND projects.slug = '" . mysql_real_escape_string($project_slug) . "'";
	$result = mysql_query($query) or die ("Couldn't run: $query");

	if (mysql_numrows($result)) {
		return true;
	} else {
		return false;
	}
}


///////////////////////////////////////////////////////////////////////
//
// Web service functions
//

function getItemWS($db) {
	$item_id = $_POST['item_id'];
	$project_slug = $_POST['project_slug'];
	$username = $_POST['username'];

	if (!$item_id || !$project_slug) { return ""; }

	$item = getItem($db, $item_id, $project_slug, $username);

	echo $item->getJSON();
}

function getProjectWS($db) {
	$slug = $_POST['slug'];

	if (!$slug) { return ""; }

	$project = getProject($db, $slug);

	echo $project->getJSON();
}

function saveItemTextWS($db) {
	$item_id = $_POST['item_id'];
	$project_slug = $_POST['project_slug'];
	$username = $_POST['username'];
	$itemtext = $_POST['itemtext'];

	if ($_POST['draft'] == "true") {
		$draft = true;
	} else {
		$draft = false;
	}

	if (!$item_id || !$project_slug || !$username) { return ""; }

	$status = saveItemText($db, $item_id, $project_slug, $username, $draft, $itemtext);

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
}

?>
