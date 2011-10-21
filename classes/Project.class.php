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
		$query = "SELECT *, ";
		$query .= "DATE_FORMAT(date_started, '%e %b %Y') AS datestarted, ";
		$query .= "DATE_FORMAT(date_completed, '%e %b %Y') AS datecompleted, ";
		$query .= "DATE_FORMAT(date_posted, '%e %b %Y') AS dateposted, ";
		$query .= "DATEDIFF(date_completed, date_started) AS days_spent ";
		$query .= "FROM projects WHERE slug = ?;";
		$results = $this->db->query($query, array($slug));
		$result = $results[0];

		if (isset($result)) {
			$this->project_id = stripslashes(trim($result['id']));
			$this->title = stripslashes(trim($result['title']));
			$this->author = stripslashes(trim($result['author']));
			$this->slug = stripslashes(trim($result['slug']));
			$this->language = stripslashes(trim($result['language']));
			$this->description = stripslashes(trim($result['description']));
			$this->owner = trim($result['owner']);
			$this->status = trim($result['status']);
			$this->guidelines = stripslashes(trim($result['guidelines']));
			$this->deadline_days = trim($result['deadline_days']);
			$this->num_proofs = trim($result['num_proofs']);
			$this->thumbnails = trim($result['thumbnails']);
			$this->date_started = trim($result['datestarted']);
			$this->date_completed = trim($result['datecompleted']);
			$this->date_posted = trim($result['dateposted']);
			$this->days_spent = trim($result['days_spent']);
		}
	}

	public function save() {
		$sql = "UPDATE projects ";
		$sql .= "SET title = ?, ";
		$sql .= "author = ?, ";
		$sql .= "slug = ?, ";
		$sql .= "language = ?, ";
		$sql .= "description = ?, ";
		$sql .= "owner = ?, ";
		$sql .= "status = ?, ";
		$sql .= "guidelines = ?, ";
		$sql .= "deadline_days = ?, ";
		$sql .= "num_proofs = ?, ";
		$sql .= "thumbnails = ? ";
		$sql .= "WHERE id = ?;";

		$this->db->execute($sql, array($this->title, $this->author, $this->slug, $this->language, $this->description, $this->owner, $this->status, $this->guidelines, $this->deadline_days, $this->num_proofs, $this->thumbnails, $this->project_id));
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
			$sql = "INSERT INTO projects ";
			$sql .= "(title, author, slug, language, description, owner, status, guidelines, deadline_days, num_proofs, thumbnails, date_started) ";
			$sql .= "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW());";

			$this->db->execute($sql, array($this->title, $this->author, $this->slug, $this->language, $this->description, $this->owner, $this->status, $this->guidelines, $this->deadline_days, $this->num_proofs, $this->thumbnails));

			return "success";
		} else {
			return "missing title/slug";
		}
	}

	public function loadStatus() {
		$query = "SELECT (SELECT COUNT(*) FROM items WHERE items.project_id = projects.id AND status != 'available') AS completed, (SELECT COUNT(*) FROM items WHERE items.project_id = projects.id) AS total, (SELECT COUNT(*) FROM items WHERE items.project_id = projects.id AND status != 'available') / (SELECT COUNT(*) FROM items WHERE items.project_id = projects.id) * 100 AS percentage, (SELECT COUNT(*) FROM assignments WHERE assignments.project_id = projects.id AND assignments.date_completed IS NOT NULL) / (projects.num_proofs * (SELECT COUNT(*) FROM items where items.project_id = projects.id)) * 100 AS proof_percentage, (SELECT COUNT(*) FROM assignments WHERE assignments.project_id = projects.id AND assignments.date_completed IS NOT NULL) AS proofed FROM projects WHERE slug = ?;";
		$results = $this->db->query($query, array($this->slug));
		$result = $results[0];

		if (isset($result)) {
			$this->completed = trim($result['completed']);
			$this->total = stripslashes(trim($result['total']));
			$this->percentage = stripslashes(trim($result['percentage']));
			$this->proof_percentage = stripslashes(trim($result['proof_percentage']));
			$this->proofed = stripslashes(trim($result['proofed']));
		}
	}

	public function addPages($pages) {
		$page_ids = array();

		// split the string up by the delimiter
		$pages = explode('|', $pages, -1);

		foreach ($pages as $filename) {
			// get basename of the page
			$path_parts = pathinfo($filename);
			$pagename = $path_parts['filename'];

			$sql = "INSERT INTO items (project_id, title, itemtext, status, type, href) VALUES (?, ?, NULL, 'available', 'image', ?); ";
			$this->db->execute($sql, array($this->project_id, $pagename, "{$this->slug}/$filename"));

			// get the insert ID and add it to the array
			$page_id = $this->db->last_insert_id();
			array_push($page_ids, $page_id);
		}

		return array("statuscode" => "success", "page_ids" => $page_ids);
	}

	public function saveItems($items) {
		foreach ($items as $item) {
			$item_id = $item[0];
			$item_text = $item[1];

			$sql = "UPDATE items SET itemtext = ? WHERE id = ?; ";
			$this->db->execute($sql, array($item_text, $item_id));
		}

		return array("statuscode" => "success");
	}

	public function getStatus() {
		$completed = 0;
		$total = 0;

		// returns array with number of items and how many are completed
		$query = "SELECT COUNT(*) AS total FROM items WHERE project_id = ?;";
		$results = $this->db->query($query, array($this->project_id));
		$result = $results[0];

		if (isset($result)) {
			$total = $result['total'];
		}

		$query = "SELECT COUNT(*) AS completed FROM items WHERE project_id = ? AND status = 'completed';";
		$results = $this->db->query($query, array($this->project_id));
		$result = $results[0];

		if (isset($result)) {
			$completed = $result['completed'];
		}

		return array("completed" => $completed, "total" => $total);
	}

	public function getItems() {
		$items = $this->db->query("SELECT title, status, type, href, (SELECT COUNT(*) FROM assignments WHERE assignments.item_id = items.id) AS assignments, (SELECT COUNT(*) FROM assignments WHERE assignments.item_id = items.id AND date_completed IS NOT NULL) AS completed FROM items WHERE project_id = ? ORDER BY items.id ASC;", array($this->project_id));

		return $items;
	}

	public function getProoferStats() {
		$query = "SELECT username, ";
		$query .= "COUNT(username) AS pages, ";
		$query .= "COUNT(username) / ((SELECT COUNT(*) FROM items WHERE items.project_id = assignments.project_id) * projects.num_proofs) * 100 AS percentage ";
		$query .= "FROM assignments ";
		$query .= "JOIN projects ON assignments.project_id = projects.id ";
		$query .= "WHERE project_id = ? ";
		$query .= "GROUP BY username ORDER BY pages DESC;";

		$proofers = $this->db->query($query, array($this->project_id));

		return $proofers;
	}

	public function getItemsAndAssignments() {
		$items = array();

		/* Get all the items for this project */
		$itemlist = $this->db->query("SELECT id, title, status FROM items WHERE project_id = ? ORDER BY items.id ASC;", array($this->project_id));

		foreach ($itemlist as $row) {
			$items[$row["id"]] = array("title" => $row["title"], "status" => $row["status"], "assignments" => array());
		}

		/* Now get assignments */
		$assignmentlist = $this->db->query("SELECT item_id, username, date_completed FROM assignments WHERE project_id = ? ORDER BY item_id ASC;", array($this->project_id));

		foreach ($assignmentlist as $assignment) {
			array_push($items[$assignment["item_id"]]["assignments"], array("username" => $assignment["username"], "date_completed" => $assignment["date_completed"]));
		}

		return $items;
	}

	public function getJSON() {
		return json_encode(array("project_id" => $this->project_id, "title" => $this->title, "author" => $this->author, "slug" => $this->slug, "language" => $this->language, "description" => $this->description, "owner" => $this->owner, "status" => $this->status));
	}
}
