<?php

class Server {
	private $db;

	public function Server($db) {
		$this->db = $db;
	}

	public function getProjects() {
		$this->db->connect();

		$query = "SELECT projects.title, projects.author, projects.slug, (SELECT COUNT(*) FROM items WHERE items.project_id = projects.id AND status != 'available') AS completed, (SELECT COUNT(*) FROM items WHERE items.project_id = projects.id) AS total, (SELECT COUNT(*) FROM items WHERE items.project_id = projects.id AND status != 'available') / (SELECT COUNT(*) FROM items WHERE items.project_id = projects.id) * 100 AS percentage FROM projects WHERE projects.status = 'active' ORDER BY percentage DESC";
		$result = mysql_query($query) or die ("Couldn't run: $query");

		$projects = array();

		while ($row = mysql_fetch_assoc($result)) {
			array_push($projects, array("title" => $row["title"], "author" => $row["author"], "slug" => $row["slug"], "completed" => $row["completed"], "total" => $row["total"], "percentage" => $row["percentage"]));
		}

		$this->db->close();
		return $projects;
	}

	public function getTopUsers() {
		$this->db->connect();

		$query = "SELECT username, score FROM users ORDER BY score DESC LIMIT 10;";
		$result = mysql_query($query) or die ("Couldn't run: $query");

		$users = array();

		while ($row = mysql_fetch_assoc($result)) {
			array_push($users, array("username" => $row["username"], "score" => $row["score"]));
		}

		$this->db->close();
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
			$query .= "UPDATE users SET score = score - " . $user["points_lost"] . " WHERE username = '" . $user["username"] . "'; ";
		}

		if ($query != "") {
			$result = mysql_query($query) or die("Couldn't run: $query");
		}

		$this->db->close();
	}

	public function emailTardies() {
		$this->db->connect();

		$query = "SELECT users.email, item_id, items.title, projects.slug, DATE_FORMAT(deadline, '%e %b %Y') AS deadline FROM assignments JOIN users on assignments.username = users.username JOIN items ON item_id = items.id JOIN projects ON projects.id = assignments.project_id WHERE date_completed IS NULL AND DATEDIFF(deadline, NOW()) = 1;";
		$result = mysql_query($query) or die ("Couldn't run: $query");

		global $SITEROOT;

		while ($row = mysql_fetch_assoc($result)) {
			$message = "Just a reminder that you have an item due tomorrow:\n";
			$message .= "\n";
			$message .= "Edit link: " . $SITEROOT . "/edit/" . $row["slug"] . "/" . $row["item_id"] . "\n";
			$message .= "\n";
			$message .= "Thanks! We appreciate your help.";
			
			$subject = "[Unbindery] Assignment '" . $row["title"] . "' due tomorrow (" . $row["deadline"] . ")";

			Mail::sendMessage($row["email"], $subject, $message);
		}

		$this->db->close();
	}
}
