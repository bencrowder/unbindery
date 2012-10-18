<?php

class EventManager {
	private $groups = array();


	// Register event
	// --------------------------------------------------

	public function register($name, $group, $function) {
		// If this is the first time adding an event to this group, create the arrays
		if (!array_key_exists($group, $this->groups)) {
			$this->groups[$group] = array();
			$this->groups[$group][$name] = array();
		} else {
			// Make sure the name array exists
			if (!array_key_exists($name, $this->groups[$group])) {
				$this->groups[$group][$name] = array();
			}
		}

		array_push($this->groups[$group][$name], $function);
	}


	// Remove event
	// --------------------------------------------------

	public function remove($name, $group, $function='') {
		// Make sure the event and group exist
		if (array_key_exists($group, $this->groups) && isset($this->groups[$group]) && array_key_exists($name, $this->groups[$group]) && isset($this->groups[$group][$name])) {
			if ($function == '') {
				// If no function has been passed in, remove all events with that name/group
				unset($this->groups[$group][$name]);
			} else {
				// Otherwise remove the specific event/group/function we've asked for
				foreach ($this->groups[$group][$name] as $event => $eventFunction) {
					if ($function == $eventFunction) {
						unset($this->groups[$group][$name][$event]);
					}
				}
			}
		}
	}


	// Trigger event
	// --------------------------------------------------

	public function trigger($name, $group, $params='') {
		// If $params is empty, create an empty array
		if ($params == '') {
			$params = array();
		}

		// Make sure the group and event exist
		if (array_key_exists($group, $this->groups) && isset($this->groups[$group]) && array_key_exists($name, $this->groups[$group]) && isset($this->groups[$group][$name])) {
			foreach ($this->groups[$group][$name] as $function) {
				call_user_func($function, $params);
			}
		}
	}


	// Get events
	// --------------------------------------------------

	public function getEventsByGroup($group, $name='') {
		if (array_key_exists($group, $this->groups)) {
			$groupArray = $this->groups[$group];

			if ($name != '' && array_key_exists($name, $$this->groups[$group]) && isset($this->groups[$group][$name])) {
				// If the name is there, return the function for that event
				$response = $this->groups[$group][$name];
			} else {
				// Otherwise just return the group array
				$response = $groupArray;
			}
		} else {
			// That group didn't exist, so return null
			$response = null;
		}

		return $response;
	}
}

?>
