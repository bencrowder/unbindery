<?php

class Project {
	private $db;

	private $project_id;

	private $title;
	private $type;
	private $slug;
	private $language;
	private $description;
	private $owner;
	private $status;
	private $guidelines;
	private $thumbnails;
	private $workflow;
	private $whitelist;

	private $url;
	private $admin_url;

	public function Project($slug = "") {
		$this->db = Settings::getProtected('db');

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
		$project = $this->db->loadProject($slug);

		if (isset($project)) {
			$this->project_id = stripslashes(trim($project['id']));
			$this->title = stripslashes(trim($project['title']));
			$this->type = stripslashes(trim($project['type']));
			$this->slug = stripslashes(trim($project['slug']));
			$this->language = stripslashes(trim($project['language']));
			$this->description = stripslashes(trim($project['description']));
			$this->owner = trim($project['owner']);
			$this->status = trim($project['status']);
			$this->guidelines = stripslashes(trim($project['guidelines']));
			$this->thumbnails = trim($project['thumbnails']);
			$this->workflow = trim($project['workflow']);
			$this->whitelist = trim($project['whitelist']);
			$this->date_started = trim($project['datestarted']);
			$this->date_completed = trim($project['datecompleted']);
			$this->days_spent = trim($project['days_spent']);

			// Put the whitelist into an array
			if ($this->whitelist != '') {
			   	$this->whitelist = explode(",", $this->whitelist);
			} else {
				$this->whitelist = array();
			}
		}
	}

	public function save() {
		$status = false;

		$whitelist = implode(",", $this->whitelist);

		if ($this->project_id) {
			$status = $this->db->saveProject($this->project_id, $this->title, $this->type, $this->slug, $this->description, $this->owner, $this->status, $this->workflow, $whitelist, $this->guidelines, $this->language, $this->thumbnails, $this->fields);
		} else {
			$status = $this->db->addProject($this->title, $this->type, $this->slug, $this->description, $this->owner, $this->status, $this->workflow, $whitelist, $this->guidelines, $this->language, $this->thumbnails, $this->fields);
		}

		return $status;
	}

	public function create($title, $type, $slug, $language, $description, $owner, $guidelines, $deadline_days, $num_proofs, $thumbnails) {
		$this->title = $title;
		$this->type = $type;
		$this->slug = $slug;
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
			$this->db->addProject($this->title, $this->type, $this->slug, $this->language, $this->description, $this->owner, $this->status, $this->guidelines, $this->deadline_days, $this->num_proofs, $this->thumbnails);

			return "success";
		} else {
			return "missing title/slug";
		}
	}

	public function loadStatus() {
		$project = $this->db->loadProjectStatus($this->slug);

		if (isset($project)) {
			$this->completed = trim($project['completed']);
			$this->total = stripslashes(trim($project['total']));
			$this->percentage = stripslashes(trim($project['percentage']));
			$this->proof_percentage = stripslashes(trim($project['proof_percentage']));
			$this->proofed = stripslashes(trim($project['proofed']));
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

			$this->db->addPage($this->project_id, $pagename, "{$this->slug}/$filename");

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

			$this->db->saveItemText($item_id, $item_text);
		}

		return array("statuscode" => "success");
	}

	public function getStatus() {
		$total = $this->db->getNumProjectItems($this->project_id);
		$completed = $this->db->getNumProjectItems($this->project_id);

		return array("completed" => $completed, "total" => $total);
	}

	public function getItems() {
		return $this->db->getItems($this->project_id);
	}

	public function getProoferStats() {
		return $this->db->getProoferStats($this->project_id);
	}

	public function getItemsAndAssignments() {
		$items = array();

		/* Get all the items for this project */
		$itemlist = $this->db->getBasicItems($this->project_id);

		foreach ($itemlist as $row) {
			$items[$row["id"]] = array("title" => $row["title"], "status" => $row["status"], "assignments" => array());
		}

		/* Now get assignments */
		$assignmentlist = $this->db->getBasicAssignments($this->project_id);

		foreach ($assignmentlist as $assignment) {
			array_push($items[$assignment["item_id"]]["assignments"], array("username" => $assignment["username"], "date_completed" => $assignment["date_completed"]));
		}

		return $items;
	}

	public function getJSON() {
		return json_encode(array("project_id" => $this->project_id, "title" => $this->title, "type" => $this->type, "slug" => $this->slug, "language" => $this->language, "description" => $this->description, "owner" => $this->owner, "status" => $this->status));
	}

	static public function getProjects() {
		$db = Settings::getProtected('db');
		$projects = $db->getProjects();

		foreach ($projects as &$project) {
			$project["title"] = stripslashes($project["title"]);
		}

		return $projects;
	}

	static public function getCompletedProjects() {
		$db = Settings::getProtected('db');
		return $db->getCompletedProjects();
	}

	static public function allowedToJoin($username) {
		return in_array($username, $this->whitelist);
	}
}
