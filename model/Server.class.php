<?php

class Server {
	private $db;

	public function Server($db) {
		$this->db = $db;
	}

	public function getProjects() {
		$projects = $this->db->query("SELECT projects.title AS title, projects.author AS author, projects.slug AS slug, projects.num_proofs AS num_proofs, (SELECT COUNT(*) FROM items WHERE items.project_id = projects.id AND status != 'available' AND status != 'assigned') AS completed, (SELECT COUNT(*) FROM items WHERE items.project_id = projects.id) AS total, (SELECT COUNT(*) FROM items WHERE items.project_id = projects.id AND status != 'available' AND status != 'assigned') / (SELECT COUNT(*) FROM items WHERE items.project_id = projects.id) * 100 AS percentage, (SELECT COUNT(*) FROM assignments WHERE assignments.project_id = projects.id AND assignments.date_completed IS NOT NULL) / (projects.num_proofs * (SELECT COUNT(*) FROM items where items.project_id = projects.id)) * 100 AS proof_percentage, (SELECT count(items.id) FROM items LEFT JOIN assignments ON assignments.item_id = items.id WHERE items.status = 'available' AND assignments.date_assigned IS NULL AND items.project_id = projects.id) AS available_pages FROM projects WHERE projects.status = 'active' ORDER BY percentage DESC");

		foreach ($projects as &$project) {
			$project["title"] = stripslashes($project["title"]);
			$project["author"] = stripslashes($project["author"]);
		}

		return $projects;
	}

	public function getCompletedProjects() {
		$query = "SELECT projects.title, projects.author, projects.slug, ";
		$query .= "(SELECT date_assigned FROM assignments WHERE project_id=projects.id ORDER BY date_assigned limit 1) AS date_started, ";
		$query .= "(SELECT date_completed FROM assignments WHERE project_id=projects.id ORDER BY date_completed DESC limit 1) AS date_comp, ";
		$query .= "DATE_FORMAT((SELECT date_completed FROM assignments WHERE project_id=projects.id ORDER BY date_completed DESC limit 1), '%e %b %Y') AS date_completed ";
		$query .= "FROM projects ";
		$query .= "WHERE projects.status = 'completed' OR projects.status = 'posted' ";
		$query .= "ORDER BY date_comp DESC";

		$projects = $this->db->query($query);
		return $projects;
	}

	public function getTopUsers() {
		$users = $this->db->query("SELECT username, score FROM users WHERE score > 0 ORDER BY score DESC LIMIT 10;");
		return $users;
	}

	public function decrementTardies() {
		$this->db->connect();

		$query = "SELECT username, count(username) AS points_lost FROM assignments WHERE date_completed IS NULL AND deadline < NOW() GROUP BY username";
		$result = mysql_query($query) or die ("Couldn't run: $query");

		$users = array();

		while ($row = mysql_fetch_assoc($result)) {
			array_push($users, array("username" => $row["username"], "points_lost" => $row["points_lost"]));
		}

		$query = "";
		foreach ($users as $user) {
			$points = intval($user["points_lost"]) * 10;
			$query = "UPDATE users SET score = score - " . $points . " WHERE username = '" . $user["username"] . "'; ";
			if ($query != "") {
				$result = mysql_query($query) or die("Couldn't run: $query");
			}
		}

		$this->db->close();
	}

	public function emailTardies() {
		$this->db->connect();

		$query = "SELECT users.email, item_id, items.title, projects.slug, DATE_FORMAT(deadline, '%e %b %Y') AS deadline FROM assignments JOIN users on assignments.username = users.username JOIN items ON item_id = items.id JOIN projects ON projects.id = assignments.project_id WHERE assignments.date_completed IS NULL AND DATEDIFF(deadline, NOW()) = 1;";
		$result = mysql_query($query) or die ("Couldn't run: $query");

		global $SITEROOT;
		global $EMAILSUBJECT;

		while ($row = mysql_fetch_assoc($result)) {
			$message = "Just a reminder that you have an item due tomorrow. If you finish it before the deadline, you won't lose any points.\n";
			$message .= "\n";
			$message .= "Edit link: " . $SITEROOT . "/edit/" . $row["slug"] . "/" . $row["item_id"] . "\n";
			$message .= "\n";
			$message .= "Thanks! We appreciate your help.";
			
			$subject = "$EMAILSUBJECT Assignment '" . $row["title"] . "' due tomorrow (" . $row["deadline"] . ")";

			Mail::sendMessage($row["email"], $subject, $message);

			echo "\n\nSent email to " . $row["email"] . " about " . $row["slug"] . "/" . $row["item_id"];
		}

		$this->db->close();
	}

	public function getCurrentAssignments() {
		$assigments = $this->db->query("SELECT username, item_id, project_id, date_assigned, deadline FROM assignments WHERE date_completed IS NULL ORDER BY project_id, date_assigned DESC");

		return $assignments;
	}
}