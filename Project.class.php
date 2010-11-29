<?php

class Project {
	public $project_id;

	public $title;
	public $slug;
	public $description;
	public $owner;
	public $status;

	public function getJSON() {
		return json_encode(array("project_id" => $this->project_id, "title" => $this->title, "slug" => $this->slug, "description" => $this->description, "owner" => $this->owner, "status" => $this->status));
	}
}
