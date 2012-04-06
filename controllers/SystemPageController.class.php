<?php

class SystemPageController {
	static public function indexHandler($args) {
		$app_url = Settings::getProtected('app_url');
		$auth = Settings::getProtected('auth');
		$externalLogin = Settings::getProtected('external_login');
		$allowSignup = Settings::getProtected('allow_signup');

		if ($auth->authenticated()) {
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

	static public function loginHandler($args) {
		$app_url = Settings::getProtected('app_url');
		$db = Settings::getProtected('db');
		$auth = Settings::getProtected('auth');

		$username = $_POST["username"];
		$password = $_POST["password"];

		if ($auth->login($username, $password)) {
			$user = new User($username);
			$user->updateLogin();						// updates last_login time in database

			header("Location: $app_url/users/$username/dashboard");
		} else {
			$auth->redirectToLogin("Login failed");
		}
	}

	static public function logoutHandler($args) {
		$app_url = Settings::getProtected('app_url');
		$auth = Settings::getProtected('auth');

		$auth->logout($app_url);
	}

	/* POST */
	static public function signupHandler($args) {
		$app_url = Settings::getProtected('app_url');
		$db = Settings::getProtected('db');
		$auth = Settings::getProtected('auth');

		$email = $_POST["email_signup"];
		$username = $_POST["username_signup"];
		$password = $_POST["password_signup"];

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

		// return "done" (so Ajax can replace the div)
		//echo json_encode(array("statuscode" => "done", "username" => $user->username));
		break;
	}

	/* POST */
	static public function activateHandler($args) {
		$app_url = Settings::getProtected('app_url');
		$db = Settings::getProtected('db');
		$i18n = new I18n(Settings::getProtected('language'));

		$hash = $args[0];

		$user = new User();
		$status = $user->validateHash($hash);

		if ($status) {
			$_SESSION['ub_message'] = $i18n->t("signup.activated");
		} else {
			$_SESSION['ub_message'] = $i18n->t("signup.invalid_code");
		}

		header("Location: $app_url");
	}

	static public function testPageHandler($args) {
		$app_url = Settings::getProtected('app_url');
		$auth = Settings::getProtected('auth');

		if ($auth->authenticated()) {
			$username = $auth->getUsername();

			$user = new User($username);

			// Redirect back to index with message
			$options = array(
				'user' => array(
					'loggedin' => false
					),
			);

			Template::render($args[0], $options);
		}
	}

	static public function fileNotFoundHandler() {
		echo "File not found.";
	}
}
