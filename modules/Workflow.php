<?php

class Workflow extends Queue {
	private $currentIndex;
	private $workflow;

	// Constructor: loads the specified queue
	//   $saveOnChanges: specifies whether the queue should be saved to the
	//     data source after each change or not (default = false)
	// --------------------------------------------------

	public function __construct($workflow, $index = 0) {
		parent::__construct();
		$this->workflow = explode(',', $workflow);
		$this->setIndex($index);
	}

	public function setIndex($input) {
		$this->currentIndex = $input;
		$this->setItems(array_slice($this->workflow, $this->currentIndex));
	}

	public function getIndex() {
		return $this->currentIndex;
	}

	public function getWorkflow() {
		return $this->workflow;
	}
	

	static public function register($action, $function) {
		return parent::register($action.'_workflow', $function);
	}

	// next: call callback function for next item.
	// --------------------------------------------------
	public function next($params) {
		$rtn = false;
		if (array_key_exists('callback_workflow', self::$functions) && self::$functions['callback_workflow'] != null) {
			$currentAction = $this->getFirstItem();
			if ($currentAction != null) {
				$this->currentIndex++;
				$rtn = call_user_func(self::$functions['callback_workflow'], $params, $currentAction);
			}
		}
		return $rtn;
	}
}
?>
