<?php

// Configuration
require_once 'include/Settings.class.php';
Settings::loadFromYAML();

// Utilities
require_once 'include/utils.php';

// External libraries
require_once 'lib/Alibaba.class.php';
require_once 'lib/Router.class.php';

// Modules
require_once 'modules/Event.php';
require_once 'modules/Dispatch.php';
require_once 'modules/I18n.php';

// App-specific logic
require_once 'classes/Database.class.php';
require_once 'classes/User.class.php';
require_once 'classes/Mail.class.php';
require_once 'classes/Project.class.php';
require_once 'classes/Server.class.php';
require_once 'classes/Item.class.php';

// Handlers
require_once 'classes/Handlers.class.php';
require_once 'classes/WebServiceHandlers.class.php';


// Initialize Twig
// --------------------------------------------------

require_once 'lib/Twig/Autoloader.php';
Twig_Autoloader::register();


// Initialize auth engine 
// --------------------------------------------------

$authEngine = Settings::getProtected('auth');
require_once "auth/Auth$authEngine.class.php";

// Load the appropriate auth engine class
$authClass = "Auth$authEngine";
$auth = new $authClass;
$auth->init();

Settings::setProtected('auth', $auth);


// Initialize database
// --------------------------------------------------

$dbengine = Settings::getProtected('db');
require_once "db/Db$dbengine.class.php";

// Load the appropriate database engine class
$dbClass = "Db$dbengine";
$db = new $dbClass;

$dbsettings = Settings::getProtected('database');
$db->create($dbsettings['host'], $dbsettings['username'], $dbsettings['password'], $dbsettings['database']);

// Save it to the settings manager
Settings::setProtected('db', $db);


// Parse the routes
// --------------------------------------------------

// TODO: replace Router
// First, we get the URL from PHP
$url = $_SERVER["REQUEST_URI"];

// Create the routes we want to use
$routes = array(
	'#^/?$#' => 'Handlers::indexHandler',
	'#^/login/process/?$#' => 'Handlers::loginHandler',
	'#^/signup/?$#' => 'Handlers::signupHandler',
	'#^/logout/?$#' => 'Handlers::logoutHandler',
	'#^/dashboard[/?]?(.*)/?$#' => 'Handlers::dashboardHandler',
	'#^/settings/save/?$#' => 'Handlers::saveSettingsHandler',
	'#^/settings/?$#' => 'Handlers::settingsHandler',
	'#^/projects/(.*)/join/?$#' => 'Handlers::joinProjectHandler',
	'#^/projects/(.*)/(guidelines)/?$#' => 'Handlers::projectHandler',
	'#^/projects/(.*)/?$#' => 'Handlers::projectHandler',
	'#^/projects/?$#' => 'Handlers::projectsHandler',
	'#^/admin/projects/(.*)?$#' => 'Handlers::adminProjectHandler',
	'#^/admin/new_project/?$#' => 'Handlers::adminProjectHandler',
	'#^/admin/save_project/?$#' => 'Handlers::adminSaveProjectHandler',
	'#^/admin/upload/(.*)/?$#' => 'Handlers::adminUploadHandler',
	'#^/admin/upload_backend/?$#' => 'Handlers::adminUploadBackendHandler',
	'#^/admin/save_page/?$#' => 'Handlers::adminSavePageHandler',
	'#^/admin/new_page/(.*)/(.*)/?$#' => 'Handlers::adminNewPageHandler',
	'#^/admin/edit/(.*)/(.*)/?$#' => 'Handlers::adminEditPageHandler',
	'#^/admin/review/(.*)/(.*)/(.*)/?$#' => 'Handlers::adminReviewPageHandler',
	'#^/admin/?$#' => 'Handlers::adminHandler',
	'#^/edit/(.*)/(.*)/?$#' => 'Handlers::editPageHandler',
	'#^/save_page/?$#' => 'Handlers::savePageHandler',
	'#^/activate/(.*)/?$#' => 'Handlers::activateHandler',

	// Web services
	'#^/ws/save_item_transcript/?$#' => 'WebServiceHandlers::saveItemTranscriptHandler',
	'#^/ws/get_new_page/?$#' => 'WebServiceHandlers::getNewPageHandler'
);

Router::route($url, $routes, 'Handlers::fileNotFoundHandler');

?>
