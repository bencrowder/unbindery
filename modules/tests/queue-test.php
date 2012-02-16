<?php

include '../Queue.php';

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

$_SERVER['array1'] = array('one', 'two', 'three', 'four');
$_SERVER['array2'] = array(new Point(1,1), new Point(2,2), new Point(3,3), new Point(4,1));

function destroyQueue($name) {
	echo "queue destroy\n";

	if ($name == '1') {
		unset($_SERVER['array1']);
		return true;
	} elseif ($name =='2') {
		unset($_SERVER['array2']);
		return true;
	}

	return false;
}

function saveQueue($name, $array) {
	echo "saving $name - " . json_encode($array);

	if ($name == '1') {
		$_SERVER['array1'] = $array;
		return $_SERVER['array1'];
	} elseif ($name =='2') {
		$_SERVER['array2'] = $array;
		return $_SERVER['array2'];
	}
}

function loadQueue($name) {
	$rtn = array();
	if ($name == '1') {
		$rtn = $_SERVER['array1'];
	} elseif ($name == '2') {
		$rtn = $_SERVER['array2'];
	}
	echo "loaded $name\n";
	return $rtn;
}

try {
	Queue::register('save', 'saveQueue');
	Queue::register('load', 'loadQueue');
} catch (Exception $e) {
	echo $e->getMessage();
}

$queue = new Queue('1');
echo "queue 1 loaded\n";
$item = $queue->getFirstItem();
echo 'first item: ' . $item . "\n";
$item = $queue->remove('three');
echo 'third item: ' . $item . "\n";
$item = $queue->getFirstItem();
echo 'second item: ' . $item . "\n";
if ($queue->save()) echo "\nsaved.\n";
echo json_encode($queue->getItems());
echo "\n";
echo "test 1 done\n\n";

try {
	Queue::register('compare', array('Point', 'compareItems'));
	Queue::register('destroy', 'destroyQueue');
} catch (Exception $e) {
	echo "FAIL\n";
	echo $e->getMessage();
}

$queue = new Queue('2');
echo "queue 1 loaded\n";
$item = $queue->getFirstItem();
echo 'first item: ' . $item . "\n";
$item = $queue->remove(new Point(4,0));
echo 'fourth item: ' . $item . "\n";
$item = $queue->getFirstItem();
echo 'second item: ' . $item . "\n";
if ($queue->save()) echo "\nsaved.\n";
echo json_encode($queue->getItems());
echo "\n";
$items = $queue->getItems();
$items[] = new Point(4,3);
$items[] = new Point(5,3);
$items[] = new Point(1,1);
$queue->setItems($items);
$queue->add(new Point(42,222));
if ($queue->save()) echo "\nsaved.\n";
echo "test 2 done\n\n";

$queue->destroy();

?>
