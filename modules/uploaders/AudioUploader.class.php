<?php
// Class: AudioUploader

class AudioUploader extends ItemTypeUploader {
	public function preprocess($filenames) {
		// Get settings
		$sysPath = Settings::getProtected('sys_path');
		$uploaders = Settings::getProtected('uploaders');
		$chunkSize = $uploaders['Audio']['chunksize'];
		$chunkOverlap = $uploaders['Audio']['chunkoverlap'];
		$ffmpegPath = $uploaders['Audio']['ffmpeg'];

		// Chunk each MP3 into smaller segments
		foreach ($filenames as $file) {
			$path = "{$this->tempDir}/$file";

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

				// Get the size of the chunks, including the overlap
				$chunkSeconds = $chunkSize + $chunkOverlap;

				for ($start = 0; $start < $totalSeconds; $start += $chunkSize) {
					// Prep output filename
					$outputFilename = sprintf("$filename-%03d.$ext", $count);
					$outputPath = "{$this->tempDir}/$outputFilename";

					// And chunk the file
					$execStr = $ffmpegPath . " -ss $start -i $path -t $chunkSeconds -acodec copy $outputPath";
					exec($execStr);

					// Add to files array
					array_push($this->files, $outputFilename);

					$count++;
				}
			}
		}

		// And create the item info array
		foreach ($this->files as $file) {
			// Strip off the extension for the title
			$title = pathinfo($file, PATHINFO_FILENAME);

			$item = array(
				"title" => $title,
				"project_id" => $this->projectId,
				"transcript" => "",
				"type" => "audio",
				"href" => $file,
			);
			
			array_push($this->itemData, $item);
		}
	}
}

?>
