<?php
// Class: ItemTypeUploader

class ItemTypeUploader {
	protected $files;					// Array of filenames

	protected $itemData;				// Array of item information
	protected $items;					// Array of Item objects, populated by createItems()

	protected $projectSlug;
	protected $projectId;

	// Constructor that takes projectSlug
	public function ItemTypeUploader($projectSlug) {
		$this->itemData = array();
		$this->items = array();

		$this->projectSlug = $projectSlug;

		$project = new Project($projectSlug);
		$this->projectId = $project->project_id;
	}

	// The function that does it all
	public function upload($filenames) {
		$this->preprocess($filenames);
		$this->createItems();
		$this->process();
		$this->cleanup();

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
		}
	}

	// Process files (move from temp dir to destination dir)
	public function process() {
		$project = new Project($this->projectSlug);
		Media::moveFilesForProject($project, $this->files);
	}

	// Cleanup (if necessary)
	public function cleanup() {
		// Remove everything in the temp dir (most of which should have already moved when moveFilesForProject was called)
	}
}

?>
