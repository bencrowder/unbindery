<?php

class User {
	private $db;

	private $username;
	private $password;
	private $name;
	private $email;
	private $status;
	private $score = 0;
	private $hash;
	private $signup_date;
	private $last_login;
	private $role;
	private $prefs;

	private $in_db;			// Whether we have a db entry yet

	public function User($username = "") {
		$this->db = Settings::getProtected('db');

		// Defaults
		$this->status = 'pending';
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

		if (isset($user) && array_key_exists('username', $user) && array_key_exists('email', $user) && array_key_exists('status', $user)) {
			$this->password = trim($user["password"]);
			$this->name = trim($user["name"]);
			$this->email = trim($user["email"]);
			$this->status = $user["status"];
			$this->role = $user["role"];
			$this->score = $user["score"];
			$this->signup_date = $user["signup_date"];
			$this->last_login = $user["last_login"];
			$this->hash = $user["hash"];
			$this->prefs = json_decode($user["prefs"]);
			$this->in_db = true;
		}
		$this->username = $username;

		$this->getStats();
	}

	public function save() {
		if ($this->in_db) {
			$status = $this->db->saveUser($this);
		} else {
			$status = $this->db->createUser($this);
			$this->in_db = true;
		}

		return $status;
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

	public function isMember($projectSlug, $role, $owner='') {
		return $this->db->isMember($this->username, $projectSlug, $role, $owner);
	}

	public function hasProjectItem($projectSlug) {
		return $this->db->userHasProjectItem($this->username, $projectSlug);
	}

	public function getRolesForProject($projectSlug) {
		$dbRoles = $this->db->getRolesForProject($this->username, $projectSlug);

		// Parse it into a cleaner array
		$roles = array();
		foreach ($dbRoles as $role) {
			$roles[] = $role['role'];
		}

		return $roles;
	}

	public function assignToProject($projectSlug, $role) {
		// make sure they're not already a member
		if (!$this->isMember($projectSlug, $role)) {
			$project = new Project($projectSlug);

			// insert into membership (default = proofer)
			$this->db->assignUserToProject($this->username, $project->project_id, $role);

			return true;
		} else {
			return false;
		}
	}

	public function removeFromProject($projectSlug, $role) {
		if ($this->isMember($projectSlug, $role)) {
			$project = new Project($projectSlug);
			$this->db->removeUserFromProject($this->username, $project->project_id, $role);

			return true;
		}

		return false;
	}

	public function getStats() {
		$user = $this->db->getUserStats($this->username);

		if (isset($user)) {
			$this->score = ($user["score"] == "") ? 0 : $user["score"];
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

		$this->db->setUserStatus($username, 'active');

		return true;
	}

	public function updateLogin() {
		$this->db->updateUserLastLogin($this->username);
	}

	public function addTranscript($item, $status, $transcript, $fields, $type) {
		return $this->db->addItemTranscript($item->project_id, $item->item_id, $status, $transcript, $fields, $this->username, $type);
	}

	public function updateTranscript($item, $status, $transcript, $fields, $type) {
		return $this->db->updateItemTranscript($item->project_id, $item->item_id, $status, $transcript, $fields, $this->username, $type);
	}

	public function loadTranscript($item, $type) {
		return $this->db->loadItemTranscript($item->project_id, $item->item_id, $this->username, $type);
	}

	public function setStatus($status) {
		return $this->db->setUserStatus($this->username, $status);
	}

	public function updateScoreForItem($itemId, $projectId, $scoreInc, $queueType) {
		return $this->db->updateUserScoreForItem($this->username, $itemId, $projectId, $scoreInc, $queueType);
	}

	static public function getTopUsers() {
		$db = Settings::getProtected('db');

		return $db->getTopUsers();
	}

	public function getResponse($projectSlug = '') {
		$this->getStats();

		$response = array(
			'loggedin' => true,
			'username' => $this->username,
			'name' => $this->name,
			'email' => $this->email,
			'status' => $this->status,
			'score' => $this->score,
			'hash' => $this->hash,
			'signup_date' => $this->signup_date,
			'last_login' => $this->last_login,
			'role' => $this->role,
			'prefs' => $this->prefs,
			'proofed' => $this->proofed,
			'proofed_past_week' => $this->proofed_past_week
		);

		if ($projectSlug) {
			$project = new Project($projectSlug);

			$response['is_member'] = $this->isMember($projectSlug, 'proofer');
			$response['is_owner'] = ($this->username == $project->owner);
			$response['is_admin'] = $this->isMember($projectSlug, 'admin');
			$response['project_roles'] = join('|', $this->getRolesForProject($projectSlug));
		}

		return $response;
	}

	static public function getAuthenticatedUser() {
		$auth = Settings::getProtected('auth');

		$auth->forceAuthentication();

		return new User($auth->getUsername());
	}
}
