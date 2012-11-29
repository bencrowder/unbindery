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
require_once '../lib/PHP-FineDiff/finediff.php';
require_once '../lib/Twig/Autoloader.php';
Twig_Autoloader::register();

// Modules
require_once '../modules/Dispatch.php';
require_once '../modules/EventManager.php';
require_once '../modules/I18n.php';
require_once '../modules/NotificationManager.php';
require_once '../modules/Queue.php';
require_once '../modules/Transcript.php';
require_once '../modules/Workflow.php';
require_once '../modules/uploaders/ItemTypeUploader.class.php';

// Module controllers
require_once '../controllers/DispatchController.class.php';
require_once '../controllers/NotificationController.class.php';
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


// Initialize dispatcher
// --------------------------------------------------

$dispatch = new Dispatch();
$dispatch->register(array('DispatchController', 'getNextAvailableItem'));
Settings::setProtected('dispatch', $dispatch);


// Initialize event manager
// --------------------------------------------------

$eventManager = new EventManager();
Settings::setProtected('eventManager', $eventManager);


// Initialize transcript controller
// --------------------------------------------------

Transcript::setEventManager($eventManager);
Transcript::register('load', array('TranscriptController', 'load'));
Transcript::register('save', array('TranscriptController', 'save'));
Transcript::register('diff', array('TranscriptController', 'diff'));


// Initialize workflow controller
// --------------------------------------------------

Workflow::register('callback', array('WorkflowController', 'parse'));


// Initialize notifications controller
// --------------------------------------------------

$notifications = Settings::getProtected('notifications');
$notificationsList = array();
foreach ($notifications as $key=>$value) {			// Get an array of just the keys
	array_push($notificationsList, $key);	
}
$notify = new NotificationManager();
$notify->setEventManager($eventManager);
$notify->registerNotifications($notificationsList, array('NotificationController', 'send'));
Settings::setProtected('notify', $notify);


// Parse the routes
// --------------------------------------------------

// The \.?([^/]*)?/? at the end allows us to add .json, etc. for other formats

// Create the routes we want to use
$routes = array(
	// System pages
	'#^/?$#'									=> 'SystemPageController::index',
	'#^/login/?$#'								=> 'SystemPageController::login',
	'#^/logout/?$#'								=> 'SystemPageController::logout',
	'#^/signup(\.[^/]+)?/?$#'					=> 'SystemPageController::signup',
	'#^/signup/activate/(.*)(\.[^/]+)?/?$#'		=> 'SystemPageController::activate',
	'#^/messages/?$#'							=> 'SystemPageController::message',
	'#^/install/?$#'							=> 'SystemPageController::install',
	'#^/test/(.*)/?$#'							=> 'SystemPageController::test',

	// User project item pages
	'#^/(users)/([^/]+)/projects/([^/]+)/items/get(\.[^/]+)?/?#'					=> 'ItemPageController::getNewItem',
	'#^/(users)/([^/]+)/projects/([^/]+)/items/([^/.]+)/transcript(\.[^/]+)?/?#'	=> 'ItemPageController::transcript',
	'#^/(users)/([^/]+)/projects/([^/]+)/items/([^/.]+)/delete(\.[^/]+)?/?#'		=> 'ItemPageController::deleteItem',
	'#^/(users)/([^/]+)/projects/([^/]+)/items/([^/.]+)/(proof|review)(\.[^/]+)?/?#'	=> 'ItemPageController::itemProof',
	'#^/(users)/([^/]+)/projects/([^/]+)/items/([^/.]+)/(proof|review|edit)(\.[^/]+)?/?#'		=> 'ItemPageController::itemProof',
	'#^/(users)/([^/]+)/projects/([^/]+)/items/([^/.]+)(\.[^/]+)?/?#'				=> 'ItemPageController::item',
	'#^/(users)/([^/]+)/projects/([^/]+)/items(\.[^/]+)?/?#'						=> 'ItemPageController::items',

	// User projects
	'#^/(users)/([^/]+)/projects/([^/]+)/transcript/split(\.[^/]+)?/?#'	=> 'ProjectPageController::splitTranscript',
	'#^/(users)/([^/]+)/projects/([^/]+)/transcript(\.[^/]+)?/?#'	=> 'ProjectPageController::transcript',
	'#^/(users)/([^/]+)/projects/([^/]+)/membership/leave(\.[^/]+)?/?#'	=> 'ProjectPageController::membershipLeave',
	'#^/(users)/([^/]+)/projects/([^/]+)/membership(\.[^/]+)?/?#'	=> 'ProjectPageController::membership',
	'#^/(users)/([^/]+)/projects/([^/]+)/admin(\.[^/]+)?/?#'		=> 'ProjectPageController::admin',
	'#^/(users)/([^/]+)/projects/([^/]+)/upload(\.[^/]+)?/?#'		=> 'ProjectPageController::upload',
	'#^/(users)/([^/]+)/projects/([^/]+)/import(\.[^/]+)?/?#'		=> 'ProjectPageController::import',
	'#^/(users)/([^/]+)/projects/new-project(\.[^/]+)?/?#'			=> 'ProjectPageController::newProject',
	'#^/(users)/([^/]+)/projects/([^/.]+)(\.[^/]+)?/?#'			=> 'ProjectPageController::projectPage',
	'#^/(users)/([^/]+)/projects(\.[^/]+)?/?#'						=> 'ProjectPageController::projects',

	// User pages
	'#^/users/([^/]+)/dashboard(\.[^/]+)?/?#'	=> 'UserPageController::userDashboard',
	'#^/users/([^/]+)/settings(\.[^/]+)?/?#'	=> 'UserPageController::userSettings',
	'#^/users/([^/.]+)(\.[^/]+)?/?#'			=> 'UserPageController::userPage',
	'#^/users(\.[^/]+)?/?#'					=> 'UserPageController::users',

	// Item pages
	'#^/projects/([^/]+)/items/get(\.[^/]+)?/?#'							=> 'ItemPageController::getNewItem',
	'#^/projects/([^/]+)/items/([^/.]+)/transcript(\.[^/]+)?/?#'			=> 'ItemPageController::transcript',
	'#^/projects/([^/]+)/items/([^/.]+)/delete(\.[^/]+)?/?#'				=> 'ItemPageController::deleteItem',
	'#^/projects/([^/]+)/items/([^/.]+)/(proof|review)/(\.[^/]+)/?#'		=> 'ItemPageController::itemProof',
	'#^/projects/([^/]+)/items/([^/.]+)/(proof|review|edit)(\.[^/]+)?/?#'	=> 'ItemPageController::itemProof',
	'#^/projects/([^/]+)/items/([^/.]+)(\.[^/]+)?/?#'						=> 'ItemPageController::item',
	'#^/projects/([^/]+)/items(\.[^/]+)?/?#'								=> 'ItemPageController::items',

	// Project pages
	'#^/projects/([^/]+)/transcript/split(\.[^/]+)?/?#'		=> 'ProjectPageController::splitTranscript',
	'#^/projects/([^/]+)/transcript(\.[^/]+)?/?#'			=> 'ProjectPageController::transcript',
	'#^/projects/([^/]+)/membership/leave(\.[^/]+)?/?#'		=> 'ProjectPageController::membershipLeave',
	'#^/projects/([^/]+)/membership(\.[^/]+)?/?#'			=> 'ProjectPageController::membership',
	'#^/projects/([^/]+)/admin(\.[^/]+)?/?#'				=> 'ProjectPageController::admin',
	'#^/projects/([^/]+)/upload(\.[^/]+)?/?#'				=> 'ProjectPageController::upload',
	'#^/projects/([^/]+)/import(\.[^/]+)?/?#'				=> 'ProjectPageController::import',
	'#^/projects/new-project(\.[^/]+)?/?#'					=> 'ProjectPageController::newProject',
	'#^/projects/([^/.]+)(\.[^/]+)?/?#'						=> 'ProjectPageController::projectPage',
	'#^/projects(\.[^/]+)?/?#'								=> 'ProjectPageController::projects',

	// Admin pages
	'#^/admin(\.[^/]+)?/?$#'								=> 'AdminPageController::admin',
);

$router = new Router('SystemPageController::fileNotFound');
$router->route($routes);

?>
