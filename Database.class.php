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
}

$db = new Database($HOST, $USERNAME, $PASSWORD, $DATABASE);
