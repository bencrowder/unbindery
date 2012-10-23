<?php

class AdminPageController {
	static public function adminHandler($args) {
		$app_url = Settings::getProtected('app_url');
		$db = Settings::getProtected('db');

		$user = User::getAuthenticatedUser();

		// Get the current user's role and make sure they're at least creator or admin
		$roleManager = new Role();
		$roleManager->forceClearance(array('role' => 'user:creator', 'user' => $user));
	
		$options = array(
			'user' => $user->getResponse(),
		);

		Template::render('admin_dashboard', $options);
	}
}

?>
