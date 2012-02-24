<?php

/* Unbindery */
/* ------------------------------------------------- */

// Helpers
require_once '../helpers/Mail.class.php';
require_once '../helpers/Media.class.php';
require_once '../helpers/Template.class.php';

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
require_once '../controllers/Handlers.class.php';
require_once '../controllers/WebServiceHandlers.class.php';
require_once '../controllers/SystemPageController.class.php';
require_once '../controllers/UserPageController.class.php';

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

// TODO: replace Router
// First, we get the URL from PHP
$url = $_SERVER["REQUEST_URI"];

// Create the routes we want to use
$routes = array(
	// System pages
	'#^/?$#'									=> 'SystemPageController::indexHandler',
	'#^/login/?$#'								=> 'SystemPageController::loginHandler',
	'#^/logout/?$#'								=> 'SystemPageController::logoutHandler',
	'#^/signup/?$#'								=> 'SystemPageController::signupHandler',
	'#^/signup/activate/(.*)/?$#'				=> 'SystemPageController::activateHandler',

	// User pages
	'#^/dashboard/?$#'							=> 'UserPageController::dashboardHandler',
	'#^/settings/save/?$#'						=> 'UserPageController::saveSettingsHandler',
	'#^/settings/?$#'							=> 'UserPageController::settingsHandler',

	// Project pages
	'#^/projects/(.*)/join/?$#'					=> 'ProjectPageController::joinProjectHandler',
	'#^/projects/(.*)/(guidelines)/?$#'			=> 'ProjectPageController::projectHandler',
	'#^/projects/(.*)/?$#'						=> 'ProjectPageController::projectHandler',
	'#^/projects/?$#'							=> 'ProjectPageController::projectsHandler',
	'#^/admin/projects/(.*)?$#'					=> 'ProjectPageController::adminProjectHandler',
	'#^/admin/new_project/?$#'					=> 'ProjectPageController::adminProjectHandler',
	'#^/admin/save_project/?$#'					=> 'ProjectPageController::adminSaveProjectHandler',

	// Item pages
	'#^/admin/upload/(.*)/?$#'					=> 'ItemPageController::adminUploadHandler',
	'#^/admin/upload_backend/?$#'				=> 'ItemPageController::adminUploadBackendHandler',
	'#^/admin/save_page/?$#'					=> 'ItemPageController::adminSavePageHandler',
	'#^/admin/new_page/(.*)/(.*)/?$#'			=> 'ItemPageController::adminNewPageHandler',
	'#^/admin/edit/(.*)/(.*)/?$#'				=> 'ItemPageController::adminEditPageHandler',
	'#^/admin/review/(.*)/(.*)/(.*)/?$#'		=> 'ItemPageController::adminReviewPageHandler',
	'#^/edit/(.*)/(.*)/?$#'						=> 'ItemPageController::editPageHandler',
	'#^/save_page/?$#'							=> 'ItemPageController::savePageHandler',

	// Admin pages
	'#^/admin/?$#'								=> 'AdminPageController::adminHandler',

	// Web services
	'#^/ws/save_item_transcript/?$#'			=> 'WebServiceHandlers::saveItemTranscriptHandler',
	'#^/ws/get_new_page/?$#'					=> 'WebServiceHandlers::getNewPageHandler'
);

Router::route($url, $routes, 'SystemPageController::fileNotFoundHandler');

?>
