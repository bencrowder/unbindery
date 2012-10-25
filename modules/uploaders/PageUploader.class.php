<?php
// Class: PageUploader

class PageUploader extends ItemTypeUploader {
	public function preprocess($filenames) {
		// No file processing needed on page images
		$this->files = $filenames;

		// Now create the items
		foreach ($this->files as $file) {
			// Strip off the extension for the title
			$title = pathinfo($file, PATHINFO_FILENAME);

			$item = array(
				"title" => $title,
				"project_id" => $this->projectId,
				"transcript" => "",
				"type" => "page",
				"href" => $file
			);
			
			array_push($this->itemData, $item);
		}
	}
}

?>
