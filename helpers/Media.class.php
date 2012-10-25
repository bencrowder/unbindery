<?php

class Media {

	// --------------------------------------------------
	// Media::moveFiles
	// $files = array of filenames (full paths)
	// $destDir = destination directory (path relative to htdocs/media)

	static public function moveFiles($files, $destDir) {
		$sysPath = Settings::getProtected('sys_path');
		$targetDir = "$sysPath/htdocs/media/$destDir";	// the full path to the destination

		// Make sure there are files to move
		if (empty($files)) return false;

		// Make sure the destination directory exists and is a directory
		if (!$destDir || !file_exists($targetDir) || !is_dir($targetDir)) return false;

		// Make sure it's writeable
		if (!is_writable($targetDir)) return false;

		// Go through the files and move them
		foreach ($files as $file) {
			// Make sure it's a file
			if (is_file($file)) {
				// Get the base filename
				$basename = basename($file);

				// Move it
				$status = rename($file, "$targetDir/$basename");
				if (!$status) return false;
			}
		}

		return true;
	}


	// --------------------------------------------------
	// Media::moveFilesForProject
	// Moves files from the project's temp dir into the final location
	// $project = project object
	// $files = array of filenames (relative paths), defaults to everything in $sysPath/htdocs/media/temp/$slug

	static public function moveFilesForProject($project, $files = array()) {
		$sysPath = Settings::getProtected('sys_path');

		$tempDir = "$sysPath/htdocs/media/temp/{$project->slug}";

		// Set up the target dir
		if ($project->type == 'system') {
			$targetDir = "$sysPath/htdocs/media/projects/{$project->slug}";
		} else if ($project->type == 'user') {
			$targetDir = "$sysPath/htdocs/media/users/{$project->owner}/{$project->slug}";
		}

		// Make sure the directory exists, and if it doesn't, create it
		if (!file_exists($targetDir)) {
			mkdir($targetDir, 0775, true);

			// Change permissions (drwxrwxr-x)
			// For some reason mkdir's permissions don't actually work
			chmod($targetDir, 0775);
		}

		// Set up the array
		$filesToMove = array();

		// If the files array is empty
		if (empty($files)) {
			// Get everything in the temp dir
			if ($handle = opendir($tempDir)) {
				while (($file = readdir($handle)) !== false) {
					// If it's a file, add it to the array of files to move
					if (is_file("$tempDir/$file")) {
						array_push($filesToMove, "$tempDir/$file");
					}
				}
			}
		} else {
			// Loop through the files array
			foreach ($files as $file) {
				array_push($filesToMove, "$tempDir/$file");
			}
		}

		// Finally, move the files
		Media::moveFiles($filesToMove, str_replace("$sysPath/htdocs/media/", "", $targetDir));

		// If the temporary project directory is now empty, remove it
		if (is_readable($tempDir) && count(scandir($tempDir)) == 2) {
			rmdir($tempDir);
		}

		return true;
	}


	// --------------------------------------------------
	// Media::moveUploadedFilesToTempDir()
	// Does what the name says it does

	static public function moveUploadedFilesToTempDir($projectSlug) {
		$sysPath = Settings::getProtected('sys_path');

		if (!empty($_FILES)) {
			// Get the filename and project slug
			$tempFile = $_FILES['items']['tmp_name'];
			error_log("files: " . $tempFile);

			// Set the target directory
			$targetDir = "$sysPath/htdocs/media/temp/$projectSlug/";

			// If the directory doesn't exist, create it
			if (!file_exists($targetDir)) {
				mkdir($targetDir, 0775, true);

				// Change permissions (drwxrwxr-x)
				// For some reason mkdir's permissions don't actually work
				chmod($targetDir, 0775);
			}

			// And the target filename with path
			$targetFile = $targetDir . $_FILES['items']['name'];
			
			// Move the file
			move_uploaded_file($tempFile, $targetFile);

			// Strip the targetDir first since we only care about the filename
			// And echo the filename back so the JavaScript can know it's done
			echo str_replace($targetDir, '', $targetFile);
		}
	}
}

?>
