<?php

/* Unbindery */
/* ------------------------------------------------- */

// Helpers
require_once '../helpers/Mail.class.php';
require_once '../helpers/Media.class.php';
require_once '../helpers/Template.class.php';
require_once '../helpers/Utils.class.php';

// Configuration
require_once '../helpers/Settings.class.php';
Settings::loadFromYAML();

// External libraries
require_once '../lib/Alibaba.class.php';
require_once '../lib/Router.class.php';
require_once '../lib/Twig/Autoloader.php';
Twig_Autoloader::register();

// Modules
require_once '../modules/Dispatch.php';
require_once '../modules/Event.php';
require_once '../modules/I18n.php';
require_once '../modules/Queue.php';
require_once '../modules/Role.php';
require_once '../modules/Transcript.php';
require_once '../modules/Workflow.php';

// Module controllers
require_once '../controllers/QueueController.class.php';
require_once '../controllers/RoleController.class.php';
require_once '../controllers/TranscriptController.class.php';
require_once '../controllers/WorkflowController.class.php';

// Page handler controllers
require_once '../controllers/SystemPageController.class.php';
require_once '../controllers/UserPageController.class.php';
require_once '../controllers/ProjectPageController.class.php';
require_once '../controllers/AdminPageController.class.php';
require_once '../controllers/ItemPageController.class.php';
require_once '../controllers/WebServiceHandlers.class.php'; // TODO: remove

// Object models
require_once '../model/User.class.php';
require_once '../model/Project.class.php';
require_once '../model/Item.class.php';


// Initialize the session
// --------------------------------------------------

session_start();


// Initialize auth engine 
// --------------------------------------------------

$authEngine = Settings::getProtected('auth');
require_once "../modules/auth/Auth$authEngine.class.php";

// Load the appropriate auth engine class
$authClass = "Auth$authEngine";
$auth = new $authClass;
$auth->init();

Settings::setProtected('auth', $auth);


// Initialize database
// --------------------------------------------------

$dbengine = Settings::getProtected('db');
require_once "../modules/db/$dbengine/Db$dbengine.class.php";

// Load the appropriate database engine class
$dbClass = "Db$dbengine";
$db = new $dbClass;

$dbsettings = Settings::getProtected('database');
$db->create($dbsettings['host'], $dbsettings['username'], $dbsettings['password'], $dbsettings['database']);

// Save it to the settings manager
Settings::setProtected('db', $db);


// Initialize roles
// --------------------------------------------------

Role::register('verify', 'RoleController::verify');
Role::register('force_clearance', 'RoleController::forceClearance');
Role::init(Settings::getProtected('roles'));


// Parse the routes
// --------------------------------------------------

// The \.?([^/]*)?/? at the end allows us to add .json, etc. for other formats

// Create the routes we want to use
$routes = array(
	// System pages
	'#^/?$#'									=> 'SystemPageController::indexHandler',
	'#^/login/?$#'								=> 'SystemPageController::loginHandler',
	'#^/logout/?$#'								=> 'SystemPageController::logoutHandler',
	'#^/signup\.?([^/]+)?/?$#'					=> 'SystemPageController::signupHandler',
	'#^/signup/activate/(.*)/\.?([^/]+)?/?$#'	=> 'SystemPageController::activateHandler',
	'#^/test/(.*)/?$#'							=> 'SystemPageController::testPageHandler',

	// User project item pages
	'#^/(users)/([^/]+)/projects/([^/]+)/items/media\.?([^/]+)?/?#'					=> 'ItemPageController::media',
	'#^/(users)/([^/]+)/projects/([^/]+)/items/transcripts\.?([^/]+)?/?#'			=> 'ItemPageController::transcripts',
	'#^/(users)/([^/]+)/projects/([^/]+)/items/([^/.]+)/transcript\.?([^/]+)?/?#'	=> 'ItemPageController::transcript',
	'#^/(users)/([^/]+)/projects/([^/]+)/items/([^/.]+)/admin\.?([^/]+)?/?#'		=> 'ItemPageController::admin',
	'#^/(users)/([^/]+)/projects/([^/]+)/items/([^/.]+)/proof\.?([^/]+)?/?#'		=> 'ItemPageController::itemProof',
	'#^/(users)/([^/]+)/projects/([^/]+)/items/([^/.]+)/review\.?([^/]+)?/?#'		=> 'ItemPageController::itemReview',
	'#^/(users)/([^/]+)/projects/([^/]+)/items/([^/.]+)\.?([^/]+)?/?#'				=> 'ItemPageController::item',
	'#^/(users)/([^/]+)/projects/([^/]+)/items\.?([^/]+)?/?#'						=> 'ItemPageController::items',

	// User projects
	'#^/(users)/([^/]+)/projects/([^/]+)/membership\.?([^/]+)?/?#'	=> 'ProjectPageController::membership',
	'#^/(users)/([^/]+)/projects/([^/]+)/admin\.?([^/]+)?/?#'		=> 'ProjectPageController::admin',
	'#^/(users)/([^/]+)/projects/new-project\.?([^/]+)?/?#'			=> 'ProjectPageController::newProject',
	'#^/(users)/([^/]+)/projects/([^/.]+)\.?([^/]+)?/?#'			=> 'ProjectPageController::projectPage',
	'#^/(users)/([^/]+)/projects\.?([^/]+)?/?#'						=> 'ProjectPageController::projects',

	// User pages
	'#^/users/([^/]+)/dashboard\.?([^/]+)?/?#'	=> 'UserPageController::userDashboard',
	'#^/users/([^/]+)/settings\.?([^/]+)?/?#'	=> 'UserPageController::userSettings',
	'#^/users/([^/.]+)\.?([^/]+)?/?#'			=> 'UserPageController::userPage',
	'#^/users\.?([^/]+)?/?#'					=> 'UserPageController::users',

	// Item pages
	'#^/projects/([^/]+)/items/media\.?([^/]+)?/?#'					=> 'ItemPageController::media',
	'#^/projects/([^/]+)/items/transcripts\.?([^/]+)?/?#'			=> 'ItemPageController::transcripts',
	'#^/projects/([^/]+)/items/([^/.]+)/transcript\.?([^/]+)?/?#'	=> 'ItemPageController::transcript',
	'#^/projects/([^/]+)/items/([^/.]+)/admin\.?([^/]+)?/?#'		=> 'ItemPageController::admin',
	'#^/projects/([^/]+)/items/([^/.]+)/proof\.?([^/]+)?/?#'		=> 'ItemPageController::itemProof',
	'#^/projects/([^/]+)/items/([^/.]+)/review\.?([^/]+)?/?#'		=> 'ItemPageController::itemReview',
	'#^/projects/([^/]+)/items/([^/.]+)\.?([^/]+)?/?#'				=> 'ItemPageController::item',
	'#^/projects/([^/]+)/items\.?([^/]+)?/?#'						=> 'ItemPageController::items',

	// Project pages
	'#^/projects/([^/]+)/membership\.?([^/]+)?/?#'	=> 'ProjectPageController::membership',
	'#^/projects/([^/]+)/admin\.?([^/]+)?/?#'		=> 'ProjectPageController::admin',
	'#^/projects/new-project\.?([^/]+)?/?#'			=> 'ProjectPageController::newProject',
	'#^/projects/([^/.]+)\.?([^/]+)?/?#'			=> 'ProjectPageController::projectPage',
	'#^/projects\.?([^/]+)?/?#'						=> 'ProjectPageController::projects',

	// Admin pages
	'#^/admin\.?([^/]+)?/?$#'					=> 'AdminPageController::adminHandler',
);

$router = new Router('SystemPageController::fileNotFoundHandler');
$router->route($routes);

?>
