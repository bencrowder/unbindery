<?php

require_once 'include/config.php';
require_once 'include/utils.php';
require_once 'include/Alibaba.class.php';
require_once 'include/Router.class.php';
require_once 'lib/Twig/Autoloader.php';

Twig_Autoloader::register();

$dbengine = Settings::getProtected('dbengine');
require_once "db/Db$dbengine.class.php";

// Create the database object
$dbClass = "Db$dbengine";
$db = new $dbClass;
$db->create(Settings::getProtected('db_host'), Settings::getProtected('db_username'), Settings::getProtected('db_password'), Settings::getProtected('db_database'));
Settings::setProtected('db', $db);

require_once 'classes/Database.class.php';
require_once 'classes/User.class.php';
require_once 'classes/Mail.class.php';
require_once 'classes/Project.class.php';
require_once 'classes/Server.class.php';
require_once 'classes/Item.class.php';

require_once 'classes/Handlers.class.php';
require_once 'classes/WebServiceHandlers.class.php';


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
