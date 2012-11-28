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
	private $workflow = "@proofer, @proofer, @reviewer";	// Default
	private $fields;
	private $downloadTemplate = "<page item-id=\"{{ item.id }}\" proofers=\"{{ proofers }}\">\n\t{{ transcript }}\n</page>";
	private $characters;

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
			$this->workflow = trim($project['workflow']);
			$this->fields = trim($project['fields']);
			$this->downloadTemplate = trim($project['download_template']);
			$this->characters = trim($project['characters']);
			$this->dateStarted = trim($project['datestarted']);
			$this->dateCompleted = trim($project['datecompleted']);
			$this->daysSpent = trim($project['days_spent']);
			$this->numItems = trim($project['num_items']);
			$this->itemsCompleted = trim($project['items_completed']);
			$this->numProofers = trim($project['num_proofers']);
			$this->numReviewers = trim($project['num_reviewers']);

			// Put the character list into an array
			if (trim($this->characters) != '') {
				$this->characters = explode(" ", $this->characters);
			} else {
				$this->characters = array();
			}
		}
	}

	public function save() {
		$status = false;

		if ($this->project_id) {
			$status = $this->db->saveProject($this->project_id, $this->title, $this->type, $this->public, $this->slug, $this->description, $this->owner, $this->status, $this->workflow, $this->guidelines, $this->language, $this->fields, $this->downloadTemplate, $this->characters);
		} else {
			$status = $this->db->addProject($this->title, $this->type, $this->public, $this->slug, $this->description, $this->owner, $this->status, $this->workflow, $this->guidelines, $this->language, $this->fields, $this->downloadTemplate, $this->characters);
		}

		return $status;
	}

	// TODO: are we even using this function anywhere? save() does the same thing
	public function create($title, $type, $public, $slug, $language, $description, $owner, $guidelines, $workflow, $fields, $downloadTemplate, $characters) {
		$this->title = $title;
		$this->type = $type;
		$this->public = $public;
		$this->slug = $slug;
		$this->language = $language;
		$this->description = $description;
		$this->owner = $owner;
		$this->status = "pending";
		$this->guidelines = $guidelines;
		$this->workflow = $workflow;
		$this->fields = $fields;
		$this->downloadTemplate = $downloadTemplate;
		$this->characters = $characters;

		if ($title != "" && $slug != "") {
			$status = $this->db->addProject($this->title, $this->type, $this->public, $this->slug, $this->description, $this->owner, $this->status, $this->workflow, $this->guidelines, $this->language, $this->fields, $this->downloadTemplate, $characters);

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
		$itemIds = $this->db->getItemsForProject($this->project_id);
		$this->items = array();

		foreach ($itemIds as $id) {
			$item = new Item($id, $this->slug);

			// If we were able to load it (which should always be the case), add it to the array
			if ($item) {
				// Load proofs and reviews for the item
				$response = $this->db->getStatsForItem($id);

				// And populate the array
				$newItem = array(
					"id" => $item->item_id,
					"title" => $item->title,
					"project_id" => $item->project_id,
					"transcript" => $item->transcript,
					"status" => $item->status,
					"type" => $item->type,
					"href" => $item->href,
					"workflow_index" => $item->workflow_index,
					"proofs" => $response['proofs'],
					"reviews" => $response['reviews'],
				);

				array_push($this->items, $newItem);
			}
		}

		return $this->items;
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
		$str .= " | [workflow={$this->workflow}]";
		$str .= " | [fields={$this->fields}]";
		$str .= " | [downloadTemplate={$this->downloadTemplate}]";
		$str .= " | [characters={$this->characters}]";
		$str .= " | [url={$this->url}]";
		$str .= " | [admin_url={$this->admin_url}]";

		return $str;
	}

	public function getResponse() {
		$projectArray = array(
			"project_id" => $this->project_id,
			"title" => $this->title,
			"type" => $this->type,
			"public" => $this->public,
			"slug" => $this->slug,
			"language" => $this->language,
			"description" => $this->description,
			"owner" => $this->owner,
			"status" => $this->status,
			"guidelines" => $this->guidelines,
			"workflow" => $this->workflow,
			"fields" => $this->fields,
			"downloadTemplate" => $this->downloadTemplate,
			"characters" => $this->characters,
			"url" => $this->url,
			"admin_url" => $this->admin_url,
		);

		if (property_exists($this, "dateStarted")) $projectArray["date_started"] = $this->dateStarted;
		if (property_exists($this, "dateCompleted")) $projectArray["date_completed"] = $this->dateCompleted;
		if (property_exists($this, "daysSpent")) $projectArray["days_spent"] = $this->daysSpent;
		if (property_exists($this, "numItems")) $projectArray["num_items"] = $this->numItems;
		if (property_exists($this, "itemsCompleted")) $projectArray["items_completed"] = $this->itemsCompleted;
		if (property_exists($this, "numProofers")) $projectArray["num_proofers"] = $this->numProofers;
		if (property_exists($this, "numReviewers")) $projectArray["num_reviewers"] = $this->numReviewers;

		return $projectArray;
	}
}
