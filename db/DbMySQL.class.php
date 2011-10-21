<?php

require_once 'DbInterface.php';

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
		$users = $this->query("SELECT name, email, status, admin FROM users WHERE username = ?", array($username));
		if (count($users) > 0) {
			$user = $users[0];
		} else {
			$user = array();
		}
		return $user;
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
	public function updateUserSettings($username, $name, $email, $password) {
		$sql = "UPDATE users SET name = ?, email = ?, ";
		if ($password != '') {
			$sql .= "password = ?, ";
		}
		$sql .= "WHERE username = ?";

		return $this->execute($sql, array($name, $email, md5($password), $username));
	}
}
