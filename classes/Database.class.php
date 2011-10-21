<?php

class Database {
	private $host;
	private $username;
	private $password;
	private $database;
	private $siteroot;

	private $conn;

	public function Database($host, $username, $password, $database) {
		$this->host = $host;
		$this->username = $username;
		$this->password = $password;
		$this->database = $database;
	}

	public function connect() {
		$this->conn = mysql_connect($this->host, $this->username, $this->password);

		if (!$this->conn) {
			echo "Error connecting to database.\n";
		}

		@mysql_select_db($this->database, $this->conn) or die("Unable to select database.");
	}

	public function close() {
		mysql_close($this->conn);
	}

	public function connect2() {
		$this->db = new MySQLi($this->host, $this->username, $this->password, $this->database);
	}

	public function close2() {
		$this->db->close();
	}

	public function query($query) {
		$this->connect();

		$result = mysql_query($query) or die ("Couldn't run: $query");

		$results = array();

		while ($row = mysql_fetch_assoc($result)) {
			array_push($results, $row);
		}

		$this->close();
		return $results;
	}

	public function query2($query) {
		$results = array();
		$args = array();
		$bind_params = array();
		$types = '';

		$this->connect2();

		$numargs = func_num_args();

		for ($i=1; $i<$numargs; $i++) {
			$arg = func_get_arg($i);

			if (is_numeric($arg)) {
				$types .= 'i';
			} else {
				$types .= 's';
			}

			$args[$i] = $arg;
			unset($arg);
		}

		// We can't pass $args in to call_user_func_array, so we need to make a copy
		$bind_params[0] = &$types;
		foreach ($args as $key => $value) {
			$bind_params[$key] = &$args[$key];
		}

		// Prepare the statement
		if ($stmt = $this->db->prepare($query)) {
			echo "PC: " . $stmt->param_count;
			print_r($bind_params);
			// Execute $stmt->bind_param() with our parameters
			call_user_func_array(array($stmt, "bind_param"), $bind_params);

			$stmt->execute();

			// Now we want to get the results and put them in an associative array
			$meta = $stmt->result_metadata();
			while ($field = $meta->fetch_field()) {
				$params[] = &$row[$field->name];
			}
			call_user_func_array(array($stmt, 'bind_result'), $params);

			while ($stmt->fetch()) {
				foreach ($row as $key=>$val) {
					$c[$key] = $val;
				}
				$results[] = $c;
			}
			
			$stmt->close();
		}

		$this->close2();
		return $results;
	}

	function refValues($arr) { 
	} 
}

$db = new Database($HOST, $USERNAME, $PASSWORD, $DATABASE);
