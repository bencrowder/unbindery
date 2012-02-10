<?php

// Auth implementation for Alibaba authentication library
// Written by Ben Crowder

require_once 'AuthInterface.php';

class AuthAlibaba implements AuthInterface {
	private $username;

	public function login($username, $password) {
		$status = Alibaba::login($username, $password);

		if ($status) {
			$this->username = $username;
		}
	}

	public function logout($redirect = '') {
		Alibaba::logout($redirect);
	}

	public function authenticated() {
		return Alibaba::authenticated();
	}

	public function forceAuthentication() {
		return Alibaba::forceAuthentication();
	}

	public function redirectToLogin() {
		return Alibaba::redirectToLogin();
	}

	public function getUsername() {
		if ($this->username) {
			return $this->username;
		} else {
			return Alibaba::getUsername();
		}
	}
}
