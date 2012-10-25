<?php
// Class: AudioUploader

class AudioUploader extends ItemTypeUploader {
	public function preprocess($filenames) {
		$uploaders = Settings::getProtected('uploaders');
		$chunkSize = $uploaders['Audio']['chunksize'];
		$ffmpegPath = $uploaders['Audio']['ffmpeg'];

		$sysPath = Settings::getProtected('sys_path');
		$tempDir = "$sysPath/htdocs/media/temp/{$this->projectSlug}";

		// Chunk each MP3 into smaller segments
		foreach ($filenames as $file) {
			$path = $tempDir . "/" . $file;

			// These four lines from http://stackoverflow.com/questions/3069574/get-the-length-of-an-audio-file-php
			$execStr = $ffmpegPath . " -i $path 2>&1 | grep 'Duration' | cut -d ' ' -f 4 | sed s/,//";
			$time = exec($execStr);

			list($hms, $milli) = explode('.', $time);
			list($hours, $minutes, $seconds) = explode(':', $hms);
			$totalSeconds = ($hours * 3600) + ($minutes * 60) + $seconds;

			$count = 1;

			// If the MP3 is longer than the chunk size, split it
			if ($totalSeconds > $chunkSize) {
				$start = 0;

				// Get the filename and extension
				$filename = pathinfo($file, PATHINFO_FILENAME);
				$ext = pathinfo($file, PATHINFO_EXTENSION);

				for ($start = 0; $start < $totalSeconds; $start += $chunkSize) {
					// Prep output filename
					$outputFilename = sprintf("$tempDir/$filename-%03d.$ext", $count);

					// Move forward by $chunkSize
					$endSeconds = $chunkSize * $count;

					// And chunk the file
					$execStr = $ffmpegPath . " -ss $start -i $path -t $endSeconds -acodec copy $outputFilename";
					exec($execStr);

					$count++;
				}
			}

			// if length > 1 minute
				// calculate # segments?
				// call ffmpeg to create chunks

			// Foreach chunk
				// Push new filenames to $this->files
				// array_push($filename, $this->files)

				// And push the item data
				// Strip off the extension for the title
				$title = pathinfo($file, PATHINFO_FILENAME);

				$item = array(
					"title" => $title,
					"project_id" => $this->projectId,
					"transcript" => "",
					"type" => "audio",
					"href" => $file
				);
				
				array_push($this->itemData, $item);
		}
	}

	// TODO: remove the original files
	public function cleanup() {
	}
}

?>
