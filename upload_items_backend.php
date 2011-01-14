<?php

if (!empty($_FILES)) {
	$tempFile = $_FILES['items']['tmp_name'];
	$targetPath = dirname(__FILE__) . $_REQUEST['folder'] . '/';
	$targetFile = str_replace('//', '/', $targetPath) . $_FILES['items']['name'];

	move_uploaded_file($tempFile, $targetFile);
	echo str_replace(dirname(__FILE__), '', $targetFile);
}

?>
