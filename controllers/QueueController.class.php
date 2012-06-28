<?php

class QueueController {

	// --------------------------------------------------
	// Save queue handler

	static public function save($name, $queueItems) {
		$db = Settings::getProtected('db');
		$auth = Settings::getProtected('auth');

		$auth->forceAuthentication();
		$username = $auth->getUsername();

		// First load the queue so we can tell what to add and replace
		$dbList = array();
		$results = $db->loadQueue($name);

		// Create a list combining the item and project IDs, since we need both
		foreach ($results as $result) {
			$dbList[] = $result['item_id'] . '|' . $result['project_id'];
		}

		// Go through our queue
		$queueList = array();
		foreach ($queueItems as $item) {
			$key = $item->item_id . '|' . $item->project_id;
			$queueList[] = $key;

			// If it's not in the database, we need to add it
			if (!in_array($key, $dbList)) {
				$db->saveToQueue($name, $item->item_id, $item->project_id);
			}
		}

		// Now go through and remove everything that's left over
		$removeList = array();
		foreach ($dbList as $key) {
			if (!in_array($key, $queueList)) {
				$exploded = explode("|", $key);
				$itemId = $exploded[0];
				$projectId = $exploded[1];

				$removeList[] = array("item_id" => $itemId, "project_id" => $projectId);
			}
		}

		// And now remove the rest from the database, since they're no longer in the queue
		$db->removeFromQueue($name, $removeList);
	}


	// --------------------------------------------------
	// Load queue handler

	static public function load($name, $options = array()) {
		$db = Settings::getProtected('db');
		$auth = Settings::getProtected('auth');

		$auth->forceAuthentication();
		$username = $auth->getUsername();

		if (array_key_exists('include-removed', $options) && $options['include-removed'] == true) {
			$includeRemoved = true;
		} else {
			$includeRemoved = false;
		}

		$items = array();
		$results = $db->loadQueue($name, $includeRemoved);

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


	// --------------------------------------------------
	// Compare handler

	static public function compare($item1, $item2) {
		if (($item1->item_id == $item2->item_id) &&
			($item1->project_id == $item2->project_id) &&
			($item1->project_owner == $item2->project_owner)) {
			return true;
		} else {
			return false;
		}
	}
}

Queue::register('save', array('QueueController', 'save'));
Queue::register('load', array('QueueController', 'load'));
Queue::register('compare', array('QueueController', 'compare'));

?>
