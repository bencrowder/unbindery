<?php

class SystemPageController {

	// --------------------------------------------------
	// Index handler
	// URL: /
	// Methods: GET = get index or confirmation page

	static public function index($params) {
		$app_url = Settings::getProtected('app_url');
		$auth = Settings::getProtected('auth');
		$externalLogin = Settings::getProtected('external_login');
		$allowSignup = Settings::getProtected('allow_signup');
		$db = Settings::getProtected('db');

		if (!$db->installed()) {
			header("Location: $app_url/install");
		}

		if ($auth->authenticated() && ($auth->getUsername() != '')) {
			$username = $auth->getUsername();

			// Check to see if they have an account
			if ($auth->hasAccount($username)) {
				header("Location: $app_url/users/$username/dashboard");
			} else {
				// Create account and email confirmation link to user
				$user = new User($username);
				$auth->createAccount($user);

				// Redirect back to index with message
				$options = array(
					'page_title' => 'Confirmation',
					'user' => array(
						'loggedin' => false
						),
				);

				Template::render('confirmation', $options);
			}
		} else {
			if ($externalLogin) {
				$auth->redirectToLogin();
			} else {
				$options = array(
					'page_title' => 'Login',
					'user' => array(
						'loggedin' => false
						),
					'allow_signup' => $allowSignup,
					'includes' => "<script src='$app_url/js/index.js' type='text/javascript'></script>\n"
				);

				Template::render('index', $options);
			}
		}
	}


	// --------------------------------------------------
	// Login handler
	// URL: /login
	// Methods: GET = get login page

	static public function login($params) {
		$app_url = Settings::getProtected('app_url');
		$auth = Settings::getProtected('auth');

		$username = Utils::POST("username");
		$password = Utils::POST("password");

		if ($auth->login($username, $password)) {
			$user = new User($username);
			$user->updateLogin();						// updates last_login time in database

			header("Location: $app_url/users/$username/dashboard");
		} else {
			$auth->redirectToLogin("Login failed");
		}
	}


	// --------------------------------------------------
	// Logout handler
	// URL: /logout
	// Methods: GET = logout

	static public function logout($params) {
		$app_url = Settings::getProtected('app_url');
		$auth = Settings::getProtected('auth');

		$auth->logout($app_url);
	}


	// --------------------------------------------------
	// Signup handler
	// URL: /signup
	// Methods: POST = create new signup

	static public function signup($params) {
		$app_url = Settings::getProtected('app_url');
		$auth = Settings::getProtected('auth');

		$email = Utils::POST("email_signup");
		$username = Utils::POST("username_signup");
		$password = Utils::POST("password_signup");

		// Add user to database with "pending" as status
		$user = new User();
		$user->username = $username;
		$user->password = md5($password);
		$user->email = $email;
		$user->save();

		// Now go back to the home page to create the account
		if ($auth->login($username, $password)) {
			header("Location: $app_url");
		} else {
			error_log("Login didn't work.");
		}
	}


	// --------------------------------------------------
	// Activate signup handler
	// URL: /signup/activate
	// Methods: POST = activate new signup

	static public function activate($params) {
		$app_url = Settings::getProtected('app_url');
		$i18n = new I18n("../translations", Settings::getProtected('language'));

		$hash = $params['args'][0];

		$user = new User();
		$status = $user->validateHash($hash);

		if ($status) {
			$_SESSION['ub_message'] = $i18n->t("signup.activated");
		} else {
			$_SESSION['ub_message'] = $i18n->t("signup.invalid_code");
		}

		header("Location: $app_url");
	}


	// --------------------------------------------------
	// Message handler
	// URL: /messages
	// Methods: POST

	static public function message($args) {
		$message = Utils::POST('message');
		$error = Utils::POST('error');

		if ($message) $_SESSION['ub_message'] = $message;
		if ($error) $_SESSION['ub_error'] = $error;
	}


	// --------------------------------------------------
	// Install handler
	// URL: /install
	// Methods: GET = show install page
    //          POST = run install script

	static public function install($params) {
		// Load database
		$db = Settings::getProtected('db');		
		$installed = $db->installed();

		// Make sure we haven't already installed
		if ($installed) {
			// Already installed
			Utils::redirectToDashboard('error.already_installed', '');
		} else {
			// We haven't, so install
			switch ($params['method']) {
				// GET: Show install form
				case 'GET':
					Template::render('install', array('external_login' => Settings::getProtected('external_login')));
					break;

				// POST: Run install script
				case 'POST':
					// And install
					if ($db->install()) {
						// Sleep two seconds to make sure the tables are all created
						sleep(2);

						// Add admin user
						$user = new User();
						$user->username = Utils::POST('username');
						if (Utils::POST('password')) {
							$user->password = md5(Utils::POST('password')); // TODO: make this better
						} else {
							$user->password = '';
						}
						$user->role = "admin";
						$user->status = "active";
						$user->hash = "adminadminadmin";	// doesn't really matter since we don't need to confirm
						$user->in_db = false;
						$user->save();

						// Redirect to admin login page
						$auth = Settings::getProtected('auth');
						$auth->redirectToLogin();
					} else {
						Template::render('install', array('status' => 'failed'));
					}	

					break;
			}
		}	
	}


	// --------------------------------------------------
	// 404 handler

	static public function fileNotFound() {
		Template::render('404', array());
	}
}
