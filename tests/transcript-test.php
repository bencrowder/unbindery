<?php

include '../modules/Transcript.php';

// Dummy load function
function myTranscriptLoadFunction($params) {
	$transcriptID = $params['transcript_id'];
	$itemID = $params['item_id'];
	$userID = $params['user_id'];

	echo "Loaded transcript $transcriptID (item $itemID) for $userID.\n";

	return "This is my transcript text  ";
}

// Dummy save function
function myTranscriptSaveFunction($params) {
	$transcriptID = $params['transcript_id'];
	$itemID = $params['item_id'];
	$userID = $params['user_id'];

	echo "Saved transcript $transcriptID (item $itemID) for $userID.\n";
}

// Dummy load-transcript event
function onLoad($params) {
	echo "** In the on-load hook.\n";
	$transcript = $params['transcript'];
	$text = $transcript->getText();
	echo "Current text: [[$text]]\n";
	$transcript->setText(trim($text));
	echo "New text: [[" . $transcript->getText() . "]]\n";
}

// Dummy save-transcript event
function onSave($params) {
	echo "** In the on-save hook.\n";
	$transcript = $params['transcript'];
	$text = $transcript->getText();
	echo "Current text: [[$text]]\n";
	$transcript->setText("<page>$text</page>");
	echo "New text: [[" . $transcript->getText() . "]]\n";
}

// Dummy data
$data = array('transcript_id' => 5, 'item_id' => 193, 'user_id' => 'username');

// Register save/load hooks
echo "Registering events...\n";
$eventManager = new EventManager();
$eventManager->register('load', 'transcript', 'onLoad');
$eventManager->register('save', 'transcript', 'onSave');

echo "Setting event manager...\n";
Transcript::setEventManager($eventManager);

// Register
echo "Registering transcript hooks...\n";
Transcript::register('load', 'myTranscriptLoadFunction');
Transcript::register('save', 'myTranscriptSaveFunction');


echo "Loading transcript...\n\n";
$transcript = new Transcript();
$transcript->load($data);

echo "Text for transcript: [" . $transcript->getText() . "]\n\n";

echo "Changing text...\n";
$myText = "Hallelujah, it worked!";
$transcript->setText($myText);

echo "Text for transcript: [" . $transcript->getText() . "]\n\n";

echo "Saving transcript...\n\n";
$transcript->save($data);

echo "Text for transcript: [" . $transcript->getText() . "]\n\n";

echo "Creating second transcript...\n";
$transcript2 = new Transcript();
$transcript2->setText("This is the second transcript.");

echo "Collating...\n";

$collated = Transcript::collate(array($transcript, $transcript2), "\n\n--**--\n\n");
echo "Collated version:\n";
echo $collated;
echo "\n";

?>
