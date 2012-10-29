<?php
// Class: ItemTypeUploader

class ItemTypeUploader {
	protected $files;					// Array of filenames

	protected $itemData;				// Array of item information
	protected $items;					// Array of Item objects, populated by createItems()

	protected $projectSlug;
	protected $projectId;
	protected $tempDir;

	// Constructor that takes projectSlug
	public function ItemTypeUploader($projectSlug) {
		$this->itemData = array();
		$this->items = array();
		$this->files = array();

		$this->projectSlug = $projectSlug;

		$sysPath = Settings::getProtected('sys_path');
		$this->tempDir = "$sysPath/htdocs/media/temp/{$this->projectSlug}";

		$project = new Project($projectSlug);
		$this->projectId = $project->project_id;
	}

	// The main function
	public function upload($filenames) {
		$this->preprocess($filenames);
		$this->createItems();
		$this->process();

		return $this->items;
	}

	// Preprocess the files and assign the final files to $this->files
	// Also create the items and put them in the $items array
	// This is the only function you need to redefine
	public function preprocess($filenames) {
		// Go through $filenames and create $this->files
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

	// Create the items in the database
	public function createItems() {
		// Load the queue
		$projectQueue = new Queue("project.proof:{$this->projectSlug}", false);

		// Now go through the item info array
		foreach ($this->itemData as $itemInfo) {
			// Create a new item
			$item = new Item();

			// Populate it
			$item->title = $itemInfo['title'];
			$item->project_id = $itemInfo['project_id'];
			$item->transcript = $itemInfo['transcript'];
			$item->type = $itemInfo['type'];
			$item->href = $itemInfo['href'];

			// And add it to the database
			$item->save();

			// Save it to our $this->items array
			array_push($this->items, $item);

			error_log("Item: " . $item->item_id . "|" . $item->project_id . "|" . $item->title . "|" . $item->type);

			// Add it to the queue
			$projectQueue->add($item);
		}

		// Save the project queue
		$projectQueue->save();
	}

	// Process files (move from temp dir to destination dir)
	public function process() {
		$project = new Project($this->projectSlug);
		Media::moveFilesForProject($project, $this->files);
	}

	// Cleanup (run this manually after everything has been processed)
	public function cleanup() {
		// Remove all remaining files in the temp dir
		foreach (glob($this->tempDir . "/*") as $file) {
			unlink($file);
		}

		// Remove the temp dir since we no longer need it
		rmdir($this->tempDir);
	}
}

?>
