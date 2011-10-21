<?php

class User {
	private $db;

	private $username;
	private $name;
	private $email;
	private $status;
	private $admin;

	public function User($username = "") {
		$this->db = Settings::getProtected('db');

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
		$db = Settings::getProtected('db');
		$user = $db->loadUser($username);

		if (isset($user) && array_key_exists('name', $user) && array_key_exists('email', $user) && array_key_exists('status', $user) && array_key_exists('admin', $user)) {
			$this->name = trim($user["name"]);
			$this->email = trim($user["email"]);
			$this->status = trim($user["status"]);
			$this->admin = $user["admin"];
		}
		$this->username = $username;
	}

	public function addToDatabase($hash) {
		$this->db->connect();

		$query = "INSERT INTO users (username, password, email, status, admin, score, hash, signup_date) VALUES ";
		$query .= "('" . mysql_real_escape_string($this->username) . "', ";
		$query .= "'" . md5(mysql_real_escape_string($this->password)) . "', ";
		$query .= "'" . mysql_real_escape_string($this->email) . "', ";
		$query .= "'pending', ";
		$query .= "0, ";
		$query .= "0, ";
		$query .= "'$hash', ";
		$query .= "NOW())";
		$result = mysql_query($query) or die ("Couldn't run: $query");

		$this->db->close();
	}

	public function getAssignments() {
		$items = $this->db->getUserAssignments($this->username);
		return $items;
	}

	public function getProjects() {
		$projects = $this->db->getUserProjects($this->username);
		return $projects;
	}

	public function isAssigned($item_id, $project_slug) {
		$result = $this->db->query("SELECT assignments.id FROM assignments JOIN projects ON assignments.project_id = projects.id WHERE username = ? AND assignments.item_id = ? AND projects.slug = ?", array($this->username, $item_id, $project_slug));

		if (count($result) > 0) {
			$retval = true;
		} else {
			$retval = false;
		}

		return $retval;
	}

	public function isMember($project_slug) {
		$result = $this->db->query("SELECT membership.id FROM membership JOIN projects ON membership.project_id = projects.id WHERE username = ' AND projects.slug = ?", array($this->username, $project_slug));
 
		if (count($result) > 0) {
			$retval = true;
		} else {
			$retval = false;
		}

		return $retval;
	}

	public function getRoleForProject($project_slug) {
		$results = $this->db->query("SELECT role FROM membership JOIN projects ON membership.project_id = projects.id WHERE username = ? AND projects.slug = ?", array($this->username, $project_slug));
		$result = $results[0];
		$role = $result['role'];

		return $role;
	}

	public function assignToProject($project_slug) {
		// make sure they're not already a member
		if (!$this->isMember($project_slug)) {
			$project = new Project($this->db, $project_slug);

			$this->db->connect();

			// insert into membership (default = proofer)
			$query = "INSERT INTO membership (project_id, username, role) VALUES (" . mysql_real_escape_string($project->project_id) . ", '" . mysql_real_escape_string($this->username) . "', 'proofer')";
			$result = mysql_query($query) or die ("Couldn't run: $query");

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
			$deadlinelength = $project->deadline_days;

			$this->db->connect();

			// insert into assignments
			$query = "INSERT INTO assignments (username, item_id, project_id, date_assigned, deadline) VALUES ('" . mysql_real_escape_string($this->username) . "', " . $item_id . ", " . mysql_real_escape_string($project->project_id) . ", NOW(), DATE_ADD(NOW(), INTERVAL $deadlinelength DAY))";
			$result = mysql_query($query) or die ("Couldn't run: $query");

			// get the updated number of assignments for this page
			$query = "SELECT COUNT(*) AS itemcount FROM assignments WHERE assignments.item_id = $item_id";
			$result = mysql_query($query) or die ("Couldn't run: $query");

			while ($row = mysql_fetch_assoc($result)) {
				$itemcount = $row["itemcount"];
			}

			// if we're at the number of proofs, set the item to "assigned" (unavailable)
			if ($itemcount == $project->num_proofs) {
				$query = "UPDATE items SET status = 'assigned' WHERE id = $item_id";
				$result = mysql_query($query) or die ("Couldn't run: $query");
			}

			/*
			// don't do this anymore
			// send email to user w/ edit link, deadline
			global $SITEROOT;
			$editlink = "$SITEROOT/edit/$project_slug/$item_id";
			$deadline = strftime("%e %b %Y", strtotime("+1 week"));

			$message = "New Unbindery assignment for " . $project->title . "\n";
			$message .= "\n";
			$message .= "Edit link: $editlink\n";
			$message .= "Deadline: $deadline\n";
			$message .= "\n";

			global $EMAILSUBJECT;
			Mail::sendMessage($this->email, "$EMAILSUBJECT New assignment (due $deadline)", $message);
			*/

			$this->db->close();

			return "success";
		} else {
			return "already_assigned";
		}
	}

	public function getNewPage($project_slug = "") {
		// if no project specified, get the user's first current project
		if (!$project_slug || $project_slug == "") {
			$projects = $this->getProjects(); 
			$project_slug = $projects[0]["slug"];
		} else {
			if (!$this->isMember($project_slug)) {
				return array("statuscode" => "not_a_member");
			}
		}

		$project = new Project($this->db, $project_slug);

		$this->db->connect();

		// first check to see if they're in training mode. if so, only return an item if they haven't done any yet.
		if ($this->status == "training") {
			$query = "SELECT assignments.id FROM assignments WHERE username = '" . mysql_real_escape_string($this->username) . "' AND date_completed IS NOT NULL";
			$result = mysql_query($query) or die ("Couldn't run: $query");

			if (mysql_numrows($result)) {
				$this->db->close();
				return array("statuscode" => "waiting_for_clearance");
			}
		}

		// make sure they've finished any existing items for that project (if not, go to next project)
		$query = "SELECT assignments.id FROM assignments JOIN projects ON assignments.project_id = projects.id WHERE username = '" . mysql_real_escape_string($this->username) . "' AND projects.slug = '" . mysql_real_escape_string($project_slug) . "' AND assignments.date_completed IS NULL";
		$result = mysql_query($query) or die ("Couldn't run: $query");

		if (mysql_numrows($result)) {
			$this->db->close();
			return array("statuscode" => "have_item_already");
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
		$query .= "HAVING itemcount < {$project->num_proofs} ";
		$query .= "ORDER BY items.id ASC ";
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

	public function getStats() {
		$users = $this->db->query("SELECT score, (SELECT COUNT(*) FROM assignments WHERE username = ? AND date_completed IS NOT NULL) AS proofed, (SELECT COUNT(*) FROM assignments WHERE username = ? AND date_completed IS NOT NULL AND DATE_COMPLETED > DATE_SUB(NOW(), INTERVAL 7 DAY)) AS proofed_past_week FROM users WHERE username = ?", array($this->username, $this->username, $this->username));
		$user = $users[0];

		if (isset($user)) {
			$this->score = $user["score"];
			$this->proofed = $user["proofed"];
			$this->proofed_past_week = $user["proofed_past_week"];
		}
	}

	public function getHistory() {
		$query = "SELECT items.title AS item_title, projects.title AS project_title, assignments.date_completed AS date_comp, ";
		$query .= "DATE_FORMAT(assignments.date_completed, '%e %b %Y') AS date_completed, ";
		$query .= "items.id as item_id, projects.slug as project_slug ";
		$query .= "FROM assignments JOIN items ON item_id = items.id ";
		$query .= "JOIN projects ON assignments.project_id = projects.id ";
		$query .= "WHERE username = ? ";
		$query .= "AND assignments.date_completed IS NOT null ";
		$query .= "ORDER BY assignments.date_completed DESC LIMIT 5;";

		$history = $this->db->query($query, array($this->username));

		return $history;
	}

	public function validateHash($hash) {
		$this->db->connect();

		$query = "SELECT username FROM users WHERE hash = '" . mysql_real_escape_string($hash) . "'";
		$result = mysql_query($query) or die ("Couldn't run: $query");

		if (mysql_numrows($result)) {
			$username = trim(mysql_result($result, 0, "username"));
		} else {
			return false;
		}

		$query = "UPDATE users SET status = 'training' WHERE username = '$username'";
		$result = mysql_query($query) or die("Couldn't run: $query");

		$this->db->close();

		return true;
	}

	public function updateLogin() {
		$this->db->connect();

		$query = "UPDATE users SET last_login = NOW() WHERE username = '" . mysql_real_escape_string($this->username) . "'";
		$result = mysql_query($query) or die ("Couldn't run: $query");

		$this->db->close();
	}
}
