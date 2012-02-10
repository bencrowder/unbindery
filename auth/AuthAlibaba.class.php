<?php

// Auth implementation for Alibaba authentication library
// Written by Ben Crowder

require_once 'AuthInterface.php';

class AuthAlibaba implements AuthInterface {
	private $username;

	public function init() {
		$params = array();
		$params['app_name'] = Settings::getProtected('app_name');
		$params['database_host'] = Settings::getProtected('db_host');
		$params['database_name'] = Settings::getProtected('db_database');
		$params['database_username'] = Settings::getProtected('db_username');
		$params['database_password'] = Settings::getProtected('db_password');
		$params['user_table_name'] = Settings::getProtected('user_table_name');
		$params['username_field'] = Settings::getProtected('username_field');
		$params['password_field'] = Settings::getProtected('password_field');
		$params['cookie_expiration'] = Settings::getProtected('cookie_expiration');
		$params['hash_function'] = Settings::getProtected('hash_function');
		$params['login_page_url'] = Settings::getProtected('login_page_url');

		Alibaba::AlibabaInit($params);
	}

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
