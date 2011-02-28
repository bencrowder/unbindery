<?php

class Project {
	private $db;

	private $project_id;

	private $title;
	private $author;
	private $slug;
	private $language;
	private $description;
	private $owner;
	private $status;

	private $guidelines;
	private $deadline_days;
	private $num_proofs;
	private $thumbnails;

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

		$query = "SELECT *, ";
		$query .= "DATE_FORMAT(date_started, '%e %b %Y') AS datestarted, ";
		$query .= "DATE_FORMAT(date_completed, '%e %b %Y') AS datecompleted, ";
		$query .= "DATEDIFF(date_completed, date_started) AS days_spent ";
		$query .= "FROM projects WHERE slug = '" . mysql_real_escape_string($slug) . "'";
		$result = mysql_query($query) or die ("Couldn't run: $query");

		if (mysql_numrows($result)) {
			$this->project_id = trim(mysql_result($result, 0, "id"));
			$this->title = stripslashes(trim(mysql_result($result, 0, "title")));
			$this->author = stripslashes(trim(mysql_result($result, 0, "author")));
			$this->slug = stripslashes(trim(mysql_result($result, 0, "slug")));
			$this->language = stripslashes(trim(mysql_result($result, 0, "language")));
			$this->description = stripslashes(trim(mysql_result($result, 0, "description")));
			$this->owner = trim(mysql_result($result, 0, "owner"));
			$this->status = trim(mysql_result($result, 0, "status"));
			$this->guidelines = stripslashes(trim(mysql_result($result, 0, "guidelines")));
			$this->deadline_days = trim(mysql_result($result, 0, "deadline_days"));
			$this->num_proofs = trim(mysql_result($result, 0, "num_proofs"));
			$this->thumbnails = trim(mysql_result($result, 0, "thumbnails"));
			$this->date_started = trim(mysql_result($result, 0, "datestarted"));
			$this->date_completed = trim(mysql_result($result, 0, "datecompleted"));
			$this->days_spent = trim(mysql_result($result, 0, "days_spent"));
		}

		$this->db->close();
	}

	public function save() {
		$this->db->connect();

		$query = "UPDATE projects ";
		$query .= "SET title = '" . mysql_real_escape_string($this->title) . "', ";
		$query .= "author = '" . mysql_real_escape_string($this->author) . "', ";
		$query .= "slug = '" . mysql_real_escape_string($this->slug) . "', ";
		$query .= "language = '" . mysql_real_escape_string($this->language) . "', ";
		$query .= "description = '" . mysql_real_escape_string($this->description) . "', ";
		$query .= "owner = '" . mysql_real_escape_string($this->owner) . "', ";
		$query .= "status = '" . mysql_real_escape_string($this->status) . "', ";
		$query .= "guidelines = '" . mysql_real_escape_string($this->guidelines) . "', ";
		$query .= "deadline_days = '" . mysql_real_escape_string($this->deadline_days) . "', ";
		$query .= "num_proofs = '" . mysql_real_escape_string($this->num_proofs) . "', ";
		if ($this->status == "completed") { $query .= "date_completed = NOW(), "; }
		if ($this->status == "posted") { $query .= "date_posted = NOW(), "; }
		$query .= "thumbnails = '" . mysql_real_escape_string($this->thumbnails) . "' ";
		$query .= "WHERE id = " . $this->project_id . " ";

		$result = mysql_query($query) or die ("Couldn't run: $query");

		$this->db->close();
	}

	public function create($title, $author, $slug, $language, $description, $owner, $guidelines, $deadline_days, $num_proofs, $thumbnails) {
		$this->title = $title;
		$this->author = $author;
		$this->slug = $slug;
		$this->language = $language;
		$this->description = $description;
		$this->owner = $owner;
		$this->status = "pending";
		$this->guidelines = $guidelines;
		$this->deadline_days = $deadline_days;
		$this->num_proofs = $num_proofs;
		$this->thumbnails = $thumbnails;

		if ($title != "" && $slug != "") {
			$this->db->connect();

			$query = "INSERT INTO projects ";
			$query .= "(title, author, slug, language, description, owner, status, guidelines, deadline_days, num_proofs, thumbnails, date_started) ";
			$query .= "VALUES (";
			$query .= "'" . mysql_real_escape_string($this->title) . "', ";
			$query .= "'" . mysql_real_escape_string($this->author) . "', ";
			$query .= "'" . mysql_real_escape_string($this->slug) . "', ";
			$query .= "'" . mysql_real_escape_string($this->language) . "', ";
			$query .= "'" . mysql_real_escape_string($this->description) . "', ";
			$query .= "'" . mysql_real_escape_string($this->owner) . "', ";
			$query .= "'" . mysql_real_escape_string($this->status) . "', ";
			$query .= "'" . mysql_real_escape_string($this->guidelines) . "', ";
			$query .= "'" . mysql_real_escape_string($this->deadline_days) . "', ";
			$query .= "'" . mysql_real_escape_string($this->num_proofs) . "', ";
			$query .= "'" . mysql_real_escape_string($this->thumbnails) . "', ";
			$query .= "NOW()) ";

			$result = mysql_query($query) or die ("Couldn't run: $query");

			$this->db->close();

			return "success";
		} else {
			return "missing title/slug";
		}
	}

	public function loadStatus() {
		$this->db->connect();

		$query = "SELECT (SELECT COUNT(*) FROM items WHERE items.project_id = projects.id AND status != 'available') AS completed, (SELECT COUNT(*) FROM items WHERE items.project_id = projects.id) AS total, (SELECT COUNT(*) FROM items WHERE items.project_id = projects.id AND status != 'available') / (SELECT COUNT(*) FROM items WHERE items.project_id = projects.id) * 100 AS percentage FROM projects WHERE slug = '" . mysql_real_escape_string($this->slug) . "'";
		$result = mysql_query($query) or die ("Couldn't run: $query");

		if (mysql_numrows($result)) {
			$this->completed = trim(mysql_result($result, 0, "completed"));
			$this->total = stripslashes(trim(mysql_result($result, 0, "total")));
			$this->percentage = stripslashes(trim(mysql_result($result, 0, "percentage")));
		}

		$this->db->close();
	}

	public function addPages($pages) {
		$this->db->connect();

		$page_ids = array();

		foreach ($pages as $page) {
			$query = "INSERT INTO items (project_id, title, itemtext, status, type, href) VALUES ({$this->project_id}, '" . mysql_real_escape_string($page) . "', NULL, 'available', 'image', '{$this->slug}/" . mysql_real_escape_string($page) . ".jpg'); ";

			$result = mysql_query($query) or die ("Couldn't run: $query");

			// get the insert ID and add it to the array
			$page_id = mysql_insert_id();
			array_push($page_ids, $page_id);
		}

		$this->db->close();

		return array("statuscode" => "success", "page_ids" => $page_ids);
	}

	public function saveItems($items) {
		$this->db->connect();

		foreach ($items as $item) {
			$item_id = $item[0];
			$item_text = $item[1];

			$query = "UPDATE items SET itemtext = '$item_text' WHERE id = $item_id; ";
			$result = mysql_query($query) or die ("Couldn't run: $query");
		}

		$this->db->close();

		return array("statuscode" => "success");
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
			array_push($items, array("title" => stripslashes($row["title"]), "status" => $row["status"], "type" => $row["type"], "href" => $row["href"], "assignments" => $row["assignments"], "completed" => $row["completed"]));
		}

		$this->db->close();

		return $items;
	}

	public function getProoferStats() {
		$this->db->connect();

		$query = "SELECT username, ";
		$query .= "COUNT(username) AS pages, ";
		$query .= "COUNT(username) / (SELECT COUNT(*) FROM items WHERE items.project_id = assignments.project_id) * 100 AS percentage ";
		$query .= "FROM assignments ";
		$query .= "WHERE project_id={$this->project_id} ";
		$query .= "GROUP BY username ORDER BY pages DESC;";
		$result = mysql_query($query) or die ("Couldn't run: $query");

		$proofers = array();

		while ($row = mysql_fetch_assoc($result)) {
			array_push($proofers, array("username" => $row["username"], "pages" => $row["pages"], "percentage" => $row["percentage"]));
		}

		$this->db->close();

		return $proofers;
	}

	public function getJSON() {
		return json_encode(array("project_id" => $this->project_id, "title" => $this->title, "author" => $this->author, "slug" => $this->slug, "language" => $this->language, "description" => $this->description, "owner" => $this->owner, "status" => $this->status));
	}

}
