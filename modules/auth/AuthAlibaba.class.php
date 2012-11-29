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
		$params['hash_function'] = $alibaba['hash_function'];
		$params['login_page_url'] = $alibaba['login_page_url'];

		Alibaba::AlibabaInit($params);
	}

	public function hasAccount($username) {
		$user = new User($username);
		return ($user->hash != '' && $user->status != 'pending');	// If there's a hash, they have an account
	}

	public function createAccount($user) {
		$app_url = Settings::getProtected('app_url');
		$email_subject = Settings::getProtected('email_subject');
		$admin_email = Settings::getProtected('admin_email');
		$i18n = new I18n("../translations", Settings::getProtected('language'));

		// Add username/email here if they're not already in Unbindery (unnecessary for Alibaba)
		// Example:
		// $user->username = $this->getUsername();
		// $user->email = $this->getEmail();

		// Generate hash
		$user->hash = md5($user->email . $user->username . time());

		// Add user to the database or update if they're already there
		$user->save();

		// Send confirmation link to user via email
		$message = $i18n->t('signup.confirmation_email', array("url" => "$app_url/signup/activate/{$user->hash}"));

		$status = Mail::sendMessage($user->email, "$email_subject " . $i18n->t('signup.confirmation_link'), $message);

		if ($status == 1) { 
			$status = "done";
		} else {
			$status = "error mailing";
		}

		$status = Mail::sendMessage($admin_email, "$email_subject " . $i18n->t('signup.new_signup'), $i18n->t('signup.new_user') . " {$user->username} <{$user->email}>");

	}

	public function login($username, $password) {
		$status = Alibaba::login($username, $password);

		if ($status) {
			$this->username = $username;
		}

		return $status;
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
