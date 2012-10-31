<?php
// Class: DbInterface

interface DbInterface {
	// Configuration
	public function create($host, $username, $password, $database);

	// Connects to the database
	public function connect();

	// Closes the database connection
	public function close();

	// Queries the database and returns an associative array
	// Pass in the query and then the parameters for prepared statements (if any)
	public function query($query, $params = array());

	// Executes a statement on the database
	// Pass in the query and then the parameters for prepared statements (if any)
	public function execute($statement);


	/* Data retrieval functions */
	/* -------------------------------------------------- */

	// Loads a user
	// Returns: name, email, status, admin
	public function loadUser($username);
}
