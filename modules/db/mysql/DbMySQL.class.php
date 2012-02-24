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

		$this->close();
		return $results;
	}

	public function execute($sql, $params = array()) {
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

			$stmt->execute();
			$stmt->close();
		}

		$this->close();
	}

	public function last_insert_id() {
		return $this->db->insert_id;
	}

	/* Data retrieval functions */
	/* -------------------------------------------------- */

	// Returns: name, email, status, admin
	public function loadUser($username) {
		$users = $this->query("SELECT username, password, name, email, status, admin, hash FROM users WHERE username = ?", array($username));
		if (count($users) > 0) {
			$user = $users[0];
		} else {
			$user = array();
		}
		return $user;
	}

	// Returns: ?
	public function saveUser($user) {
		$sql = "UPDATE users SET username = ?, password = ?, name = ?, email = ?, status = ?, admin = ?, hash = ? WHERE username = ?";

		return $this->execute($sql, array($user->username, $user->password, $user->name, $user->email, $user->status, $user->admin, $user->hash, $user->username));
	}

	// Returns: ?
	public function createUser($user) {
		$sql = "INSERT INTO users (username, password, name, email, status, admin, hash, score, signup_date) VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW());";

		return $this->execute($sql, array($user->username, $user->password, $user->name, $user->email, $user->status, $user->admin, $user->hash));
	}

	// Returns: item_id, item_title, project_id, project_title, project_slug, date_assigned, deadline, days_left
	public function getUserAssignments($username) {
		$items = $this->query("SELECT item_id, items.title AS item_title, assignments.project_id, projects.title AS project_title, projects.slug AS project_slug, DATE_FORMAT(date_assigned, '%e %b %Y') AS date_assigned, DATE_FORMAT(deadline, '%e %b %Y') AS deadline, DATEDIFF(deadline, NOW()) AS days_left FROM assignments JOIN items ON assignments.item_id = items.id JOIN projects ON assignments.project_id = projects.id WHERE username = ? AND assignments.date_completed IS NULL ORDER BY deadline ASC;", array($username));
		return $items;
	}

	// Returns: project_id, title, slug, author, num_proofs, role, completed, total, percentage, proof_percentage, available_pages
	public function getUserProjects($username) {
		$projects = $this->query("SELECT project_id, projects.title, projects.slug, projects.author, projects.num_proofs, role, (SELECT COUNT(*) FROM items WHERE items.project_id = membership.project_id AND status != 'available' AND status != 'assigned') AS completed, (SELECT COUNT(*) FROM items WHERE items.project_id = membership.project_id) AS total, (SELECT COUNT(*) FROM items WHERE items.project_id = projects.id AND status != 'available' AND status != 'assigned') / (SELECT COUNT(*) FROM items WHERE items.project_id = projects.id) * 100 AS percentage, (SELECT COUNT(*) FROM assignments WHERE assignments.project_id = projects.id AND assignments.date_completed IS NOT NULL) / (projects.num_proofs * (SELECT COUNT(*) FROM items where items.project_id = projects.id)) * 100 AS proof_percentage, (SELECT count(items.id) FROM items LEFT JOIN assignments ON assignments.item_id = items.id AND assignments.username = ? WHERE items.status = 'available' AND items.project_id = projects.id AND assignments.username IS NULL) AS available_pages FROM membership JOIN projects ON membership.project_id = projects.id WHERE username = ? AND projects.status = 'active' ORDER BY percentage DESC;", array($username, $username));
		return $projects;
	}

	// Returns: boolean
	public function isAssigned($username, $item_id, $project_slug) {
		$result = $this->query("SELECT assignments.id FROM assignments JOIN projects ON assignments.project_id = projects.id WHERE username = ? AND assignments.item_id = ? AND projects.slug = ?", array($username, $item_id, $project_slug));

		if (count($result) > 0) {
			$retval = true;
		} else {
			$retval = false;
		}

		return $retval;
	}

	// Returns: boolean
	public function isMember($username, $project_slug) {
		$result = $this->query("SELECT membership.id FROM membership JOIN projects ON membership.project_id = projects.id WHERE username = ? AND projects.slug = ?", array($username, $project_slug));

		if (count($result) > 0) {
			$retval = true;
		} else {
			$retval = false;
		}

		return $retval;
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

	// Returns: role (string)
	public function getRoleForProject($username, $project_slug) {
		$results = $this->query("SELECT role FROM membership JOIN projects ON membership.project_id = projects.id WHERE username = ? AND projects.slug = ?", array($username, $project_slug));
		$result = $results[0];
		$role = $result['role'];

		return $role;
	}

	// Returns: status
	public function assignUserToProject($username, $project_id, $role) {
		$sql = "INSERT INTO membership (project_id, username, role) VALUES (?, ?, ?);";
		return $this->execute($sql, array($username, $project_id, $role));
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

	// Returns: status
	public function assignItemToUser($username, $item_id, $project_id, $deadline_length) {
		$query = "INSERT INTO assignments (username, item_id, project_id, date_assigned, deadline) VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL $deadline_length DAY))";

		return $this->execute($query, array($username, $item_id, $project_id));
	}

	// Returns: integer
	public function getItemAssignmentsCount($item_id) {
		$query = "SELECT COUNT(*) AS itemcount FROM assignments WHERE assignments.item_id = ?";
		$results = $this->query($query, array($item_id));
		$result = $results[0];
		$itemcount = $result["itemcount"];

		return $itemcount;
	}

	// Returns: none
	public function setItemStatus($item_id, $status) {
		$sql = "UPDATE items SET status = ? WHERE id = ?";
		return $this->execute($sql, array($status, $item_id));
	}

	// Returns: none
	public function setUserStatus($username, $status) {
		$sql = "UPDATE users SET status = ? WHERE username = ?";
		return $this->execute($sql, array($status, $username));
	}

	// Returns: array of assignment IDs
	public function userHasUnfinishedAssignment($username) {
		$query = "SELECT assignments.id FROM assignments WHERE username = ? AND date_completed IS NOT NULL";
		$results = $this->query($query, array($username));

		return (count($results) > 0) ? true : false;
	}

	// Returns: boolean
	public function userHasProjectAssignment($username, $project_slug) {
		$query = "SELECT assignments.id FROM assignments JOIN projects ON assignments.project_id = projects.id WHERE username = ? AND projects.slug = ? AND assignments.date_completed IS NULL";
		$results = $this->query($query, array($username, $project_slug));

		return (count($results) > 0) ? true : false;
	}

	// Returns: item ID
	public function getNextAvailableItem($username, $project_slug, $num_proofs) {
		$query = "SELECT items.id, items.project_id, ";
		$query .= "(SELECT COUNT(*) FROM assignments WHERE assignments.item_id = items.id) AS itemcount ";
		$query .= "FROM items JOIN projects ON projects.id = items.project_id ";
		$query .= "WHERE items.status = 'available' ";
		$query .= "AND projects.slug = ? ";
		$query .= "AND items.id NOT IN ";
		$query .= "(SELECT item_id FROM assignments ";
		$query .= "WHERE username = ? AND project_id = items.project_id) ";
		$query .= "HAVING itemcount < ? ";
		$query .= "ORDER BY items.id ASC ";
		$query .= "LIMIT 1;";

		$results = $this->query($query, array($project_slug, $username, $num_proofs));

		return (count($results) > 0) ? $results[0]['id'] : -1;
	}

	// Returns: user row
	public function getUserStats($username) {
		$users = $this->query("SELECT score, (SELECT COUNT(*) FROM assignments WHERE username = ? AND date_completed IS NOT NULL) AS proofed, (SELECT COUNT(*) FROM assignments WHERE username = ? AND date_completed IS NOT NULL AND DATE_COMPLETED > DATE_SUB(NOW(), INTERVAL 7 DAY)) AS proofed_past_week FROM users WHERE username = ?", array($username, $username, $username));
		$user = $users[0];

		return $user;
	}

	// Returns: history row
	public function getUserHistory($username) {
		$query = "SELECT items.title AS item_title, projects.title AS project_title, assignments.date_completed AS date_comp, ";
		$query .= "DATE_FORMAT(assignments.date_completed, '%e %b %Y') AS date_completed, ";
		$query .= "items.id as item_id, projects.slug as project_slug ";
		$query .= "FROM assignments JOIN items ON item_id = items.id ";
		$query .= "JOIN projects ON assignments.project_id = projects.id ";
		$query .= "WHERE username = ? ";
		$query .= "AND assignments.date_completed IS NOT null ";
		$query .= "ORDER BY assignments.date_completed DESC LIMIT 5;";

		$history = $this->query($query, array($username));

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

	// Returns: id, project_id, title, itemtext, status, type
	public function loadItem($item_id, $project_slug) {
		$query = "SELECT items.id as id, projects.id as project_id, projects.slug as project_slug, items.title as title, items.itemtext as itemtext, items.status as status, items.type as type, items.href as href FROM items ";
		$query .= "JOIN projects ON items.project_id = projects.id ";
		$query .= "WHERE items.id = ? ";
		$query .= "AND projects.slug = ?;";

		$results = $this->query($query, array($item_id, $project_slug));

		if (count($results) > 0) {
			$result = $results[0];
		} else {
			$result = false;
		}

		return $result;
	}	

	// Returns: transcript
	public function getUserTranscript($item_id, $project_id, $username) {
		$query = "SELECT itemtext FROM texts ";
		$query .= "WHERE item_id = ? ";
		$query .= "AND project_id = ? ";
		$query .= "AND user = ?;";

		$results = $this->query($query, array($item_id, $project_id, $username));

		return (count($results) > 0) ? trim($results[0]['itemtext']) : '';
	}

	// Returns: boolean
	public function saveExistingItem($item_id, $title, $project_id, $itemtext, $status, $type, $href) {
		$sql = "UPDATE items ";
		$sql .= "SET title = ?, project_id = ?, itemtext = ?, status = ?, type = ?, href = ? ";
		$sql .= "WHERE id = ?;";

		return $this->execute($sql, array($title, $project_id, $itemtext, $status, $type, $href, $item_id));
	}

	// Returns: boolean
	public function userHasTranscriptDraft($username, $item_id, $project_id) {
		$query = "SELECT itemtext FROM texts ";
		$query .= "WHERE item_id = ? ";
		$query .= "AND project_id = ? ";
		$query .= "AND user = ?;";

		$results = $this->query($query, array($item_id, $project_id, $username));

		if (count($results) > 0) {
			$result = true;
		} else {
			$result = false;
		}

		return $result;
	}

	// Returns: none
	public function updateItemTranscriptStatus($item_id, $project_id, $status, $transcript, $username) {
		$sql = "UPDATE texts SET itemtext = ?, ";
		$sql .= "date = NOW(), ";
		$sql .= "status = ? ";
		$sql .= "WHERE item_id = ? ";
		$sql .= "AND project_id = ? ";
		$sql .= "AND user = ?;";

		return $this->execute($sql, array($transcript, $status, $item_id, $project_id, $username));
	}

	// Returns: none
	public function addItemTranscript($item_id, $project_id, $status, $transcript, $username) {
		$sql = "INSERT INTO texts (item_id, project_id, user, date, itemtext, status) VALUES (?, ?, ?, NOW(), ?, ?);";

		return $this->execute($sql, array($item_id, $project_id, $username, $transcript, $status));

	}

	// Returns: none
	public function updateAssignmentReviewDate($item_id, $project_id, $username) {
		$sql = "UPDATE assignments ";
		$sql .= "SET date_reviewed = NOW() ";
		$sql .= "WHERE username = ? ";
		$sql .= "AND item_id = ? ";
		$sql .= "AND project_id = ?;";

		return $this->execute($sql, array($username, $item_id, $project_id));
	}

	// Returns: none
	public function updateUserScoreForItem($username, $item_id, $project_id, $score) {
		$sql = "UPDATE users, assignments SET score = score + ? ";
		$sql .= "WHERE users.username = ? ";
		$sql .= "AND item_id = ? ";
		$sql .= "AND project_id = ? ";
		$sql .= "AND date_completed IS NULL;";

		return $this->execute($sql, array($score, $username, $item_id, $project_id));
	}

	// Returns: none
	public function completeAssignment($username, $item_id, $project_id) {
		$sql = "UPDATE assignments SET date_completed = NOW() ";
		$sql .= "WHERE username = ? ";
		$sql .= "AND item_id = ? ";
		$sql .= "AND project_id = ?;";

		return $this->execute($sql, array($username, $item_id, $project_id));
	}

	// Returns: transcript_count
	public function getItemProofCount($item_id, $project_id) {
		$query = "SELECT COUNT(id) as proofcount FROM assignments ";
		$query .= "WHERE item_id = ? ";
		$query .= "AND project_id = ? ";
		$query .= "AND date_completed IS NOT NULL";

		$results = $this->query($query, array($item_id, $project_id));

		if (count($results) > 0) {
			return intval($results[0]['proofcount']);
		} else {
			return -1;
		}
	}

	// Returns: item_id
	public function getNextItem($item_id, $project_slug) {
		$results = $this->query("SELECT items.id FROM items JOIN projects ON items.project_id = projects.id WHERE items.id > ? AND projects.slug = ? LIMIT 1", array($item_id, $project_slug));

		if (count($results) > 0) {
			$next_item_id = trim($results[0]['id']);
		} else {
			$next_item_id = -1;
		}
	}

	// Returns: project array
	public function loadProject($project_slug) {
		$query = "SELECT *, ";
		$query .= "DATE_FORMAT(date_started, '%e %b %Y') AS datestarted, ";
		$query .= "DATE_FORMAT(date_completed, '%e %b %Y') AS datecompleted, ";
		$query .= "DATE_FORMAT(date_posted, '%e %b %Y') AS dateposted, ";
		$query .= "DATEDIFF(date_completed, date_started) AS days_spent ";
		$query .= "FROM projects WHERE slug = ?;";
		$results = $this->query($query, array($project_slug));
		$result = $results[0];

		if (isset($result)) {
			return $result;
		} else {
			return false;
		}
	}

	// Returns: none
	public function saveProject($project_id, $title, $author, $slug, $language, $description, $owner, $status, $guidelines, $deadline_days, $num_proofs, $thumbnails) {
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

		$this->execute($sql, array($title, $author, $slug, $language, $description, $owner, $status, $guidelines, $deadline_days, $num_proofs, $thumbnails, $project_id));
	}

	public function addProject($title, $author, $slug, $language, $description, $owner, $status, $guidelines, $deadline_days, $num_proofs, $thumbnails) {
		$sql = "INSERT INTO projects ";
		$sql .= "(title, author, slug, language, description, owner, status, guidelines, deadline_days, num_proofs, thumbnails, date_started) ";
		$sql .= "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW());";

		$this->execute($sql, array($title, $author, $slug, $language, $description, $owner, $status, $guidelines, $deadline_days, $num_proofs, $thumbnails));
	}

	public function loadProjectStatus($project_slug) {
		$query = "SELECT (SELECT COUNT(*) FROM items WHERE items.project_id = projects.id AND status != 'available') AS completed, (SELECT COUNT(*) FROM items WHERE items.project_id = projects.id) AS total, (SELECT COUNT(*) FROM items WHERE items.project_id = projects.id AND status != 'available') / (SELECT COUNT(*) FROM items WHERE items.project_id = projects.id) * 100 AS percentage, (SELECT COUNT(*) FROM assignments WHERE assignments.project_id = projects.id AND assignments.date_completed IS NOT NULL) / (projects.num_proofs * (SELECT COUNT(*) FROM items where items.project_id = projects.id)) * 100 AS proof_percentage, (SELECT COUNT(*) FROM assignments WHERE assignments.project_id = projects.id AND assignments.date_completed IS NOT NULL) AS proofed FROM projects WHERE slug = ?;";
		$results = $this->query($query, array($project_slug));

		if (count($results) > 0) {
			return $results[0];
		} else {
			return false;
		}
	}

	// Returns: ?
	public function addPage($projectId, $pageTitle, $href) {
		$sql = "INSERT INTO items (project_id, title, itemtext, status, type, href) VALUES (?, ?, NULL, 'available', 'image', ?); ";
		return $this->execute($sql, array($projectId, $pageTitle, $href));
	}

	// Returns: ?
	public function saveItemText($itemId, $itemText) {
		$sql = "UPDATE items SET itemtext = ? WHERE id = ?; ";
		return $this->execute($sql, array($itemText, $itemId));
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
	public function getItems($projectId) {
		$results = $this->query("SELECT title, status, type, href, (SELECT COUNT(*) FROM assignments WHERE assignments.item_id = items.id) AS assignments, (SELECT COUNT(*) FROM assignments WHERE assignments.item_id = items.id AND date_completed IS NOT NULL) AS completed FROM items WHERE project_id = ? ORDER BY items.id ASC;", array($projectId));

		return $results;
	}

	// Returns: array of proofers
	public function getProoferStats($projectId) {
		$query = "SELECT username, ";
		$query .= "COUNT(username) AS pages, ";
		$query .= "COUNT(username) / ((SELECT COUNT(*) FROM items WHERE items.project_id = assignments.project_id) * projects.num_proofs) * 100 AS percentage ";
		$query .= "FROM assignments ";
		$query .= "JOIN projects ON assignments.project_id = projects.id ";
		$query .= "WHERE project_id = ? ";
		$query .= "GROUP BY username ORDER BY pages DESC;";

		return $this->query($query, array($projectId));
	}

	// Returns: array of items
	public function getBasicItems($projectId) {
		return $this->query("SELECT id, title, status FROM items WHERE project_id = ? ORDER BY items.id ASC;", array($projectId));
	}

	// Returns: array of assignments
	public function getBasicAssignments($projectId) {
		return $this->query("SELECT item_id, username, date_completed FROM assignments WHERE project_id = ? ORDER BY item_id ASC;", array($projectId));
	}

	// Returns: array of projects
	public function getProjects() {
		return $this->query("SELECT projects.title AS title, projects.author AS author, projects.slug AS slug, projects.num_proofs AS num_proofs, (SELECT COUNT(*) FROM items WHERE items.project_id = projects.id AND status != 'available' AND status != 'assigned') AS completed, (SELECT COUNT(*) FROM items WHERE items.project_id = projects.id) AS total, (SELECT COUNT(*) FROM items WHERE items.project_id = projects.id AND status != 'available' AND status != 'assigned') / (SELECT COUNT(*) FROM items WHERE items.project_id = projects.id) * 100 AS percentage, (SELECT COUNT(*) FROM assignments WHERE assignments.project_id = projects.id AND assignments.date_completed IS NOT NULL) / (projects.num_proofs * (SELECT COUNT(*) FROM items where items.project_id = projects.id)) * 100 AS proof_percentage, (SELECT count(items.id) FROM items LEFT JOIN assignments ON assignments.item_id = items.id WHERE items.status = 'available' AND assignments.date_assigned IS NULL AND items.project_id = projects.id) AS available_pages FROM projects WHERE projects.status = 'active' ORDER BY percentage DESC", array());
	}

	// Returns: array of projects
	public function getCompletedProjects() {
		$query = "SELECT projects.title, projects.author, projects.slug, ";
		$query .= "(SELECT date_assigned FROM assignments WHERE project_id=projects.id ORDER BY date_assigned limit 1) AS date_started, ";
		$query .= "(SELECT date_completed FROM assignments WHERE project_id=projects.id ORDER BY date_completed DESC limit 1) AS date_comp, ";
		$query .= "DATE_FORMAT((SELECT date_completed FROM assignments WHERE project_id=projects.id ORDER BY date_completed DESC limit 1), '%e %b %Y') AS date_completed ";
		$query .= "FROM projects ";
		$query .= "WHERE projects.status = 'completed' OR projects.status = 'posted' ";
		$query .= "ORDER BY date_comp DESC";

		return $this->query($query, array());
	}

	// Returns: array of users (username, score)
	public function getTopUsers() {
		return $this->query("SELECT username, score FROM users WHERE score > 0 ORDER BY score DESC LIMIT 10;", array());
	}

	// Returns: array of users (username, points_lost)
	public function getUserPointsLost() {
		return $this->query("SELECT username, count(username) AS points_lost FROM assignments WHERE date_completed IS NULL AND deadline < NOW() GROUP BY username", array());
	}

	// Returns: ?
	public function setUserScore($username, $score) {
		$sql = "UPDATE users SET score = score - ? WHERE username = ?;";
		return $this->execute($sql, array($username, $score));
	}

	// Returns: email, item_id, item title, project slug, deadline
	public function getLateUserEmails() {
		return $this->query("SELECT users.email, item_id, items.title, projects.slug, DATE_FORMAT(deadline, '%e %b %Y') AS deadline FROM assignments JOIN users on assignments.username = users.username JOIN items ON item_id = items.id JOIN projects ON projects.id = assignments.project_id WHERE assignments.date_completed IS NULL AND DATEDIFF(deadline, NOW()) = 1;", array());
	}

	// Returns: username, item_id, project_id, date_assigned, deadline
	public function getCurrentAssignments() {
		return $this->query("SELECT username, item_id, project_id, date_assigned, deadline FROM assignments WHERE date_completed IS NULL ORDER BY project_id, date_assigned DESC", array());
	}
}
