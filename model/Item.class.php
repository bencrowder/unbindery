<?php

class Item {
	private $db;

	private $item_id;
	private $project_id;
	private $project_slug;
	private $title;
	private $itemtext;
	private $status;
	private $type;
	private $href;

	public function Item($db = '', $item_id = "", $project_slug = "", $username = "") {
		$this->db = Settings::getProtected('db');

		if ($item_id && $project_slug) {
			$this->load($item_id, $project_slug, $username);
		}
	}

	public function __set($key, $val) {
		$this->$key = $val;
	}

	public function __get($key) {
		return $this->$key;
	}

	public function load($item_id, $project_slug, $username = "") {
		$item = $this->db->loadItem($item_id, $project_slug);
		if (isset($item)) {
			$this->item_id = trim($item['id']);
			$this->project_id = trim($item['project_id']);
			$this->project_slug = $project_slug;
			$this->title = trim($item['title']);
			$this->transcript = trim($item['transcript']);
			$this->status = trim($item['status']);
			$this->type = trim($item['type']);
			$this->href = trim($item['href']);
		}

		// Update the item text with the user's revision, if available
		if ($username != '') {
			$itemtext = $this->db->getUserTranscript($item_id, $this->project_id, $username);
			if ($itemtext != '') {
				$this->itemtext = $itemtext;
			}
		}
	}

	public function loadWithProjectID($item_id, $project_id, $username = "") {
		$item = $this->db->loadItemWithProjectID($item_id, $project_id);
		if (isset($item)) {
			$this->item_id = trim($item['id']);
			$this->project_id = $project_id;
			$this->project_slug = trim($item['project_slug']);
			$this->title = trim($item['title']);
			$this->transcript = trim($item['transcript']);
			$this->status = trim($item['status']);
			$this->type = trim($item['type']);
			$this->href = trim($item['href']);
		}

		// Update the item text with the user's revision, if available
		if ($username != '') {
			$itemtext = $this->db->getUserTranscript($item_id, $this->project_id, $username);
			if ($itemtext != '') {
				$this->itemtext = $itemtext;
			}
		}
	}

	public function save() {
		return $this->db->saveExistingItem($this->item_id, $this->title, $this->project_id, $this->itemtext, $this->status, $this->type, $this->href);
	}

	public function saveText($username, $draft, $review, $review_username, $itemtext) {
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
			$this->db->updateItemTranscriptStatus($this->item_id, $this->project_id, $status, $itemtext, $username);
		} else {
			// insert into texts with $draft status
			$this->db->addItemTranscript($this->item_id, $this->project_id, $status, $itemtext, $username);
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

	public function getNextItem() {
		return $this->db->getNextItem($this->item_id, $this->project_slug);
	}

	public function getJSON() {
		return json_encode(array("item_id" => $this->item_id, "project_id" => $this->project_id, "title" => $this->title, "itemtext" => $this->itemtext, "status" => $this->status, "type" => $this->type, "href" => $this->href));
	}
}
