<?php

include '../modules/Event.php';

$eventManager = new EventManager();

echo "Getting events for 'system' group...\n";
$events = $eventManager->getEventsByGroup('system');
echo "Events: \n";
print_r($events);

echo "\nRegistering an 'echo'/'system' event...\n";
$eventManager->register("echo", "system", "myEvent");

echo "\nRegistering an 'echo'/'system' event...\n";
$eventManager->register("echo", "system", "myEvent2");

echo "\nRegistering a 'script'/'project' event...\n";
$eventManager->register("script", "project", "Test::event");

echo "\nGetting events for 'system' group...\n";
$events = $eventManager->getEventsByGroup('system');
echo "Events: \n";
print_r($events);

echo "\nTriggering said event with 'apple' and 'water' passed...\n";
echo "Also triggering event 2...\n";
$eventManager->trigger("echo", "system", array('fruit' => 'apple', 'beverage' => 'water'));

echo "\nRemoving said event...\n";
$eventManager->remove("echo", "system", "myEvent");

echo "\nGetting events for 'system' group...\n";
$events = $eventManager->getEventsByGroup('system');
echo "Events: \n";
print_r($events);

echo "\nRemoving all echo event...\n";
$eventManager->remove("echo", "system");

echo "\nGetting events for 'system' group...\n";
$events = $eventManager->getEventsByGroup('system');
echo "Events: \n";
print_r($events);

echo "\nTriggering 'script' event with 'hck' passed...\n";
$eventManager->trigger("script", "project", array('project' => 'hck'));


function myEvent($params) {
	$fruit = $params['fruit'];
	$beverage = $params['beverage'];

	echo "\n** Hello, myEvent was called and $fruit was passed, with a drink of $beverage on the side.\n";
}

function myEvent2($params) {
	echo "\n** Second, success.\n";
}

class Test {
	static public function event($params) {
		$project = $params['project'];

		echo "\n** I'm in a static class for project $project.\n";
	}
}

?>
