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

	// TODO: remove?
	public function saveText($username, $draft, $review, $review_username, $transcript) {
		$adminemail = Settings::getProtected('adminemail');
		$emailsubject = Settings::getProtected('emailsubject');
		$app_url = Settings::getProtected('app_url');

		// load the project
		$project = new Project($this->project_slug);
		$user = new User($username);
		$review_user = new User($review_username);

		// check and see if we already have a draft
		$existing_draft = $this->db->userHasTranscriptDraft($username, $this->item_id, $this->project_id);

		if ($review) {
			$status = "reviewed";
		} else {
			if ($draft) { 
				$status = "draft";
			} else {
				$status = "completed";
			}
		}

		if ($existing_draft) {
			// update texts with $draft status
			$this->db->updateItemTranscript($this->item_id, $this->project_id, $status, $transcript, $username);
		} else {
			// insert into texts with $draft status
			$this->db->addItemTranscript($this->item_id, $this->project_id, $status, $transcript, $username);
		}

		// we're finished with this item
		if (!$draft) {
			if ($review) {
				// update date_reviewed for this assignment
				$this->db->updateAssignmentReviewDate($this->item_id, $this->project_id, $review_username);

				$subject = "$emailsubject Reviewed " . $this->project_slug . "/" . $this->item_id . "/" . $review_username;
				$message = "$username reviewed the item " . $this->project_slug . "/" . $this->item_id . ", proofed by $review_username.";
				Mail::sendMessage($adminemail, $subject, $message);

				// if the user who did the proofing was in training, clear them
				if ($review_user->status == "training") {
					$this->db->updateUserStatus($review_username, 'clear');

					// email the user to let them know
					$subject = "$emailsubject Clearance granted";
					$message = "You've been cleared for further proofing!\n\n";
					$message .= $app_url;
					Mail::sendMessage($review_user->email, $subject, $message);

					// email admin to let them know
					$subject = "$emailsubject Cleared $review_username";
					$message = "Cleared $review_username for further proofing.";
					Mail::sendMessage($adminemail, $subject, $message);
				}
			} else {
				// update user score (+5 for completing a page)
				// and only do it if they haven't previously completed this page
				$this->db->updateUserScoreForItem($username, $this->item_id, $this->project_id, 5);

				// update date_completed for this assignment
				$this->db->completeAssignment($username, $this->item_id, $this->project_id);

				// check number of revisions
				$proofcount = $this->db->getItemProofCount($this->item_id, $this->project_id);

				if ($proofcount >= intval($project->num_proofs)) {
					$this->db->setItemStatus($this->item_id, 'completed');
				}

				$subject = "$emailsubject $username completed " . $this->project_slug . "/" . $this->item_id;
				$message = "$username completed the item " . $this->project_slug . "/" . $this->item_id;

				if ($user->status == "training") {
					$message .= "\n\n$username is in training, so you need to review their work and clear them.";
				}

				$message .= "\n\nReview link: $app_url/admin/review/{$this->project_slug}/{$this->item_id}/{$username}";

				Mail::sendMessage($adminemail, $subject, $message);
			}
		}

		return "success";
	}

	public function setStatus($status) {
		return $this->db->setItemStatus($this->item_id, $this->project_id, $status);
	}

	public function deleteFromDatabase() {
		return $this->db->deleteItemFromDatabase($this->item_id);
	}

	public function getTemplateReady() {
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
			"type" = $this->type,
			"href" => $this->href,
			"workflow_index" => $this->workflow_index,
			"order" => $this->order,
		);

		return $userArray;
	}
}
