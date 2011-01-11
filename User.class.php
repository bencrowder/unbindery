<?php

class User {
	private $db;

	private $username;
	private $name;
	private $email;

	public function User($db, $username = "") {
		$this->db = $db;

		if ($username != "") { 
			$this->load($username);
		}
	}

	public function __set($key, $val) {
		$this->$key = $val;
	}

	public function __get($key) {
		return $this->$key;
	}

	public function load($username) {
		$this->db->connect();
		$this->username = $username;

		$query = "SELECT name, email FROM users WHERE username = '" . mysql_real_escape_string($username) . "'";
		$result = mysql_query($query) or die ("Couldn't run: $query");

		if (mysql_numrows($result)) {
			$this->name = trim(mysql_result($result, 0, "name"));
			$this->email = trim(mysql_result($result, 0, "email"));
		}

		$this->db->close();
	}

	public function getAssignments() {
		$this->db->connect();

		$query = "SELECT item_id, items.title AS item_title, assignments.project_id, projects.title AS project_title, projects.slug AS project_slug, DATE_FORMAT(date_assigned, '%e %b %Y') AS date_assigned, DATE_FORMAT(deadline, '%e %b %Y') AS deadline FROM assignments JOIN items ON assignments.item_id = items.id JOIN projects ON assignments.project_id = projects.id WHERE username='" . mysql_real_escape_string($this->username) . "' AND date_completed IS NULL ORDER BY deadline ASC;";
		$result = mysql_query($query) or die ("Couldn't run: $query");

		$items = array();

		while ($row = mysql_fetch_assoc($result)) {
			array_push($items, array("item_id" => $row["item_id"], "item_title" => $row["item_title"], "project_id" => $row["project_id"], "project_title" => $row["project_title"], "project_slug" => $row["project_slug"], "date_assigned" => $row["date_assigned"], "deadline" => $row["deadline"]));
		}

		$this->db->close();

		return $items;
	}

	public function getProjects() {
		$this->db->connect();

		$query = "SELECT project_id, projects.title, projects.slug, projects.owner, role, (SELECT COUNT(*) FROM items WHERE items.project_id = membership.project_id AND status != 'available') AS completed, (SELECT COUNT(*) FROM items WHERE items.project_id = membership.project_id) AS total FROM membership JOIN projects ON membership.project_id = projects.id WHERE username = '" . mysql_real_escape_string($this->username) . "';";
		$result = mysql_query($query) or die ("Couldn't run: $query");

		$projects = array();

		while ($row = mysql_fetch_assoc($result)) {
			array_push($projects, array("project_id" => $row["project_id"], "title" => $row["title"], "slug" => $row["slug"], "owner" => $row["owner"], "role" => $row["role"], "completed" => $row["completed"], "total" => $row["total"]));
		}

		$this->db->close();

		return $projects;
	}

	public function isAssigned($item_id, $project_slug) {
		$this->db->connect();

		$query = "SELECT assignments.id FROM assignments JOIN projects ON assignments.project_id = projects.id WHERE username = '" . mysql_real_escape_string($this->username) . "' AND assignments.item_id = " . mysql_real_escape_string($item_id) . " AND projects.slug = '" . mysql_real_escape_string($project_slug) . "'";
		$result = mysql_query($query) or die ("Couldn't run: $query");

		if (mysql_numrows($result)) {
			$retval = true;
		} else {
			$retval = false;
		}

		$this->db->close();
		return $retval;
	}

	public function isMember($project_slug) {
		$this->db->connect();

		$query = "SELECT membership.id FROM membership JOIN projects ON membership.project_id = projects.id WHERE username = '" . mysql_real_escape_string($this->username) . "' AND projects.slug = '" . mysql_real_escape_string($project_slug) . "'";
		$result = mysql_query($query) or die ("Couldn't run: $query");

		if (mysql_numrows($result)) {
			$retval = true;
		} else {
			$retval = false;
		}

		$this->db->close();
		return $retval;
	}

	public function assignToProject($project_slug) {
		// make sure they're not already a member
		if (!$this->isMember($project_slug)) {
			$project = new Project($this->db, $project_slug);

			$this->db->connect();

			// insert into membership (default = proofer)
			$query = "INSERT INTO membership (project_id, username, role) VALUES (" . mysql_real_escape_string($project->project_id) . ", '" . mysql_real_escape_string($this->username) . "', 'proofer')";
			$result = mysql_query($query) or die ("Couldn't run: $query");

			// send email to user w/ project guidelines, link to unsubscribe, and note that first item will come soon (intro email, pull from project settings)

			$this->db->close();

			return true;
		} else {
			return false;
		}
	}

	public function assignItem($item_id, $project_slug) {
		$this->db->connect();

		// make sure the item exists
		$query = "SELECT items.id FROM items JOIN projects ON projects.id = items.project_id WHERE items.id = " . mysql_real_escape_string($item_id) . " AND projects.slug = '" . mysql_real_escape_string($project_slug) . "'";
		$result = mysql_query($query) or die ("Couldn't run: $query");

		if (!mysql_numrows($result)) {
			$this->db->close();
			return "nonexistent";
		}
		$this->db->close();

		// make sure they're not already assigned
		if (!$this->isAssigned($item_id, $project_slug)) {
			$project = new Project($this->db, $project_slug);
			// get $project->deadlinelength at some point
			$deadlinelength = 7;

			$this->db->connect();

			// insert into assignments
			$query = "INSERT INTO assignments (username, item_id, project_id, date_assigned, deadline) VALUES ('" . mysql_real_escape_string($this->username) . "', " . $item_id . ", " . mysql_real_escape_string($project->project_id) . ", NOW(), DATE_ADD(NOW(), INTERVAL $deadlinelength DAY))";
			$result = mysql_query($query) or die ("Couldn't run: $query");

			// send email to user w/ edit link, deadline

			$this->db->close();

			return "success";
		} else {
			return "already_assigned";
		}
	}

	public function getNextItem($project_slug = "") {
		// if no project specified, get the user's first current project
		if (!$project_slug || $project_slug == "") {
			$projects = $this->getProjects(); 
			$project_slug = $projects[0]["slug"];
		} else {
			if (!$this->isMember($project_slug)) {
				return "not a member";
			}
		}

		// make sure they've finished any existing items for that project (if not, go to next project)
		$this->db->connect();

		$query = "SELECT assignments.id FROM assignments JOIN projects ON assignments.project_id = projects.id WHERE username = '" . mysql_real_escape_string($this->username) . "' AND projects.slug = '" . mysql_real_escape_string($project_slug) . "' AND assignments.date_completed IS NULL";
		$result = mysql_query($query) or die ("Couldn't run: $query");

		if (mysql_numrows($result)) {
			$this->db->close();
			return "already_have_an_item_assigned";
		}

		// get next item from project where
		//		status = available
		//		user hasn't done that item
		//		number of assigned users is < project proof limit (2 reviews per item, etc.)
		$query = "SELECT items.id, items.project_id, ";
		$query .= "(SELECT COUNT(*) FROM assignments WHERE assignments.item_id = items.id) AS itemcount ";
		$query .= "FROM items JOIN projects ON projects.id = items.project_id ";
		$query .= "WHERE items.status = 'available' ";
		$query .= "AND projects.slug = '$project_slug' ";
		$query .= "AND items.id NOT IN ";
		$query .= "(SELECT item_id FROM assignments ";
		$query .= "WHERE username='{$this->username}' AND project_id = items.project_id) ";
		$query .= "HAVING itemcount < 2 "; //TODO: replace with $project->itemcount
		$query .= "LIMIT 1;";

		$result = mysql_query($query) or die ("Couldn't run: $query");

		if (mysql_numrows($result)) {
			$row = mysql_fetch_assoc($result);
			$item_id = $row["id"];
			$this->assignItem($item_id, $project_slug);
			$this->db->close();

			return array("statuscode" => "success", "item_id" => $item_id);
		} else {
			$this->db->close();
			return array("statuscode" => "not_found");
		}
	}
}