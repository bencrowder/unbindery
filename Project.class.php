<?php

class Project {
	private $db;

	private $project_id;

	private $title;
	private $slug;
	private $description;
	private $owner;
	private $status;

	private $guidelines;
	private $intro_email;
	private $deadline_days;
	private $num_proofs;

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
			$this->description = trim(mysql_result($result, 0, "description"));
			$this->owner = trim(mysql_result($result, 0, "owner"));
			$this->status = trim(mysql_result($result, 0, "status"));
			$this->guidelines = trim(mysql_result($result, 0, "guidelines"));
			$this->intro_email = trim(mysql_result($result, 0, "intro_email"));
			$this->deadline_days = trim(mysql_result($result, 0, "deadline_days"));
			$this->num_proofs = trim(mysql_result($result, 0, "num_proofs"));
		}

		$this->db->close();
	}

	public function save() {
		$this->db->connect();

		$query = "UPDATE projects WHERE id = " . $this->project_id . " ";
		$query .= "SET title = '" . mysql_real_escape_string($this->title) . "', ";
		$query .= "slug = '" . mysql_real_escape_string($this->slug) . "', ";
		$query .= "description = '" . mysql_real_escape_string($this->description) . "', ";
		$query .= "owner = '" . mysql_real_escape_string($this->owner) . "', ";
		$query .= "status = '" . mysql_real_escape_string($this->status) . "', ";
		$query .= "guidelines = '" . mysql_real_escape_string($this->guidelines) . "', ";
		$query .= "intro_email = '" . mysql_real_escape_string($this->intro_email) . "', ";
		$query .= "deadline_days = '" . mysql_real_escape_string($this->deadline_days) . "', ";
		$query .= "num_proofs = '" . mysql_real_escape_string($this->num_proofs) . "' ";

		$result = mysql_query($query) or die ("Couldn't run: $query");

		$this->db->close();
	}

	public function create($title, $slug, $description, $owner, $guidelines, $intro_email, $deadline_days, $num_proofs) {
		$this->title = $title;
		$this->slug = $slug;
		$this->description = $description;
		$this->owner = $owner;
		$this->status = "active";
		$this->guidelines = $guidelines;
		$this->intro_email = $intro_email;
		$this->deadline_days = $deadline_days;
		$this->num_proofs = $num_proofs;

		if ($title != "" && $slug != "") {
			$this->db->connect();

			$query = "INSERT INTO projects ";
			$query .= "(title, slug, description, owner, status, guidelines, intro_email, deadline_days, num_proofs) ";
			$query .= "VALUES (";
			$query .= "'" . mysql_real_escape_string($this->title) . "', ";
			$query .= "'" . mysql_real_escape_string($this->slug) . "', ";
			$query .= "'" . mysql_real_escape_string($this->description) . "', ";
			$query .= "'" . mysql_real_escape_string($this->owner) . "', ";
			$query .= "'" . mysql_real_escape_string($this->status) . "', ";
			$query .= "'" . mysql_real_escape_string($this->guidelines) . "', ";
			$query .= "'" . mysql_real_escape_string($this->intro_email) . "', ";
			$query .= "'" . mysql_real_escape_string($this->deadline_days) . "', ";
			$query .= "'" . mysql_real_escape_string($this->num_proofs) . "') ";

			$result = mysql_query($query) or die ("Couldn't run: $query");

			$this->db->close();

			return "success";
		} else {
			return "missing title/slug";
		}
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

	public function getItems() {
		$this->db->connect();

		$query = "SELECT title, status, type, href, (SELECT COUNT(*) FROM assignments WHERE assignments.item_id = items.id) AS assignments, (SELECT COUNT(*) FROM assignments WHERE assignments.item_id = items.id AND date_completed IS NOT NULL) AS completed FROM items WHERE project_id = " . $this->project_id . " ORDER BY items.id ASC";
		$result = mysql_query($query) or die ("Couldn't run: $query");

		$items = array();

		while ($row = mysql_fetch_assoc($result)) {
			array_push($items, array("title" => $row["title"], "status" => $row["status"], "type" => $row["type"], "href" => $row["href"], "assignments" => $row["assignments"], "completed" => $row["completed"]));
		}

		$this->db->close();

		return $items;
	}

	public function getJSON() {
		return json_encode(array("project_id" => $this->project_id, "title" => $this->title, "slug" => $this->slug, "description" => $this->description, "owner" => $this->owner, "status" => $this->status));
	}

}
