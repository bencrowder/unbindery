<?php

class Project {
	private $db;

	private $project_id;

	private $title;
	private $type;
	private $public;
	private $slug;
	private $language;
	private $description;
	private $owner;
	private $status;
	private $guidelines;
	private $thumbnails;
	private $workflow;
	private $whitelist;
	private $fields;

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
			$this->public = trim($project['public']);
			$this->slug = stripslashes(trim($project['slug']));
			$this->language = stripslashes(trim($project['language']));
			$this->description = stripslashes(trim($project['description']));
			$this->owner = trim($project['owner']);
			$this->status = trim($project['status']);
			$this->guidelines = stripslashes(trim($project['guidelines']));
			$this->thumbnails = trim($project['thumbnails']);
			$this->workflow = trim($project['workflow']);
			$this->whitelist = trim($project['whitelist']);
			$this->fields = trim($project['fields']);
			$this->dateStarted = trim($project['datestarted']);
			$this->dateCompleted = trim($project['datecompleted']);
			$this->daysSpent = trim($project['days_spent']);
			$this->numItems = trim($project['num_items']);
			$this->itemsCompleted = trim($project['items_completed']);
			$this->numProofers = trim($project['num_proofers']);
			$this->numReviewers = trim($project['num_reviewers']);

			// Put the whitelist into an array
			if (trim($this->whitelist, "[]") != '') {
			   	$this->whitelist = explode("][", trim($this->whitelist, "[]"));
			} else {
				$this->whitelist = array();
			}
		}
	}

	public function save() {
		$status = false;

		// Turn the whitelist from a newline-delimited list to something like
		// this: [username][username]
		if ($this->whitelist && $this->whitelist != '') {
			$whitelist = "[" . join("][", explode("\n", $this->whitelist)) . "]";
		} else {
			$whitelist = '';
		}

		if ($this->project_id) {
			$status = $this->db->saveProject($this->project_id, $this->title, $this->type, $this->public, $this->slug, $this->description, $this->owner, $this->status, $this->workflow, $whitelist, $this->guidelines, $this->language, $this->thumbnails, $this->fields);
		} else {
			$status = $this->db->addProject($this->title, $this->type, $this->public, $this->slug, $this->description, $this->owner, $this->status, $this->workflow, $whitelist, $this->guidelines, $this->language, $this->thumbnails, $this->fields);
		}

		return $status;
	}

	// TODO: are we even using this function anywhere? save() does the same thing
	public function create($title, $type, $public, $slug, $language, $description, $owner, $guidelines, $thumbnails, $workflow, $whitelist, $fields) {
		$this->title = $title;
		$this->type = $type;
		$this->public = $public;
		$this->slug = $slug;
		$this->language = $language;
		$this->description = $description;
		$this->owner = $owner;
		$this->status = "pending";
		$this->guidelines = $guidelines;
		$this->thumbnails = $thumbnails;
		$this->workflow = $workflow;
		$this->whitelist = $whitelist;
		$this->fields = $fields;

		if ($title != "" && $slug != "") {
			$status = $this->db->addProject($this->title, $this->type, $this->public, $this->slug, $this->description, $this->owner, $this->status, $this->workflow, $this->whitelist, $this->guidelines, $this->language, $this->thumbnails, $this->fields);

			if ($status) {
				return "success";
			} else {
				return "error";
			}
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

	public function getProoferStats($type = 'proof') {
		return $this->db->getProoferStats($this->project_id, $type);
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

	public function allowedToJoin($username) {
		if (is_array($this->whitelist)) {
			return in_array($username, $this->whitelist);
		} else {
			return false;
		}
	}

	public function getJSON() {
		return json_encode(array("project_id" => $this->project_id, "title" => $this->title, "type" => $this->type, "slug" => $this->slug, "language" => $this->language, "description" => $this->description, "owner" => $this->owner, "status" => $this->status));
	}

	static public function getAvailableProjects($username, $owner = '') {
		$app_url = Settings::getProtected('app_url');
		$db = Settings::getProtected('db');

		$projects = $db->getAvailableProjects($username, $owner);

		foreach ($projects as &$project) {
			$project["title"] = stripslashes($project["title"]);

			if ($project["type"] == "system") {
				$project["link"] = "$app_url/projects/{$project["slug"]}";
			} else if ($project["type"] == "user") {
				$project["link"] = "$app_url/users/{$project["owner"]}/projects/{$project["slug"]}";
			}

			$project["percentage"] = round($project["percentage"], 0);
		}

		return $projects;
	}

	// TODO: refactor since this function is largely identical to the previous one
	static public function getActiveProjectsForUser($username) {
		$app_url = Settings::getProtected('app_url');
		$db = Settings::getProtected('db');

		$projects = $db->getActiveProjectsForUser($username);

		foreach ($projects as &$project) {
			$project["title"] = stripslashes($project["title"]);

			if ($project["type"] == "system") {
				$project["link"] = "$app_url/projects/{$project["slug"]}";
			} else if ($project["type"] == "user") {
				$project["link"] = "$app_url/users/{$project["owner"]}/projects/{$project["slug"]}";
			}

			$project["percentage"] = round($project["percentage"], 0);
		}

		return $projects;
	}

	static public function getPublicCompletedProjects($user = '', $limit = true) {
		$app_url = Settings::getProtected('app_url');
		$db = Settings::getProtected('db');

		$projects = $db->getPublicCompletedProjects($user, $limit);

		foreach ($projects as &$project) {
			$project["title"] = stripslashes($project["title"]);

			if ($project["type"] == "system") {
				$project["link"] = "$app_url/projects/{$project["slug"]}";
			} else if ($project["type"] == "user") {
				$project["link"] = "$app_url/users/{$project["owner"]}/projects/{$project["slug"]}";
			}
		}

		return $projects;
	}

	public function describe() {
		$str = "[pid={$this->project_id}]";
		$str .= " | [title={$this->title}]";
		$str .= " | [type={$this->type}]";
		$str .= " | [public={$this->public}]";
		$str .= " | [slug={$this->slug}]";
		$str .= " | [language={$this->language}]";
		$str .= " | [description={$this->description}]";
		$str .= " | [owner={$this->owner}]";
		$str .= " | [status={$this->status}]";
		$str .= " | [guidelines={$this->guidelines}]";
		$str .= " | [thumbnails={$this->thumbnails}]";
		$str .= " | [workflow={$this->workflow}]";
		$str .= " | [whitelist={$this->whitelist}]";
		$str .= " | [fields={$this->fields}]";
		$str .= " | [url={$this->url}]";
		$str .= " | [admin_url={$this->admin_url}]";

		return $str;
	}
}
