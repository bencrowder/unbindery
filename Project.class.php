<?php

class Project {
	private $db;

	private $project_id;

	private $title;
	private $slug;
	private $description;
	private $owner;
	private $status;

	public function Project($db, $slug = "") {
		$this->db = $db;

		if ($slug != "") {
			$this->load($slug);
		}
	}

	public function __set($key, $val) {
		$this->$key = $val;
	}

	public function __get($key) {
		return $this->$key;
	}

	public function load($slug) {
		$this->db->connect();

		$query = "SELECT * FROM projects WHERE slug = '" . mysql_real_escape_string($slug) . "'";
		$result = mysql_query($query) or die ("Couldn't run: $query");

		if (mysql_numrows($result)) {
			$this->project_id = trim(mysql_result($result, 0, "id"));
			$this->title = trim(mysql_result($result, 0, "title"));
			$this->slug = trim(mysql_result($result, 0, "slug"));
			$this->owner = trim(mysql_result($result, 0, "owner"));
			$this->status = trim(mysql_result($result, 0, "status"));
		}

		$this->db->close();
	}

	public function getStatus() {
		$completed = 0;
		$total = 0;

		// returns array with number of items and how many are completed
		$this->db->connect();

		$query = "SELECT COUNT(*) AS total FROM items WHERE project_id = " . mysql_real_escape_string($this->project_id);
		$result = mysql_query($query) or die ("Couldn't run: $query");

		if (mysql_numrows($result)) {
			$row = mysql_fetch_assoc($result);
			$total = $row["total"];
		}

		$query = "SELECT COUNT(*) AS completed FROM items WHERE project_id = " . mysql_real_escape_string($this->project_id) . " AND status = 'completed'";
		$result = mysql_query($query) or die ("Couldn't run: $query");

		if (mysql_numrows($result)) {
			$row = mysql_fetch_assoc($result);
			$completed = $row["completed"];
		}

		$this->db->close();

		return array("completed" => $completed, "total" => $total);
	}

	public function getJSON() {
		return json_encode(array("project_id" => $this->project_id, "title" => $this->title, "slug" => $this->slug, "description" => $this->description, "owner" => $this->owner, "status" => $this->status));
	}

}
