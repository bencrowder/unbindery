<?php

class Item {
	private $db;

	private $item_id;
	private $project_id;
	private $project_slug;
	private $project_type;
	private $project_public;
	private $project_owner;
	private $title;
	private $transcript;
	private $userTranscript;
	private $status;
	private $type;
	private $href;
	private $workflow_index;
	private $order;

	public function Item($itemId = '', $projectSlug = '', $username = '', $type = 'proof') {
		$this->db = Settings::getProtected('db');

		if ($itemId && $projectSlug) {
			$this->load($itemId, $projectSlug, $username, $type);
		}
	}

	public function __set($key, $val) {
		$this->$key = $val;
	}

	public function __get($key) {
		return $this->$key;
	}

	public function load($itemId, $projectSlug, $username = '', $type = 'proof') {
		$item = $this->db->loadItem($itemId, $projectSlug);

		if (isset($item)) {
			if (gettype($item) == 'array') {
				$this->item_id = $item['id'];
				$this->project_id = $item['project_id'];
				$this->project_slug = $projectSlug;
				$this->project_type = trim($item['project_type']);
				$this->project_public = $item['project_public'];
				$this->project_owner = trim($item['project_owner']);
				$this->title = trim($item['title']);
				$this->transcript = trim($item['transcript']);
				$this->status = trim($item['status']);
				$this->type = trim($item['type']);
				$this->href = trim($item['href']);
				$this->workflow_index = $item['workflow_index'];
				$this->order = $item['order'];
			} else {
				$this->item_id = -1;
			}
		}

		// Update the item text with the user's revision, if available
		if ($username != '') {
			$transcript = $this->db->loadItemTranscript($this->project_id, $itemId, $username, $type);
			if ($transcript != '') {
				$this->userTranscript = $transcript;
			}
		}
	}

	public function loadWithProjectID($itemId, $projectId, $username = '', $type) {
		$item = $this->db->loadItemWithProjectID($itemId, $projectId);

		if (isset($item)) {
			if (gettype($item) == 'array') {
				$this->item_id = $item['id'];
				$this->project_id = $projectId;
				$this->project_slug = trim($item['project_slug']);
				$this->project_type = trim($item['project_type']);
				$this->project_public = $item['project_public'];
				$this->project_owner = trim($item['project_owner']);
				$this->title = trim($item['title']);
				$this->transcript = trim($item['transcript']);
				$this->status = trim($item['status']);
				$this->type = trim($item['type']);
				$this->href = trim($item['href']);
				$this->workflow_index = $item['workflow_index'];
				$this->order = $item['order'];
			} else {
				$this->item_id = -1;
			}
		}

		// Update the item text with the user's revision, if available
		if ($username != '') {
			$transcript = $this->db->loadItemTranscript($this->project_id, $itemId, $username, $type);
			if ($transcript != '') {
				$this->userTranscript = $transcript;
			}
		}
	}

	public function save() {
		if ($this->item_id) {
			return $this->db->saveExistingItem($this->item_id, $this->title, $this->project_id, $this->transcript, $this->status, $this->type, $this->href, $this->workflow_index, $this->order);
		} else {
			// Create a new item
			$this->item_id = $this->db->addItem($this->title, $this->project_id, $this->transcript, $this->type, $this->href);
			return $this->item_id;
		}
	}

	public function setStatus($status) {
		return $this->db->setItemStatus($this->item_id, $this->project_id, $status);
	}

	public function deleteFromDatabase() {
		return $this->db->deleteItemFromDatabase($this->item_id);
	}

	public function getResponse() {
		$userArray = array(
			"item_id" => $this->item_id,
			"project_id" => $this->project_id,
			"project_slug" => $this->project_slug,
			"project_type" => $this->project_type,
			"project_public" => $this->project_public,
			"project_owner" => $this->project_owner,
			"title" => $this->title,
			"transcript" => $this->transcript,
			"userTranscript" => $this->userTranscript,
			"status" => $this->status,
			"type" => $this->type,
			"href" => $this->href,
			"workflow_index" => $this->workflow_index,
			"order" => $this->order,
		);

		return $userArray;
	}
}
