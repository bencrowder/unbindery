<?php

include '../Queue.php';
include '../Workflow.php';

class Point {
	public $x;
	public $y;

	function __construct($x, $y) {
		$this->x = $x;
		$this->y = $y;
	}

	static public function compareItems($item1, $item2) {
		if ($item1->x == $item2->x) return true;
		else return false;
	}

	public function __toString() {
		return '[' . $this->x . ',' . $this->y . ']';
	}
}

function parser($params, $action) {
	return "This is the action: $action.\nAnd this is the item: " . json_encode($params) . "\n";
}

try {
	Workflow::register('callback', 'parser');
} catch (Exception $e) {
	echo $e->getMessage();
}

$workflow = new Workflow('@proofer/@proofer,@reviewer/@reviewer,bencrowder');
echo $workflow->next(array('item'=>'nothing'));
echo $workflow->next(array('item'=>'nothing'));
echo "resetting index for new item\n";
echo $workflow->setIndex(1);
$item = array('item'=>'item2');
echo $workflow->next($item);
echo $workflow->next($item);
echo $workflow->next($item);
?>
