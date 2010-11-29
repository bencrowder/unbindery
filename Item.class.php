<?php

class Item {
	public $item_id;
	public $project_id;

	public $title;

	public $itemtext;

	public $status;

	public $type;
	public $href;

	public $width;
	public $height;
	public $length;

	public function getJSON() {
		return json_encode(array("item_id" => $this->item_id, "project_id" => $this->project_id, "title" => $this->title, "itemtext" => $this->itemtext, "status" => $this->status, "type" => $this->type, "href" => $this->href, "width" => $this->width, "height" => $this->height, "length" => $this-length));
	}
}
