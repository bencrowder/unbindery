<?php

// Auth implementation for Alibaba authentication library
// Written by Ben Crowder

require_once 'AuthInterface.php';

class AuthAlibaba implements AuthInterface {
	private $username;

	public function init() {
		$params = array();

		$dbsettings = Settings::getProtected('database');
		$params['database_host'] = $dbsettings['host'];
		$params['database_name'] = $dbsettings['database'];
		$params['database_username'] = $dbsettings['username'];
		$params['database_password'] = $dbsettings['password'];

		$alibaba = Settings::getProtected('alibaba');
		$params['app_name'] = $alibaba['app_name'];
		$params['user_table_name'] = $alibaba['users_table'];
		$params['username_field'] = $alibaba['username_field'];
		$params['password_field'] = $alibaba['password_field'];
		$params['cookie_expiration'] = $alibaba['cookie_expiration'];
		$params['hash_function'] = $alibaba['hash_function'];
		$params['login_page_url'] = $alibaba['login_page_url'];

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
