<?php

include '../Dispatch.php';

// Dummy data

class Colors {
	public $colors = array("red", "orange", "yellow", "green", "blue", "indigo", "violet");

	public function getNextColor() {
		$color = array_pop($this->colors);
		return $color;
	}
}

$colors = new Colors();

// Get next available item function
function getNextAvailable($params) {
	global $colors;

	return $colors->getNextColor();
}

// Test code
$dispatch = new Dispatch();

$dispatch->register('getNextAvailable');
$dispatch->init(array('colors' => $colors));

echo "Getting next item: ";
$color = $dispatch->next();
echo ($color) ? $color : 'end of list';

echo "\nGetting next item: ";
$color = $dispatch->next();
echo ($color) ? $color : 'end of list';

echo "\nGetting next item: ";
$color = $dispatch->next();
echo ($color) ? $color : 'end of list';

echo "\nGetting next item: ";
$color = $dispatch->next();
echo ($color) ? $color : 'end of list';

echo "\nGetting next item: ";
$color = $dispatch->next();
echo ($color) ? $color : 'end of list';

echo "\nGetting next item: ";
$color = $dispatch->next();
echo ($color) ? $color : 'end of list';

echo "\nGetting next item: ";
$color = $dispatch->next();
echo ($color) ? $color : 'end of list';

echo "\nGetting next item: ";
$color = $dispatch->next();
echo ($color) ? $color : 'end of list';

echo "\n";

?>
