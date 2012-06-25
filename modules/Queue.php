<?php

class Queue {
	private $queue;
	private $queueName;
	private $saveOnChanges;
	static protected $functions = array('compare' => 'Queue::defaultItemCompareFunction');


	// Constructor: loads the specified queue
	//   $saveOnChanges: specifies whether the queue should be saved to the
	//     data source after each change or not (default = false)
	// --------------------------------------------------

	public function __construct($queueName = '', $saveOnChanges = false) {
		$this->saveOnChanges = $saveOnChanges;
		$this->queue = array();
		$this->queueName = trim($queueName);
		$this->load($this->queueName);
	}


	// exists: determines if a function to be registered actually exists
	// --------------------------------------------------

	static private function exists($function) {
		$rtn = false;

		if (is_array($function) && count($function) > 1 && method_exists($function[0], $function[1])) {
			$rtn = true;
		} elseif (is_string($function) && trim($function) != '' && function_exists($function)) {
			$rtn = true;
		}

		return $rtn;
	}


	// save: delegates to the save function specified
	// --------------------------------------------------

	public function save() {
		$rtn = false;

		if (!array_key_exists('save', self::$functions)) {
			return $rtn;
		}

		if ($this->queueName != '' && self::$functions['save'] != null) {
			$this->queue = call_user_func(self::$functions['save'], $this->queueName, $this->queue);
			$rtn = true;
		}

		return $rtn;
	}


	// load: delegates to the load function specified
	//   $saveOnChanges: specifies whether the queue should be saved to the
	//     data source after each change or not (default = false)
	// --------------------------------------------------

	public function load($queueName, $saveOnChanges = false) {
		$rtn = false;

		if (!array_key_exists('load', self::$functions)) {
			return $rtn;
		}

		if ($queueName != '' && self::$functions['load'] != null) {
			$this->queue = call_user_func(self::$functions['load'], $queueName);
			$this->saveOnChanges = $saveOnChanges;
			$rtn = true;
		}

		return $rtn;
	}


	// register: registers a function
	// --------------------------------------------------

	static public function register($action, $function) {
		$rtn = false;

		if (!self::exists($function)) {
			throw new Exception('Function does not exist.');
		} else {
			self::$functions[$action] = $function;
			$rtn = true;
		}

		return $rtn;
	}


	// add: adds an item to the queue
	// --------------------------------------------------

	public function add($item) {
		array_push($this->queue, $item);

		if ($this->saveOnChanges == true) {
			$this->save();
		}
	}


	// remove: finds the specified item and removes it from the queue
	//   Uses the itemCompareFunction registered
	// --------------------------------------------------

	public function remove($item) {
		$rtnItem = null;

		foreach ($this->queue as $key=>$currentItem) {
			if (call_user_func(self::$functions['compare'], $item, $currentItem)) {
				$rtnItem = $currentItem;
				unset($this->queue[$key]);
				array_filter($this->queue);
				break;
			}
		}

		if ($this->saveOnChanges) {
			$this->save();
		}

		return $rtnItem;
	}


	// getFirstItem: returns the first item in the queue and removes it from the queue
	// --------------------------------------------------

	public function getFirstItem() {
		$item = array_shift($this->queue);

		if ($this->saveOnChanges) $this->save();

		return $item;
	}


	// getItems: returns the queue array
	// --------------------------------------------------

	public function getItems() {
		return $this->queue;
	}


	// setItems: sets the queue to the $queue array specified
	// --------------------------------------------------

	public function setItems($queue) {
		$this->queue = $queue;

		if ($this->saveOnChanges) $this->save();
	}


	// destroy: deletes a queue
	// --------------------------------------------------

	public function destroy() {
		$rtn = false;

		if (!array_key_exists('destroy', self::$functions)) {
			return $rtn;
		}

		if ($this->queueName != '' && self::$functions['destroy'] != null) {
			$this->queue = call_user_func(self::$functions['destroy'], $this->queueName);
			$rtn = true;
		}

		if ($rtn == true) {
			unset($this->queue);
			$this->queueName = '';
		}

		return $rtn;
	}


	// Default item compare function
	// --------------------------------------------------

	public static function defaultItemCompareFunction($item1, $item2) {
		if ($item1 === $item2) return true;
		else return false;
	}
}
?>
