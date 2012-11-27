<?php

class RoleController {
	// Simple verify (returns boolean)
	// --------------------------------------------------
	// Pass in roles array to check against, user, and optional project (in params array)

	static public function verify($roles, $user, $params = array()) {
		// If we don't have a user, we're not verified
		if (!$user) return false;

		$verified = false;

		// Get project
		$project = (array_key_exists('project', $params)) ? $params['project'] : false;

		// Load user roles for project
		if ($project) {
			$projectRoles = $user->getRolesForProject($project->slug);
		}

		foreach ($roles as $role) {
			// Split on colon
			$roleParts = explode('.', $role);

			switch ($roleParts[0]) {
				// system.user, system.creator, system.admin
				case 'system':
					if ($user->role == $roleParts[1]) {
						$verified = true;
					}

					break;

				// project.proofer, project.reviewer, project.admin, project.owner
				case 'project':
					if ($project != false) {
						if (in_array($roleParts[1], $projectRoles)) {
							$verified = true;
						}

						// Check owner separately
						if ($roleParts[1] == 'owner') {
							if ($project->owner == $user->username) {
								$verified = true;
							}
						}
					}

					break;
			}
		}

		return $verified;
	}


	// Force clearance (and redirect if not cleared)
	// --------------------------------------------------
	// Pass in roles array, user, and optional project

	static public function forceClearance($roles, $user, $params = array(), $error = 'error.insufficient_rights') {
		$app_url = Settings::getProtected('app_url');
		$i18n = new I18n("../translations", Settings::getProtected('language'));

		if (!self::verify($roles, $user, $params)) {
			Utils::redirectToDashboard('', $i18n->t($error));

			return false;
		}

		return true;
	}
}

?>
