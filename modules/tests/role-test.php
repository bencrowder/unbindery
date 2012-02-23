<?php


include '../Role.php';

// Dummy User class
class User {
	public $role;
	public $projectRoles = array(
		"project1" => "project:proofer",
		"project2" => "project:admin"
	);
}

// Roles array
$roleArray = array(
	'user:user' => 10,
	'user:creator' => 30,
	'user:admin' => 50,
	'user:siteadmin' => 100,

	// Arbitray division numerically
	'project:proofer' => 1000,
	'project:reviewer' => 2000,
	'project:admin' => 5000
);

// Dummy verification function
function myVerifyFunction($params) {
	$verified = "false";

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
		if (array_key_exists($user->role, $roles)) {
			$userValue = $roles[$user->role];
		}
	} else if ($roleParts[0] == "project") {
		// It's a project-level role
		if (array_key_exists($projectId, $user->projectRoles) && array_key_exists($user->projectRoles[$projectId], $roles)) {
			$userValue = $roles[$user->projectRoles[$projectId]];
		}
	}

	echo "Verifying ($userValue >= $targetValue)...";

	if ($userValue >= $targetValue) {
		$verified = "true";
	}

	echo "$verified.\n";
}

// Register
echo "Registering verify function...\n";
Role::register('verify', 'myVerifyFunction');

echo "Initializing roles...\n";
$roleManager = new Role();
Role::init($roleArray);

echo "Setting up user with user:user role.\n";
$user = new User();
$user->role = "user:user";

echo "Verifying against user:user.\n";
$roleManager->verify(array("role" => "user:user", "user" => $user));

echo "Verifying against user:admin.\n";
$roleManager->verify(array("role" => "user:admin", "user" => $user));

echo "Verifying project1 against project:proofer.\n";
$roleManager->verify(array("role" => "project:proofer", "user" => $user, "project" => "project1"));

echo "Verifying project1 against project:reviewer.\n";
$roleManager->verify(array("role" => "project:reviewer", "user" => $user, "project" => "project1"));

echo "Verifying project1 against project:admin.\n";
$roleManager->verify(array("role" => "project:admin", "user" => $user, "project" => "project1"));

echo "Verifying project2 against project:admin.\n";
$roleManager->verify(array("role" => "project:admin", "user" => $user, "project" => "project2"));

?>
