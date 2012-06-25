<?php

class User {
	private $db;

	private $username;
	private $password;
	private $name;
	private $email;
	private $status;
	private $score;
	private $hash;
	private $signup_date;
	private $last_login;
	private $role;
	private $theme;

	private $in_db;			// Whether we have a db entry yet

	public function User($username = "") {
		$this->db = Settings::getProtected('db');

		// Defaults
		$this->status = 'pending';
		$this->admin = false;
		$this->role = "user";

		if ($username != "") { 
			$this->load($username);
		}
	}

	public function __set($key, $val) {
		$this->$key = $val;
	}

	public function __get($key) {
		return $this->$key;
	}

	public function load($username) {
		$user = $this->db->loadUser($username);

		if (isset($user) && array_key_exists('name', $user) && array_key_exists('email', $user) && array_key_exists('status', $user)) {
			$this->password = trim($user["password"]);
			$this->name = trim($user["name"]);
			$this->email = trim($user["email"]);
			$this->status = $user["status"];
			$this->role = $user["role"];
			$this->score = $user["score"];
			$this->signup_date = $user["signup_date"];
			$this->last_login = $user["last_login"];
			$this->hash = $user["hash"];
			$this->theme = $user["theme"];
			$this->in_db = true;
		}
		$this->username = $username;
	}

	public function save() {
		if ($this->in_db) {
			$this->db->saveUser($this);
		} else {
			$this->db->createUser($this);
			$this->in_db = true;
		}
	}

	public function getAssignments() {
		return $this->db->getUserAssignments($this->username);
	}

	public function getProjects() {
		return $this->db->getUserProjects($this->username);
	}

	public function getProjectSummaries() {
		return $this->db->getUserProjectSummaries($this->username);
	}

	public function isAssigned($item_id, $project_slug) {
		return $this->db->isAssigned($this->username, $item_id, $project_slug);
	}

	public function isMember($projectSlug) {
		return $this->db->isMember($this->username, $projectSlug);
	}

	public function getRoleForProject($project_slug) {
		$role = $this->db->getRoleForProject($this->username, $project_slug);
		return $role;
	}

	public function assignToProject($projectSlug) {
		// make sure they're not already a member
		if (!$this->isMember($projectSlug)) {
			$project = new Project($projectSlug);

			// insert into membership (default = proofer)
			$this->db->assignUserToProject($this->username, $project->project_id, 'proofer');

			return true;
		} else {
			return false;
		}
	}

	public function removeFromProject($projectSlug) {
		if ($this->isMember($projectSlug)) {
			$project = new Project($projectSlug);
			$this->db->removeUserFromProject($this->username, $project->project_id);

			return true;
		}

		return false;
	}


	public function assignItem($item_id, $project_slug) {
		// make sure the item exists
		if (!$this->db->itemExists($item_id, $project_slug)) {
			return "nonexistent";
		}

		// make sure they're not already assigned
		if (!$this->isAssigned($item_id, $project_slug)) {
			$project = new Project($project_slug);
			$deadline_length = $project->deadline_days;

			// insert into assignments
			$this->db->assignItemToUser($this->username, $item_id, $project->project_id, $deadline_length);

			// get the updated number of assignments for this page
			$itemcount = $this->db->getItemAssignmentsCount($item_id);

			// if we're at the number of proofs, set the item to "assigned" (unavailable)
			if ($itemcount == $project->num_proofs) {
				$this->db->setItemStatus($item_id, 'assigned');
			}

			return "success";
		} else {
			return "already_assigned";
		}
	}

	public function getNewPage($project_slug = "") {
		// if no project specified, get the user's first current project
		if (!$project_slug || $project_slug == "") {
			$projects = $this->getProjects(); 
			$project_slug = $projects[0]["slug"];
		} else {
			if (!$this->isMember($project_slug)) {
				return array("statuscode" => "not_a_member");
			}
		}

		$project = new Project($project_slug);

		// first check to see if they're in training mode. if so, only return an item if they haven't done any yet.
		if ($this->status == "training") {
			if ($this->db->userHasUnfinishedAssignment($this->username)) {
				return array("statuscode" => "waiting_for_clearance");
			}
		}

		// make sure they've finished any existing items for that project (if not, go to next project)
		if ($this->db->userHasProjectAssignment($this->username, $project_slug)) {
			return array("statuscode" => "have_item_already");
		}

		// get next item from project where
		//		status = available
		//		user hasn't done that item
		//		number of assigned users is < project proof limit (2 reviews per item, etc.)
		$item_id = $this->db->getNextAvailableItem($this->username, $project_slug, $project->num_proofs);

		if ($item_id != -1) {
			$this->assignItem($item_id, $project_slug);

			return array("statuscode" => "success", "item_id" => $item_id);
		} else {
			return array("statuscode" => "not_found");
		}
	}

	public function getStats() {
		$user = $this->db->getUserStats($this->username);

		if (isset($user)) {
			$this->score = $user["score"];
			$this->proofed = $user["proofed"];
			$this->proofed_past_week = $user["proofed_past_week"];
		}
	}

	public function getHistory() {
		return $this->db->getUserHistory($this->username);
	}

	public function validateHash($hash) {
		$username = $this->db->validateHash($hash);
		if (!$username) return false;

		$this->db->setUserStatus($username, 'training');

		return true;
	}

	public function updateLogin() {
		$this->db->updateUserLastLogin($this->username);
	}

	static public function getTopUsers() {
		$db = Settings::getProtected('db');

		return $db->getTopUsers();
	}
}
