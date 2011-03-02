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

	public function Item($db, $item_id = "", $project_slug = "", $username = "") {
		$this->db = $db;

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
		$this->db->connect();

		$query = "SELECT * FROM items ";
		$query .= "JOIN projects ON items.project_id = projects.id ";
		$query .= "WHERE items.id = '" . mysql_real_escape_string($item_id) . "' ";
		$query .= "AND projects.slug = '" . mysql_real_escape_string($project_slug) . "'";
		$result = mysql_query($query) or die ("Couldn't run: $query");

		if (mysql_numrows($result)) {
			$this->item_id = trim(mysql_result($result, 0, "id"));
			$this->project_id = trim(mysql_result($result, 0, "project_id"));
			$this->project_slug = $project_slug;
			$this->title = trim(mysql_result($result, 0, "title"));
			$this->itemtext = trim(mysql_result($result, 0, "itemtext"));
			$this->status = trim(mysql_result($result, 0, "status"));
			$this->type = trim(mysql_result($result, 0, "type"));
			$this->href = trim(mysql_result($result, 0, "href"));
		}

		// Update the item text with the user's revision, if available
		if ($username != '') {
			$query = "SELECT itemtext FROM texts ";
			$query .= "WHERE item_id=" . mysql_real_escape_string($item_id) . " ";
			$query .= "AND project_id=" . mysql_real_escape_string($this->project_id) . " ";
			$query .= "AND user='" . mysql_real_escape_string($username) . "'";
			$result = mysql_query($query) or die ("Couldn't run: $query");

			if (mysql_numrows($result)) {
				$this->itemtext = trim(mysql_result($result, 0, "itemtext"));
			}
		}

		$this->db->close();
	}

	public function save() {
		$this->db->connect();

		$query = "UPDATE items ";
		$query .= "SET title = '" . mysql_real_escape_string($this->title) . "', ";
		$query .= "project_id = '" . mysql_real_escape_string($this->project_id) . "', ";
		$query .= "itemtext = '" . mysql_real_escape_string($this->itemtext) . "', ";
		$query .= "status = '" . mysql_real_escape_string($this->status) . "', ";
		$query .= "type = '" . mysql_real_escape_string($this->type) . "', ";
		$query .= "href = '" . mysql_real_escape_string($this->href) . "' ";
		$query .= "WHERE id = " . $this->item_id . " ";

		$result = mysql_query($query) or die ("Couldn't run: $query");

		$this->db->close();

		return true;
	}

	public function saveText($username, $draft, $review, $review_username, $itemtext) {
		global $ADMINEMAIL;
		global $SITEROOT;

		// load the project
		$project = new Project($this->db, $this->project_slug);
		$user = new User($this->db, $username);
		$review_user = new User($this->db, $review_username);

		$this->db->connect();

		// check and see if we already have a draft
		$query = "SELECT itemtext FROM texts ";
		$query .= "WHERE item_id=" . mysql_real_escape_string($this->item_id) . " ";
		$query .= "AND project_id=" . mysql_real_escape_string($this->project_id) . " ";
		$query .= "AND user='" . mysql_real_escape_string($username) . "'";
		$result = mysql_query($query) or die ("Couldn't run: $query");

		if (mysql_numrows($result)) {
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
			$query = "UPDATE texts SET itemtext = '" . mysql_real_escape_string($itemtext) . "', ";
			$query .= "date = NOW(), ";
			$query .= "status = '" . mysql_real_escape_string($status) . "' ";
			$query .= "WHERE item_id=" . mysql_real_escape_string($this->item_id) . " ";
			$query .= "AND project_id=" . mysql_real_escape_string($this->project_id) . " ";
			$query .= "AND user='" . mysql_real_escape_string($username) . "'";
			$result = mysql_query($query) or die ("Couldn't run: $query");
		} else {
			// insert into texts with $draft status
			$query = "INSERT INTO texts (project_id, item_id, user, date, itemtext, status) VALUES (" . mysql_real_escape_string($this->project_id) . ", " . mysql_real_escape_string($this->item_id) . ", '" . mysql_real_escape_string($username) . "', NOW(), '" . mysql_real_escape_string($itemtext) . "', '" . mysql_real_escape_string($status) . "')";
			$result = mysql_query($query) or die ("Couldn't run: $query");
		}

		// we're finished with this item
		if (!$draft) {
			if ($review) {
				// update date_reviewed for this assignment
				$query = "UPDATE assignments ";
				$query .= "SET date_reviewed = NOW() ";
				$query .= "WHERE username = '" . mysql_real_escape_string($review_username) . "' ";
				$query .= "AND item_id = {$this->item_id} ";
				$query .= "AND project_id = {$this->project_id} ";
				$result = mysql_query($query) or die ("Couldn't run: $query");

				// and set the status on the item to reviewed
				$query = "UPDATE items ";
				$query .= "SET status = 'reviewed' ";
				$query .= "WHERE id = {$this->item_id} ";
				$query .= "AND project_id = {$this->project_id} ";
				$result = mysql_query($query) or die ("Couldn't run: $query");

				$subject = "[Unbindery] Reviewed " . $this->project_slug . "/" . $this->item_id . "/" . $review_username;
				$message = "$username reviewed the item " . $this->project_slug . "/" . $this->item_id . ", proofed by $review_username.";
				Mail::sendMessage($ADMINEMAIL, $subject, $message);

				// if the user who did the proofing was in training, clear them
				if ($review_user->status == "training") {
					$query = "UPDATE users SET status = 'clear' ";
					$query .= "WHERE username = '" . mysql_real_escape_string($review_username) . "';";
					$result = mysql_query($query) or die ("Couldn't run: $query");

					// email the user to let them know
					$subject = "[Unbindery] Clearance granted";
					$message = "You've been cleared for further proofing!\n\n";
					$message .= $SITEROOT;
					Mail::sendMessage($review_user->email, $subject, $message);

					// email admin to let them know
					$subject = "[Unbindery] Cleared $review_username";
					$message = "Cleared $review_username for further proofing.";
					Mail::sendMessage($ADMINEMAIL, $subject, $message);
				}
			} else {
				// update user score (+5 for completing a page)
				// and only do it if they haven't previously completed this page
				$query = "UPDATE users, assignments SET score = score + 5 ";
				$query .= "WHERE users.username = '" . mysql_real_escape_string($username) . "' ";
				$query .= "AND item_id = {$this->item_id} ";
				$query .= "AND project_id = {$this->project_id} ";
				$query .= "AND date_completed IS NULL ";
				$result = mysql_query($query) or die ("Couldn't run: $query");

				// update date_completed for this assignment
				$query = "UPDATE assignments SET date_completed = NOW() ";
				$query .= "WHERE username = '" . mysql_real_escape_string($username) . "' ";
				$query .= "AND item_id = " . $this->item_id . " ";
				$query .= "AND project_id = " . $this->project_id;
				$result = mysql_query($query) or die ("Couldn't run: $query");

				// check number of revisions
				$query = "SELECT COUNT(id) as revisioncount FROM assignments ";
				$query .= "WHERE item_id = " . $this->item_id . " ";
				$query .= "AND project_id = " . $this->project_id . " ";
				$query .= "AND date_completed IS NOT NULL";
				$result = mysql_query($query) or die ("Couldn't run: $query");

				$row = mysql_fetch_assoc($result);
				$revisioncount = $row["revisioncount"];

				if (intval($revisioncount) >= intval($project->num_proofs)) {
					$query = "UPDATE items SET status = 'completed' ";
					$query .= "WHERE id = " . $this->item_id . " ";
					$query .= "AND project_id = " . $this->project_id . ";";
					$result = mysql_query($query) or die ("Couldn't run: $query");
				}

				$subject = "[Unbindery] $username completed " . $this->project_slug . "/" . $this->item_id;
				$message = "$username completed the item " . $this->project_slug . "/" . $this->item_id;

				if ($user->status == "training") {
					$message .= "\n\n$username is in training, so you need to review their work and clear them.";
				}

				$message .= "\n\nReview link: $SITEROOT/admin/review/{$this->project_slug}/{$this->item_id}/{$username}";

				Mail::sendMessage($ADMINEMAIL, $subject, $message);
			}
		}

		$this->db->close();

		return "success";
	}

	public function getJSON() {
		return json_encode(array("item_id" => $this->item_id, "project_id" => $this->project_id, "title" => $this->title, "itemtext" => $this->itemtext, "status" => $this->status, "type" => $this->type, "href" => $this->href));
	}

	public function getNextPage() {
		$this->db->connect();

		$query = "SELECT items.id FROM items JOIN projects ON items.project_id = projects.id WHERE projects.slug = '" . mysql_real_escape_string($this->project_slug) . "' AND items.id > '" . mysql_real_escape_string($this->item_id) . "' LIMIT 1";
		$result = mysql_query($query) or die ("Couldn't run: $query");

		if (mysql_numrows($result)) {
			$nextpage_id = trim(mysql_result($result, 0, "id"));
		}

		$this->db->close();

		return $nextpage_id;
	}
}
