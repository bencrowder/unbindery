<?php

class AdminPageController {
	static public function adminHandler($args) {
		$app_url = Settings::getProtected('app_url');
		$db = Settings::getProtected('db');

		$auth = Settings::getProtected('auth');
		$auth->forceAuthentication();

		$username = $auth->getUsername();
		$user = new User($username);

		// Get the current user's role on the project and make sure they're owner or admin
		$roleManager = new Role();
		if ($roleManager->forceClearance(array('role' => 'user:creator', 'user' => $user))) {
			$options = array(
				'user' => array(
					'loggedin' => true,
					'admin' => $user->admin),
			);

			Template::render('admin_dashboard', $options);
		}
	}
}

?>
