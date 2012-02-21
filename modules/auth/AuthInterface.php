<?php
// Class: AuthInterface

interface AuthInterface {
	// Initialization
	public function init();

	// Check to see if the user has an Unbindery account
	public function hasAccount($username);

	// Create an Unbindery account for the user
	public function createAccount($user);

	// Login (returns boolean)
	public function login($username, $password);

	// Logout (with optional URL to redirect to)
	public function logout($redirect = '');

	// Check to see if user is authenticated (returns boolean)
	public function authenticated();

	// Force authentication (if not authenticated, redirects to login)
	public function forceAuthentication();

	// Redirect to login page
	public function redirectToLogin();

	// Get username (returns string)
	public function getUsername();
}

?>
