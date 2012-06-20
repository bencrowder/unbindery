<?php

class QueueController {

	// --------------------------------------------------
	// Save queue handler

	static public function saveQueue($name, $array) {
		// Check to see if the database exists
		// If it doesn't, create it
		// If it does, update it

		// Each item in the queue is an Item class instance
		$db = Settings::getProtected('db');
		$auth = Settings::getProtected('auth');

		$auth->forceAuthentication();

		$username = $auth->getUsername();
	}


	// --------------------------------------------------
	// Load queue handler

	static public function loadQueue($name) {
		$db = Settings::getProtected('db');
		$auth = Settings::getProtected('auth');

		$auth->forceAuthentication();
		$username = $auth->getUsername();

		$items = array();
		$results = $db->loadQueue($name);

		foreach ($results as $result) {
			$itemID = $result['item_id'];
			$projectID = $result['project_id'];
		
			$item = new Item($db);
			$item->loadWithProjectID($itemID, $projectID, $username);

			array_push($items, $item);
		}

		return $items;
	}


	// --------------------------------------------------
	// Destroy queue handler

	static public function destroyQueue($name) {

	}
}

Queue::register('save', array('QueueController', 'saveQueue'));
Queue::register('load', array('QueueController', 'loadQueue'));
