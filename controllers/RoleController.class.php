<?php

class RoleController {

	// Simple verify (returns boolean)
	// --------------------------------------------------
	// Pass in role, user, and optional projectId

	static public function verify($params) {
		$verified = false;

		$role = $params['role'];
		$user = $params['user'];
		$projectId = (array_key_exists('project', $params)) ? $params['project'] : '';

		// Get the list of roles (so we can compare)
		$roles = Role::getRoles();

		// Target role value
		$targetValue = $roles[$role];

		// Split on colon
		$roleParts = explode(':', $role);

		if ($roleParts[0] == "user") {
			// If it's a user-level role, get user role field and get value
			if (array_key_exists("user:" . $user->role, $roles)) {
				$userValue = $roles["user:" . $user->role]; // TODO: get real value
			}
		} else if ($roleParts[0] == "project") {
			// It's a project-level role
			if (array_key_exists($projectId, $user->projectRoles) && array_key_exists($user->projectRoles[$projectId], $roles)) {
				$userValue = $roles[$user->projectRoles[$projectId]]; // TODO: get real value
			}
		}

		if ($userValue >= $targetValue) $verified = true;

		return $verified;
	}


	// Force clearance (and redirect if not cleared)
	// --------------------------------------------------
	// Pass in role, user, and optional projectId

	static public function forceClearance($params) {
		$app_url = Settings::getProtected('app_url');
		$i18n = new I18n("translations", Settings::getProtected('language'));

		if (!self::verify($params)) {
			$_SESSION['ub_error'] = $i18n->t("role.insufficient");

			header("Location: $app_url/dashboard");

			return false;
		}

		return true;
	}
}

?>
