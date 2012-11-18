<?php

require_once '../modules/db/DbInterface.php';

class DbMySQL implements DbInterface {
	private $host;
	private $username;
	private $password;
	private $database;
	private $db;

	public function create($host, $username, $password, $database) {
		$this->host = $host;
		$this->username = $username;
		$this->password = $password;
		$this->database = $database;
	}

	public function connect() {
		$this->db = new MySQLi($this->host, $this->username, $this->password, $this->database);
	}

	public function close() {
		$this->db->close();
	}

	public function query($query, $params = array()) {
		$results = array();
		$types = '';

		$this->connect();

		for ($i=0; $i<count($params); $i++) {
			if (is_numeric($params[$i])) {
				$types .= 'i';
			} else {
				$types .= 's';
			}
		}

		// We can't pass $params in to call_user_func_array, so we need to make a copy
		$bind_params[0] = &$types;
		for ($i=1; $i<=count($params); $i++) {
			$bind_params[$i] = &$params[$i-1];
		}

		// Prepare the statement
		if ($stmt = $this->db->prepare($query)) {
			if (count($params) > 0) {
				// Execute $stmt->bind_param() with our parameters
				call_user_func_array(array($stmt, "bind_param"), $bind_params);
			}

			$stmt->execute();

			// Now we want to get the results and put them in an associative array
			$meta = $stmt->result_metadata();
			while ($field = $meta->fetch_field()) {
				$result_params[] = &$row[$field->name];
			}
			call_user_func_array(array($stmt, 'bind_result'), $result_params);

			while ($stmt->fetch()) {
				foreach ($row as $key=>$val) {
					$c[$key] = $val;
				}
				$results[] = $c;
			}
			
			$stmt->close();
		}

		if ($this->db->error) {
			error_log("SQL error on: $query");
			error_log("Error: " . $this->db->error);
		}

		$this->close();
		return $results;
	}

	public function execute($sql, $params = array()) {
		$status = false;

		$types = '';

		$this->connect();

		for ($i=0; $i<count($params); $i++) {
			if (is_numeric($params[$i])) {
				$types .= 'i';
			} else {
				$types .= 's';
			}
		}

		// We can't pass $params in to call_user_func_array, so we need to make a copy
		$bind_params[0] = &$types;
		for ($i=1; $i<=count($params); $i++) {
			$bind_params[$i] = &$params[$i-1];
		}

		// Prepare the statement
		if ($stmt = $this->db->prepare($sql)) {
			if (count($params) > 0) {
				// Execute $stmt->bind_param() with our parameters
				call_user_func_array(array($stmt, "bind_param"), $bind_params);
			}

			$status = $stmt->execute();
			$stmt->close();
		}

		if ($this->db->error) {
			error_log("SQL error on: $sql");
			error_log("Error: " . $this->db->error);
		}

		if ($this->db->insert_id) {
			$status = $this->db->insert_id;
		}

		$this->close();

		return $status;
	}

	// TODO: expand this to allow parameters
	public function execute_multi($sql) {
		try {
			$this->connect();

			$status = $this->db->multi_query($sql);

			if ($this->db->error) {
				error_log("SQL error on: $sql");
				error_log("Error: " . $this->db->error);
			}

			$this->close();

			return $status;
		} catch (Exception $e) {
			return false;
		}
	}

	public function last_insert_id() {
		return $this->db->insert_id;
	}

	/* Data retrieval functions */
	/* -------------------------------------------------- */

	// Returns: name, email, status, admin
	public function loadUser($username) {
		$users = $this->query("SELECT * FROM users WHERE username = ?", array($username));

		$user = (count($users) > 0) ? $users[0] : array();

		return $user;
	}

	// Returns: ?
	public function saveUser($user) {
		$sql = "UPDATE users SET username = ?, password = ?, name = ?, email = ?, score = ?, status = ?, hash = ?,  signup_date = ?, last_login = ?, role = ?, prefs = ? WHERE username = ?";

		return $this->execute($sql, array($user->username, $user->password, $user->name, $user->email, $user->score, $user->status, $user->hash, $user->signup_date, $user->last_login, $user->role, $user->prefs, $user->username));
	}

	// Returns: ?
	public function createUser($user) {
		$sql = "INSERT INTO users (username, password, name, email, score, status, hash, signup_date, last_login, role, prefs) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";

		return $this->execute($sql, array($user->username, $user->password, $user->name, $user->email, $user->score, $user->status, $user->hash, $user->signup_date, $user->last_login, $user->role, $user->prefs));
	}

	// Returns: boolean
	public function deleteUser($username) {
		$sql = "DELETE FROM users WHERE username = ?;";

		return $this->execute($sql, array($username));
	}

	// Returns: boolean
	public function isMember($username, $projectSlug, $owner = '') {
		$query = "SELECT roles.id FROM roles JOIN projects ON roles.project_id = projects.id WHERE username = ? AND projects.slug = ?";
		$args = array($username, $projectSlug);
		if ($owner != '') {
			$query .= " AND projects.owner = ?";
			array_push($args, $owner);
		}

		$result = $this->query($query, $args);

		return (count($result) > 0) ? true : false;
	}

	// Returns: boolean
	public function updateUserSettings($username, $name, $email, $password) {
		$sql = "UPDATE users SET name = ?, email = ?, ";
		if ($password != '') {
			$sql .= "password = ?, ";
		}
		$sql .= "WHERE username = ?";

		return $this->execute($sql, array($name, $email, md5($password), $username));
	}

	// Returns: roles (array of strings)
	public function getRolesForProject($username, $project_slug) {
		$results = $this->query("SELECT role FROM roles JOIN projects ON roles.project_id = projects.id WHERE username = ? AND projects.slug = ?", array($username, $project_slug));
		return $results;
	}

	// Returns: status
	public function assignUserToProject($username, $project_id, $role) {
		$sql = "INSERT INTO roles (project_id, username, role) VALUES (?, ?, ?);";
		return $this->execute($sql, array($project_id, $username, $role));
	}

	// Returns: status
	public function removeUserFromProject($username, $project_id) {
		$sql = "DELETE FROM roles WHERE project_id = ? AND username = ?";
		return $this->execute($sql, array($project_id, $username));
	}

	// Returns: boolean
	public function itemExists($item_id, $project_slug) {
		$query = "SELECT items.id FROM items JOIN projects ON projects.id = items.project_id WHERE items.id = ? AND projects.slug = ?";
		$results = $this->query($query, array($item_id, $project_slug));

		if (count($results) > 0) {
			return true;
		} else {
			return false;
		}
	}

	// Returns: none
	public function setItemStatus($itemId, $projectId, $status) {
		$sql = "UPDATE items SET status = ? WHERE id = ? AND project_id = ?";
		return $this->execute($sql, array($status, $itemId, $projectId));
	}

	// Returns: boolean
	public function deleteItemFromDatabase($itemId) {
		$sql = "DELETE FROM items WHERE id = ?";
		return $this->execute($sql, array($itemId));
	}

	// Returns: none
	public function setUserStatus($username, $status) {
		$sql = "UPDATE users SET status = ? WHERE username = ?";
		return $this->execute($sql, array($status, $username));
	}

	// Returns: boolean
	public function userHasProjectItem($username, $projectSlug) {
		$query = "SELECT queues.id ";
		$query .= "FROM queues ";
		$query .= "JOIN projects ON queues.project_id = projects.id ";
		$query .= "WHERE queues.queue_name = ? ";
		$query .= "AND projects.slug = ? ";
		$query .= "AND queues.date_removed IS NULL";

		$results = $this->query($query, array("user.proof:$username", $projectSlug));

		return (count($results) > 0) ? true : false;
	}

	// Returns: item ID
	public function getNextAvailableItem($username, $projectSlug) {
		$query = "SELECT items.id, items.project_id ";
		$query .= "FROM items JOIN projects ON projects.id = items.project_id ";
		$query .= "WHERE items.status = 'available' ";
		$query .= "AND projects.slug = ? ";
		$query .= "AND items.id NOT IN ";
		$query .= "(SELECT item_id FROM queues ";
		$query .= "WHERE queue_name = ? AND project_id = items.project_id) ";
		$query .= "ORDER BY items.id ASC ";
		$query .= "LIMIT 1;";

		$results = $this->query($query, array($projectSlug, "user.proof:$username"));

		return (count($results) > 0) ? $results[0]['id'] : -1;
	}

	// Returns: user row
	public function getUserStats($username) {
		$userString = "user.proof:$username";

		$users = $this->query("SELECT score, COUNT(DISTINCT item_id) AS proofed, (SELECT COUNT(DISTINCT item_id) FROM queues WHERE queue_name = ? AND date_removed IS NOT NULL AND date_removed > DATE_SUB(NOW(), INTERVAL 7 DAY)) AS proofed_past_week FROM queues, users WHERE queue_name = ? AND date_removed IS NOT NULL AND username = ?;", array($userString, $userString, $username));

		$user = $users[0];

		return $user;
	}

	// Returns: history row
	public function getUserHistory($username) {
		$query = "SELECT items.id AS item_id, items.title AS item_title, projects.title AS project_title, queues.date_removed AS date_comp, DATE_FORMAT(queues.date_removed, '%e %b %Y') AS date_completed, projects.slug AS project_slug, projects.type AS project_type, projects.owner AS project_owner ";
		$query .= "FROM queues JOIN items ON item_id = items.id ";
		$query .= "JOIN projects ON queues.project_id = projects.id ";
		$query .= "WHERE queue_name = ? ";
		$query .= "AND queues.date_removed IS NOT null ";
		$query .= "GROUP BY item_id ";
		$query .= "ORDER BY queues.date_removed DESC LIMIT 5;";

		$history = $this->query($query, array("user.proof:$username"));

		return $history;
	}

	// Returns: username or false
	public function validateHash($hash) {
		$query = "SELECT username FROM users WHERE hash = ?";
		$results = $this->query($query, array($hash));

		if (count($results)) {
			$username = trim($results[0]["username"]);
		} else {
			$username = false;
		}

		return $username;
	}

	// Returns: none
	public function updateUserLastLogin($username) {
		$sql = "UPDATE users SET last_login = NOW() WHERE username = ?";
		return $this->execute($sql, array($username));
	}

	// Returns: none
	public function updateUserStatus($username, $status) {
		$sql = "UPDATE users SET status = ? WHERE username = ?";
		return $this->execute($sql, array($status, $username));
	}

	// Returns: id, project_id, title, transcript, status, type
	public function loadItem($item_id, $project_slug) {
		$query = "SELECT items.id AS id, projects.id AS project_id, projects.slug AS project_slug, projects.type AS project_type, projects.public AS project_public, projects.owner AS project_owner, items.title AS title, items.transcript AS transcript, items.status AS status, items.type AS type, items.href AS href, items.workflow_index AS workflow_index, items.order AS `order` FROM items ";
		$query .= "JOIN projects ON items.project_id = projects.id ";
		$query .= "WHERE items.id = ? ";
		$query .= "AND projects.slug = ?;";

		$results = $this->query($query, array($item_id, $project_slug));

		return (count($results) > 0) ? $results[0] : false;
	}	

	// Returns: id, project_id, title, transcript, status, type
	public function loadItemWithProjectID($item_id, $project_id) {
		$query = "SELECT items.id AS id, projects.id AS project_id, projects.slug AS project_slug, projects.type AS project_type, projects.public AS project_public, projects.owner AS project_owner, items.title AS title, items.transcript AS transcript, items.status AS status, items.type AS type, items.href AS href, items.workflow_index AS workflow_index, items.order AS `order` FROM items ";
		$query .= "JOIN projects ON items.project_id = projects.id ";
		$query .= "WHERE items.id = ? ";
		$query .= "AND projects.id = ?;";

		$results = $this->query($query, array($item_id, $project_id));

		return (count($results) > 0) ? $results[0] : false;
	}	

	// Returns: transcript
	public function loadItemTranscript($projectId, $itemId, $username, $type) {
		$query = "SELECT transcript FROM transcripts ";
		$query .= "WHERE project_id = ? ";
		$query .= "AND item_id = ? ";
		$query .= "AND user = ? ";
		$query .= "AND type = ?;";

		$results = $this->query($query, array($projectId, $itemId, $username, $type));

		return (count($results) > 0) ? trim($results[0]['transcript']) : '';
	}

	// Returns: array of [transcript, user]
	public function loadItemTranscripts($projectId, $itemId, $type) {
		$query = "SELECT transcript, user FROM transcripts ";
		$query .= "WHERE project_id = ? ";
		$query .= "AND item_id = ? ";
		$query .= "AND type = ? ";
		$query .= "AND (status = 'completed' OR status = 'reviewed');";

		$transcripts = $this->query($query, array($projectId, $itemId, $type));

		return $transcripts;
	}

	// Returns: boolean
	public function saveExistingItem($itemId, $title, $projectId, $transcript, $status, $type, $href, $workflowIndex, $order) {
		$sql = "UPDATE items ";
		$sql .= "SET title = ?, project_id = ?, transcript = ?, status = ?, type = ?, href = ?, workflow_index = ?, `order` = ? ";
		$sql .= "WHERE id = ?;";

		return $this->execute($sql, array($title, $projectId, $transcript, $status, $type, $href, $workflowIndex, $order, $itemId));
	}

	// Returns: boolean
	public function userHasTranscriptDraft($username, $item_id, $project_id, $type) {
		$query = "SELECT transcript FROM transcripts ";
		$query .= "WHERE item_id = ? ";
		$query .= "AND project_id = ? ";
		$query .= "AND type = ? ";
		$query .= "AND user = ?;";

		$results = $this->query($query, array($item_id, $project_id, $type, $username));

		return (count($results) > 0) ? true : false;
	}

	// Returns: boolean
	public function updateItemTranscript($projectId, $itemId, $status, $transcript, $username, $type) {
		$sql = "UPDATE transcripts SET transcript = ?, ";
		$sql .= "date = NOW(), ";
		$sql .= "status = ? ";
		$sql .= "WHERE item_id = ? ";
		$sql .= "AND project_id = ? ";
		$sql .= "AND type = ? ";
		$sql .= "AND user = ?;";

		return $this->execute($sql, array($transcript, $status, $itemId, $projectId, $type, $username));
	}

	// Returns: boolean
	public function addItemTranscript($projectId, $itemId, $status, $transcript, $username, $type) {
		$sql = "INSERT INTO transcripts (project_id, item_id, user, date, transcript, status, type) VALUES (?, ?, ?, NOW(), ?, ?, ?);";

		return $this->execute($sql, array($projectId, $itemId, $username, $transcript, $status, $type));
	}

	// Returns: none
	public function updateUserScoreForItem($username, $itemId, $projectId, $score, $queueType) {
		$sql = "UPDATE users, queues SET score = score + ? ";
		$sql .= "WHERE users.username = ? ";
		$sql .= "AND queue_name = ? ";
		$sql .= "AND item_id = ? ";
		$sql .= "AND project_id = ? ";
		$sql .= "AND date_removed IS NULL;";

		return $this->execute($sql, array($score, $username, "user.$queueType:$username", $itemId, $projectId));
	}

	// Returns: project array
	public function loadProject($project_slug) {
		$query = "SELECT *, ";
		$query .= "DATE_FORMAT(date_started, '%e %b %Y') AS datestarted, ";
		$query .= "DATE_FORMAT(date_completed, '%e %b %Y') AS datecompleted, ";
		$query .= "DATEDIFF(date_completed, date_started) AS days_spent, ";
		$query .= "(SELECT COUNT(id) ";
		$query .=     "FROM items ";
		$query .=     "WHERE items.project_id = projects.id";
		$query .= ") AS num_items, ";
		$query .= "(SELECT COUNT(id) ";
		$query .=     "FROM items ";
		$query .=     "WHERE items.project_id = projects.id ";
		$query .=     "AND items.status = 'completed'";
		$query .= ") AS items_completed, ";
		$query .= "(SELECT COUNT(username) ";
		$query .=     "FROM roles ";
		$query .=     "WHERE roles.project_id = projects.id ";
		$query .=     "AND roles.role = 'proofer'";
		$query .= ") AS num_proofers, ";
		$query .= "(SELECT COUNT(username) ";
		$query .=     "FROM roles ";
		$query .=     "WHERE roles.project_id = projects.id ";
		$query .=     "AND roles.role = 'reviewer'";
		$query .= ") AS num_reviewers ";
		$query .= "FROM projects ";
		$query .= "WHERE slug = ?;";

		$results = $this->query($query, array($project_slug));

		return (count($results) > 0 && isset($results[0])) ? $results[0] : false;
	}

	// Returns: none
	public function saveProject($project_id, $title, $type, $public, $slug, $description, $owner, $status, $workflow, $whitelist, $guidelines, $language, $thumbnails, $fields, $downloadTemplate, $characters) {
		$sql = "UPDATE projects ";
		$sql .= "SET title = ?, ";
		$sql .= "type = ?, ";
		$sql .= "public = ?, ";
		$sql .= "slug = ?, ";
		$sql .= "description = ?, ";
		$sql .= "owner = ?, ";
		$sql .= "status = ?, ";
		$sql .= "workflow = ?, ";
		$sql .= "whitelist = ?, ";
		$sql .= "guidelines = ?, ";
		$sql .= "language = ?, ";
		$sql .= "thumbnails = ?, ";
		$sql .= "fields = ?, ";
		$sql .= "download_template = ?, ";
		$sql .= "characters = ? ";
		$sql .= "WHERE id = ?;";

		return $this->execute($sql, array($title, $type, $public, $slug, $description, $owner, $status, $workflow, $whitelist, $guidelines, $language, $thumbnails, $fields, $downloadTemplate, $characters, $project_id));
	}

	public function addProject($title, $type, $public, $slug, $description, $owner, $status, $workflow, $whitelist, $guidelines, $language, $thumbnails, $fields, $downloadTemplate, $characters) {
		$sql = "INSERT INTO projects ";
		$sql .= "(title, type, public, slug, description, owner, status, workflow, whitelist, guidelines, language, thumbnails, fields, download_template, characters, date_started) ";
		$sql .= "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW());";

		return $this->execute($sql, array($title, $type, $public, $slug, $description, $owner, $status, $workflow, $whitelist, $guidelines, $language, $thumbnails, $fields, $downloadTemplate, $characters));
	}

	// Returns: boolean
	public function addItem($title, $projectId, $transcript, $type, $href) {
		$sql = "INSERT INTO items (title, project_id, transcript, status, type, href, workflow_index, `order`) VALUES (?, ?, ?, 'available', ?, ?, 0, 9999); ";

		return $this->execute($sql, array($title, $projectId, $transcript, $type, $href));
	}

	// Returns: ?
	public function saveTranscript($itemId, $transcript) {
		$sql = "UPDATE items SET transcript = ? WHERE id = ?; ";
		return $this->execute($sql, array($transcript, $itemId));
	}

	// Returns: # items in project
	public function getNumProjectItems($projectId) {
		$query = "SELECT COUNT(*) AS total FROM items WHERE project_id = ?;";
		$results = $this->query($query, array($projectId));
		$result = $results[0];

		if (isset($result)) {
			return $result['total'];
		} else {
			return 0;
		}
	}

	// Returns: # completed items in project
	public function getNumCompletedProjectItems($projectId) {
		$query = "SELECT COUNT(*) AS completed FROM items WHERE project_id = ? AND status = 'completed';";
		$results = $this->query($query, array($projectId));
		$result = $results[0];

		if (isset($result)) {
			return $result['completed'];
		} else {
			return 0;
		}
	}

	// Returns: array of items
	public function getItemsForProject($projectId) {
		$results = $this->query("SELECT id FROM items WHERE project_id = ? ORDER BY id ASC;", array($projectId));

		$ids = array();

		foreach ($results as $row) {
			array_push($ids, $row['id']);
		}

		return $ids;
	}

	// Returns: array of proof and review information
	public function getStatsForItem($itemId) {
		$response = array();
		$response['proofs'] = array();
		$response['reviews'] = array();

		// Get the proofs
		$results = $this->query("SELECT *, DATE_FORMAT(date_added, '%e %b %Y') AS date_assigned_formatted, DATE_FORMAT(date_removed, '%e %b %Y') AS date_completed_formatted FROM queues WHERE item_id = ? AND queue_name LIKE 'user.proof:%' ORDER BY id ASC;", array($itemId));

		foreach ($results as $row) {
			array_push($response['proofs'], array(
					"user" => substr($row['queue_name'], strpos($row['queue_name'], ':') + 1),
					"date_assigned" => $row['date_assigned_formatted'],
					"date_completed" => $row['date_completed_formatted'],
				)
			);
		}

		// Get the reviews 
		$results = $this->query("SELECT *, DATE_FORMAT(date_added, '%e %b %Y') AS date_assigned_formatted, DATE_FORMAT(date_removed, '%e %b %Y') AS date_completed_formatted FROM queues WHERE item_id = ? AND queue_name LIKE 'user.review:%' ORDER BY id ASC;", array($itemId));

		foreach ($results as $row) {
			array_push($response['reviews'], array(
					"user" => substr($row['queue_name'], strpos($row['queue_name'], ':') + 1),
					"date_assigned" => $row['date_assigned_formatted'],
					"date_completed" => $row['date_completed_formatted'],
				)
			);
		}

		return $response;
	}

	// Returns: array of proofers
	public function getProoferStats($projectId, $type = 'proof') {
		$query = "SELECT DISTINCT users.username, ";
		$query .= "(";
		$query .=     "SELECT COUNT(DISTINCT item_id) ";
		$query .=     "FROM queues ";
		$query .=     "WHERE queue_name = CONCAT('user.$type:', users.username) ";
		$query .=     "AND date_removed IS NOT NULL";
		$query .= ") AS items, ";
		$query .= "ROUND(";
		$query .=     "(";
		$query .=         "SELECT COUNT(DISTINCT item_id) ";
		$query .=         "FROM queues ";
		$query .=         "WHERE queue_name = CONCAT('user.$type:', users.username) ";
		$query .=         "AND date_removed IS NOT NULL";
		$query .=     ") ";
		$query .= "/ ";
		$query .=     "(";
		$query .=         "SELECT COUNT(DISTINCT id) ";
		$query .=         "FROM items ";
		$query .=         "WHERE project_id = ?";
		$query .=     ") ";
		$query .= "* 100, 0) AS percentage ";
		$query .= "FROM users ";
		$query .= "INNER JOIN queues ";
		$query .= "ON queues.queue_name = CONCAT('user.$type:', users.username) ";
		$query .= "AND queues.project_id = ? ";
		$query .= "ORDER BY items DESC;";

		return $this->query($query, array($projectId, $projectId)); //, "{$type}er"));
	}

	// Returns: array of items
	public function getBasicItems($projectId) {
		return $this->query("SELECT id, title, status FROM items WHERE project_id = ? ORDER BY items.id ASC;", array($projectId));
	}

	// Returns: array of projects available to user
	// (project is public OR user is owner OR user is in whitelist)
	// or, if $owner is specified, then (project is public OR user is in whitelist)
	public function getAvailableProjects($username, $owner) {
		$query = "";

		$query = "SELECT projects.title AS title, ";
		$query .= "projects.owner AS owner, ";
		$query .= "projects.slug AS slug, ";
		$query .= "projects.type AS type, ";
		$query .= "projects.public AS public, ";

		// Number of people volunteering on the project
		$query .= "(SELECT COUNT(DISTINCT username) ";
		$query .=     "FROM roles ";
		$query .=     "WHERE project_id = projects.id";
		$query .= ") AS num_people, ";

		// Number of items available for proofing
		$query .= "(SELECT COUNT(DISTINCT item_id) ";
		$query .=     "FROM queues ";
		$query .=     "WHERE queue_name = CONCAT('project.proof:', projects.slug) ";
		$query .=     "AND date_removed IS NULL";
		$query .= ") AS num_available_for_proofing, ";

		// Total number of items
		$query .= "(SELECT COUNT(DISTINCT id) ";
		$query .=     "FROM items ";
		$query .=     "WHERE project_id = projects.id";
		$query .= ") AS total_items, ";

		// Number of items marked as completed
		$query .= "(SELECT COUNT(DISTINCT id) ";
		$query .=     "FROM items ";
		$query .=     "WHERE project_id = projects.id ";
		$query .=     "AND items.status = 'completed'";
		$query .= ") AS completed_items, ";

		// Percentage of items completed
		$query .= "(";
		$query .=    "(SELECT COUNT(DISTINCT id) ";
		$query .=        "FROM items ";
		$query .=        "WHERE project_id = projects.id ";
		$query .=        "AND items.status = 'completed'";
		$query .=    ") ";
		$query .=    "/ ";
		$query .=    "(SELECT COUNT(DISTINCT id) ";
		$query .=        "FROM items ";
		$query .=        "WHERE project_id = projects.id";
		$query .=    ")";
		$query .= ") * 100 AS percentage ";

		$query .= "FROM projects ";
		$query .= "WHERE projects.status = 'active' ";

		if ($owner) {
			$query .= "AND projects.owner = ? ";

			$query .= "AND (";

			// Where the project is public
			$query .=     "projects.public = 1 ";

			// Or the user is in the whitelist
			$query .=     "OR projects.whitelist LIKE ? ";

			$query .= ")";

			$params = array($owner, "%[$username]%");
		} else {

			$query .= "AND (";
			// Where the project is public
			$query .=     "projects.public = 1 ";

			// Or the user is the owner
			$query .=     "OR projects.owner = ? ";

			// Or the user is in the whitelist
			$query .=     "OR projects.whitelist LIKE ? ";

			$query .= ")";

			$params = array($username, "%[$username]%");
		}

		$query .= "ORDER BY percentage DESC, ";
		$query .=     "projects.date_started DESC;";

		return $this->query($query, $params);
	}

	// TODO: refactor, since this function is largely identical to the previous one
	// Parameter: $username
	// Returns: array of projects
	public function getActiveProjectsForUser($username) {
		$query = "";

		$query = "SELECT projects.title AS title, ";
		$query .= "projects.owner AS owner, ";
		$query .= "projects.slug AS slug, ";
		$query .= "projects.type AS type, ";
		$query .= "projects.public AS public, ";

		// Number of people volunteering on the project
		$query .= "(SELECT COUNT(DISTINCT username) ";
		$query .=     "FROM roles ";
		$query .=     "WHERE project_id = projects.id";
		$query .= ") AS num_people, ";

		// Number of items available for proofing
		$query .= "(SELECT COUNT(DISTINCT item_id) ";
		$query .=     "FROM queues ";
		$query .=     "WHERE queue_name = CONCAT('project.proof:', projects.slug) ";
		$query .=     "AND date_removed IS NULL";
		$query .= ") AS num_available_for_proofing, ";

		// Total number of items
		$query .= "(SELECT COUNT(DISTINCT id) ";
		$query .=     "FROM items ";
		$query .=     "WHERE project_id = projects.id";
		$query .= ") AS total_items, ";

		// Number of items marked as completed
		$query .= "(SELECT COUNT(DISTINCT id) ";
		$query .=     "FROM items ";
		$query .=     "WHERE project_id = projects.id ";
		$query .=     "AND items.status = 'completed'";
		$query .= ") AS completed_items, ";

		// Percentage of items completed
		$query .= "(";
		$query .=    "(SELECT COUNT(DISTINCT id) ";
		$query .=        "FROM items ";
		$query .=        "WHERE project_id = projects.id ";
		$query .=        "AND items.status = 'completed'";
		$query .=    ") ";
		$query .=    "/ ";
		$query .=    "(SELECT COUNT(DISTINCT id) ";
		$query .=        "FROM items ";
		$query .=        "WHERE project_id = projects.id";
		$query .=    ")";
		$query .= ") * 100 AS percentage ";

		$query .= "FROM projects ";
		$query .= "WHERE projects.status = 'active' ";

		// Where user is owner
		$query .= "AND (";
		$query .=     "projects.owner = ? ";

		// Or user is a member
		$query .=     "OR (SELECT COUNT(id) ";
		$query .=         "FROM roles ";
		$query .=         "WHERE username = ? ";
		$query .=         "AND project_id = projects.id";
		$query .=     ") > 0 ";
		$query .= ")";

		$query .= "ORDER BY percentage DESC, ";
		$query .=     "projects.date_started DESC;";

		return $this->query($query, array($username, $username));
	}

	// Returns: array of projects
	public function getPublicCompletedProjects($user = '', $limit = true) {
		$query = "";

		$query = "SELECT projects.title AS title, ";
		$query .= "projects.owner AS owner, ";
		$query .= "projects.slug AS slug, ";
		$query .= "projects.type AS type, ";
		$query .= "projects.date_started AS date_started, ";
		$query .= "projects.date_completed AS date_completed, ";
		$query .= "DATE_FORMAT(projects.date_completed, '%e %b %Y') AS date_completed_formatted, ";

		// Number of people volunteering on the project
		$query .= "(SELECT COUNT(DISTINCT username) ";
		$query .=     "FROM roles ";
		$query .=     "WHERE project_id = projects.id";
		$query .= ") AS num_people, ";

		// Total number of items
		$query .= "(SELECT COUNT(DISTINCT id) ";
		$query .=     "FROM items ";
		$query .=     "WHERE project_id = projects.id";
		$query .= ") AS total_items ";

		$query .= "FROM projects ";
		$query .= "WHERE projects.status = 'completed' ";
		$query .= "AND projects.public = 1 ";
		if ($user) $query .= "AND projects.owner = ? ";
		$query .= "ORDER BY projects.date_completed DESC ";
		if ($limit) $query .= "LIMIT 5";

		$params = array();
		if ($user) array_push($params, $user);

		return $this->query($query, $params);
	}

	// Returns: array of users (username, score)
	public function getTopUsers() {
		return $this->query("SELECT username, score FROM users WHERE score > 0 ORDER BY score DESC LIMIT 10;", array());
	}

	// Returns: ?
	public function setUserScore($username, $score) {
		$sql = "UPDATE users SET score = score - ? WHERE username = ?;";
		return $this->execute($sql, array($username, $score));
	}

	// Returns: item_id, project_id, date_added
	public function loadQueue($name, $includeRemoved = false) {
		$query = "SELECT item_id, project_id, date_added, date_removed FROM queues WHERE queue_name = ?";
		$query .= (($includeRemoved == true) ? "" : " AND date_removed IS NULL");
		$query .= " ORDER BY item_id, date_added";

		return $this->query($query, array($name));
	}

	// Returns: ?
	public function addToQueue($queueName, $itemId, $projectId) {
		return $this->execute("INSERT INTO queues (queue_name, item_id, project_id, date_added) values (?, ?, ?, NOW());", array($queueName, $itemId, $projectId));
	}

	// Returns: ?
	public function removeFromQueue($queueName, $items) {
		foreach ($items as $item) {
			$sql = "UPDATE queues SET date_removed = NOW() WHERE queue_name = ? AND item_id = ? AND project_id = ? AND date_removed IS NULL";
			$this->execute($sql, array($queueName, $item['item_id'], $item['project_id']));
		}
	}

	// Returns: array of projects
	public function getUserProjectSummaries($username) {
		$userProofString = "user.proof:$username";
		$userReviewString = "user.review:$username";

		$query = "SELECT DISTINCT projects.id, projects.title, projects.slug, projects.type, projects.status, projects.owner, ";
		$query .= "(SELECT count(items.id) FROM items WHERE items.project_id = projects.id) AS num_items, ";
		$query .= "(SELECT COUNT(DISTINCT item_id) FROM queues WHERE queue_name=CONCAT('project.proof:', slug) AND date_removed IS NOT NULL) AS num_proofed, ";
		$query .= "(SELECT COUNT(DISTINCT item_id) FROM queues WHERE queue_name=CONCAT('project.review:', slug) AND date_removed IS NOT NULL) AS num_reviewed, ";
		$query .= "(SELECT count(item_id) FROM queues WHERE queue_name = CONCAT('project.proof:', slug) AND date_removed IS NULL AND item_id NOT IN (SELECT item_id FROM queues WHERE queue_name = ?)) AS available_to_proof, ";
		$query .= "(SELECT count(item_id) FROM queues WHERE queue_name = CONCAT('project.review:', slug) AND date_removed IS NULL AND item_id NOT IN (SELECT item_id FROM queues WHERE queue_name = ?)) AS available_to_review ";
		$query .= "FROM projects, roles ";
		$query .= "WHERE projects.id = roles.project_id ";
		$query .= "AND projects.status = 'active' ";
		$query .= "AND username = ?;";

		return $this->query($query, array($userProofString, $userReviewString, $username));
	}

	// Returns: array of users
	public function getAdminProjectsLatestWork($username, $limit = false) {
		$query = "SELECT DISTINCT queues.queue_name, queues.item_id, DATE_FORMAT(queues.date_removed, '%e %b %Y') AS date_completed, projects.slug AS project_slug ";
		$query .= "FROM queues ";
		$query .= "JOIN roles ON roles.project_id = queues.project_id ";
		$query .= "JOIN projects ON projects.id = queues.project_id ";
		$query .= "WHERE date_removed IS NOT NULL ";
		$query .= "AND ((roles.username = ? AND roles.role = 'admin') OR (projects.owner = ?)) ";
		$query .= "AND queue_name LIKE 'user.%' ";
		$query .= "ORDER BY date_removed DESC ";
		if ($limit != false) $query .= "LIMIT $limit ";

		return $this->query($query, array($username, $username));
	}

	// Returns: array of users
	public function getNewestProjectMembers($username, $limit = false) {
		$query = "SELECT roles.username, projects.slug, roles.role ";
		$query .= "FROM roles ";
		$query .= "JOIN projects ON projects.id = roles.project_id ";
		$query .= "WHERE (projects.id IN (SELECT project_id FROM roles WHERE username = ? AND role = 'admin') OR (projects.owner = ?)) ";
		$query .= "ORDER BY roles.id DESC ";
		if ($limit != false) $query .= "LIMIT $limit ";

		return $this->query($query, array($username, $username));
	}

	// Returns: array of users
	public function getUsers($limit = false) {
		$query = "SELECT username ";
		$query .= "FROM users ";
		$query .= "ORDER BY signup_date DESC ";
		if ($limit != false) $query .= "LIMIT $limit ";

		return $this->query($query, array());
	}

	// Returns: array of users
	public function getUserProjectsWithStats($username) {
		$query = "SELECT DISTINCT slug, title, ";
		$query .= "(SELECT COUNT(DISTINCT item_id) FROM queues WHERE queue_name = CONCAT('user.proof:', ?) AND date_removed IS NOT NULL AND queues.project_id = projects.id) AS items_proofed, ";
		$query .= "(SELECT COUNT(DISTINCT item_id) FROM queues WHERE queue_name = CONCAT('user.review:', ?) AND date_removed IS NOT NULL AND queues.project_id = projects.id) AS items_reviewed, ";
		$query .= "CASE (projects.owner = ?) ";
		$query .=     "WHEN 1 THEN CONCAT('owner', IFNULL(CONCAT(', ', GROUP_CONCAT(DISTINCT roles.role ORDER BY roles.role SEPARATOR ', ')), '')) ";
		$query .=     "WHEN 0 THEN GROUP_CONCAT(DISTINCT IFNULL(roles.role, '') ORDER BY roles.role SEPARATOR ', ') ";
		$query .= "END AS role ";
		$query .= "FROM projects ";
		$query .= "LEFT JOIN roles ON roles.project_id = projects.id ";
		$query .= "WHERE ((projects.owner = ?) or (roles.username = ?)) ";
		$query .= "GROUP BY slug;";

		return $this->query($query, array($username, $username, $username, $username, $username));
	}

	// Installation script
	public function install() {
		$sql = <<<SQL
DROP TABLE IF EXISTS `users`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `users` (
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) default NULL,
  `email` varchar(255) default NULL,
  `score` int(11) default 0,
  `status` varchar(50) default NULL,
  `hash` varchar(32) default NULL,
  `signup_date` date default NULL,
  `last_login` datetime default NULL,
  `role` varchar(255) default NULL,
  `prefs` varchar(4000) default NULL,
  PRIMARY KEY  (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

DROP TABLE IF EXISTS `queues`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `queues` (
  `id` int(11) NOT NULL auto_increment,
  `queue_name` varchar(255) NOT NULL,
  `item_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_removed` datetime default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

DROP TABLE IF EXISTS `items`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `items` (
  `id` int(11) NOT NULL auto_increment,
  `project_id` int(11) NOT NULL,
  `title` varchar(255) default NULL,
  `transcript` text,
  `status` varchar(255) default NULL,
  `type` varchar(255) NOT NULL,
  `href` varchar(1000) default NULL,
  `workflow_index` int(11) NOT NULL,
  `order` int(11) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

DROP TABLE IF EXISTS `roles`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL auto_increment,
  `project_id` int(11) NOT NULL,
  `username` varchar(255) default NULL,
  `role` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

DROP TABLE IF EXISTS `projects`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `projects` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(255) NOT NULL,
  `type` varchar(255) default NULL,
  `public` tinyint(1) DEFAULT '1',
  `slug` varchar(255) default NULL,
  `description` varchar(4000) default NULL,
  `owner` varchar(255) default NULL,
  `status` varchar(255) default NULL,
  `workflow` varchar(2000) default NULL,
  `whitelist` varchar(2000) default NULL,
  `guidelines` text default NULL,
  `language` varchar(255) default NULL,
  `thumbnails` varchar(400) default NULL,
  `date_started` date default NULL,
  `date_completed` date default NULL,
  `fields` varchar(4000) default NULL,
  `download_template` varchar(2000) default NULL,
  `characters` varchar(1000) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

DROP TABLE IF EXISTS `transcripts`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `transcripts` (
  `id` int(11) NOT NULL auto_increment,
  `item_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `user` varchar(255) default NULL,
  `date` datetime default NULL,
  `transcript` text,
  `status` varchar(255) default NULL,
  `type` varchar(255) default NULL,
  `fields` varchar(8000) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

DROP TABLE IF EXISTS `metadata`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `metadata` (
  `id` int(11) NOT NULL auto_increment,
  `table` varchar(255) NOT NULL,
  `object_id` int(11) NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` varchar(4000),
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;
SQL;

		return $this->execute_multi($sql);
	}

	// Return true if installed
	public function installed() {
		$query = "SHOW TABLES LIKE 'queues';";

		$results = $this->query($query, array());

		return (count($results) == 1) ? true : false;
	}
}
