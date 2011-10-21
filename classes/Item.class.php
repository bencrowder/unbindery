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
		$query = "SELECT * FROM items ";
		$query .= "JOIN projects ON items.project_id = projects.id ";
		$query .= "WHERE items.id = ? ";
		$query .= "AND projects.slug = ?;";

		$results = $this->db->query($query, array($item_id, $project_slug));
		$result = $results[0];

		if (isset($result)) {
			$this->item_id = trim($result['id']);
			$this->project_id = trim($result['project_id']);
			$this->project_slug = $project_slug;
			$this->title = trim($result['title']);
			$this->itemtext = trim($result['itemtext']);
			$this->status = trim($result['status']);
			$this->type = trim($result['type']);
			$this->href = trim($result['href']);
		}

		// Update the item text with the user's revision, if available
		if ($username != '') {
			$query = "SELECT itemtext FROM texts ";
			$query .= "WHERE item_id = ? ";
			$query .= "AND project_id = ? ";
			$query .= "AND user = ?;";

			$results = $this->db->query($query, array($item_id, $this->project_id, $username));

			if (count($results) > 0) {
				$this->itemtext = trim($results[0]['itemtext']);
			}
		}
	}

	public function save() {
		$sql = "UPDATE items ";
		$sql .= "SET title = ?, project_id = ?, itemtext = ?, status = ?, type = ?, href = ? ";
		$sql .= "WHERE id = ?;";

		$this->db->execute($sql, array($this->title, $this->project_id, $this->itemtext, $this->status, $this->type, $this->href, $this->item_id));

		return true;
	}

	public function saveText($username, $draft, $review, $review_username, $itemtext) {
		$adminemail = Settings::getProtected('adminemail');
		$emailsubject = Settings::getProtected('emailsubject');
		$siteroot = Settings::getProtected('siteroot');

		// load the project
		$project = new Project($this->db, $this->project_slug);
		$user = new User($username);
		$review_user = new User($review_username);

		// check and see if we already have a draft
		$query = "SELECT itemtext FROM texts ";
		$query .= "WHERE item_id = ? ";
		$query .= "AND project_id = ? ";
		$query .= "AND user = ?;";
		$results = $this->db->query($query, array($this->item_id, $this->project_id, $username));
		$result = $results[0];

		if (isset($result)) {
			$existing_draft = true;
		} else {
			$existing_draft = false;
		}

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
			$sql = "UPDATE texts SET itemtext = ?, ";
			$sql .= "date = NOW(), ";
			$sql .= "status = ? ";
			$sql .= "WHERE item_id = ? ";
			$sql .= "AND project_id = ? ";
			$sql .= "AND user = ?;";

			$this->db->execute($sql, array($itemtext, $status, $this->item_id, $this->project_id, $username));
		} else {
			// insert into texts with $draft status
			$sql = "INSERT INTO texts (project_id, item_id, user, date, itemtext, status) VALUES (?, ?, ?, NOW(), ?, ?);";

			$this->db->execute($sql, array($this->project_id, $this->item_id, $username, $itemtext, $status));
		}

		// we're finished with this item
		if (!$draft) {
			if ($review) {
				// update date_reviewed for this assignment
				$sql = "UPDATE assignments ";
				$sql .= "SET date_reviewed = NOW() ";
				$sql .= "WHERE username = ? ";
				$sql .= "AND item_id = ? ";
				$sql .= "AND project_id = ?;";

				$this->db->execute($sql, array($review_username, $this->item_id, $this->project_id));

				$subject = "$emailsubject Reviewed " . $this->project_slug . "/" . $this->item_id . "/" . $review_username;
				$message = "$username reviewed the item " . $this->project_slug . "/" . $this->item_id . ", proofed by $review_username.";
				Mail::sendMessage($adminemail, $subject, $message);

				// if the user who did the proofing was in training, clear them
				if ($review_user->status == "training") {
					$sql = "UPDATE users SET status = 'clear' WHERE username = ?;";
					$this->db->execute($sql, array($review_username));

					// email the user to let them know
					$subject = "$emailsubjecT Clearance granted";
					$message = "You've been cleared for further proofing!\n\n";
					$message .= $siteroot;
					Mail::sendMessage($review_user->email, $subject, $message);

					// email admin to let them know
					$subject = "$emailsubjecT Cleared $review_username";
					$message = "Cleared $review_username for further proofing.";
					Mail::sendMessage($adminemail, $subject, $message);
				}
			} else {
				// update user score (+5 for completing a page)
				// and only do it if they haven't previously completed this page
				$sql = "UPDATE users, assignments SET score = score + 5 ";
				$sql .= "WHERE users.username = ? ";
				$sql .= "AND item_id = ? ";
				$sql .= "AND project_id = ? ";
				$sql .= "AND date_completed IS NULL;";

				$this->db->execute($sql, array($username, $this->item_id, $this->project_id));

				// update date_completed for this assignment
				$sql = "UPDATE assignments SET date_completed = NOW() ";
				$sql .= "WHERE username = ? ";
				$sql .= "AND item_id = ? ";
				$sql .= "AND project_id = ?;";

				$this->db->execute($sql, array($username, $this->item_id, $this->project_id));

				// check number of revisions
				$query = "SELECT COUNT(id) as revisioncount FROM assignments ";
				$query .= "WHERE item_id = ? ";
				$query .= "AND project_id = ? ";
				$query .= "AND date_completed IS NOT NULL";

				$results = $this->db->query($query, array($this->item_id, $this->project_id));
				$revisioncount = $results[0]["revisioncount"];

				if (intval($revisioncount) >= intval($project->num_proofs)) {
					$sql = "UPDATE items SET status = 'completed' ";
					$sql .= "WHERE id = ? ";
					$sql .= "AND project_id = ?;";

					$this->db->execute($sql, array($this->item_id, $this->project_id));
				}

				$subject = "$emailsubject $username completed " . $this->project_slug . "/" . $this->item_id;
				$message = "$username completed the item " . $this->project_slug . "/" . $this->item_id;

				if ($user->status == "training") {
					$message .= "\n\n$username is in training, so you need to review their work and clear them.";
				}

				$message .= "\n\nReview link: $siteroot/admin/review/{$this->project_slug}/{$this->item_id}/{$username}";

				Mail::sendMessage($adminemail, $subject, $message);
			}
		}

		return "success";
	}

	public function getJSON() {
		return json_encode(array("item_id" => $this->item_id, "project_id" => $this->project_id, "title" => $this->title, "itemtext" => $this->itemtext, "status" => $this->status, "type" => $this->type, "href" => $this->href));
	}

	public function getNextPage() {
		$this->db->connect();

		$results = $this->db->query("SELECT items.id FROM items JOIN projects ON items.project_id = projects.id WHERE projects.slug = ? AND items.id > ? LIMIT 1", array($this->project_slug, $this->item_id));

		if (count($results) > 0) {
			$nextpage_id = trim($results[0]['id']);
		}

		return $nextpage_id;
	}
}
