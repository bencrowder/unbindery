<?php

class User {
	private $db;

	private $username;
	private $name;
	private $email;

	public function User($db) {
		$this->db = $db;
	}

	public function __set($key, $val) {
		$this->$key = $val;
	}

	public function __get($key) {
		return $this->$key;
	}

	public function load($username) {
		$this->db->connect();
		$this->username = $username;

		$query = "SELECT name, email FROM users WHERE username = '" . mysql_real_escape_string($username) . "'";
		$result = mysql_query($query) or die ("Couldn't run: $query");

		if (mysql_numrows($result)) {
			$this->name = trim(mysql_result($result, 0, "name"));
			$this->email = trim(mysql_result($result, 0, "email"));
		}

		$this->db->close();
	}
}
