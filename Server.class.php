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
}
