<?php

class Item {
	private $db;

	private $item_id;
	private $project_id;

	private $title;

	private $itemtext;

	private $status;

	private $type;
	private $href;

	private $width;
	private $height;
	private $length;

	public function Item($db) {
		$this->db = $db;
	}

	public function __set($key, $val) {
		$this->$key = $val;
	}

	public function __get($key) {
		return $this->$key;
	}

	public function load($item_id, $project_slug, $username = "") {
		$this->db->connect();

		$query = "SELECT * FROM items JOIN projects ON items.project_id = projects.id WHERE items.id = " . mysql_real_escape_string($item_id) . " AND projects.slug = '" . mysql_real_escape_string($project_slug) . "'";
		$result = mysql_query($query) or die ("Couldn't run: $query");

		if (mysql_numrows($result)) {
			$this->item_id = trim(mysql_result($result, 0, "id"));
			$this->project_id = trim(mysql_result($result, 0, "project_id"));
			$this->title = trim(mysql_result($result, 0, "title"));
			$this->itemtext = trim(mysql_result($result, 0, "itemtext"));
			$this->status = trim(mysql_result($result, 0, "status"));
			$this->type = trim(mysql_result($result, 0, "type"));
			$this->href = trim(mysql_result($result, 0, "href"));
			$this->width = trim(mysql_result($result, 0, "width"));
			$this->height = trim(mysql_result($result, 0, "height"));
			$this->length = trim(mysql_result($result, 0, "length"));
		}

		// Update the item text with the user's revision, if available
		if ($username != '') {
			$query = "SELECT itemtext FROM texts WHERE item_id=" . mysql_real_escape_string($item_id) . " AND project_id=" . mysql_real_escape_string($this->project_id) . " AND user='" . mysql_real_escape_string($username) . "'";
			$result = mysql_query($query) or die ("Couldn't run: $query");

			if (mysql_numrows($result)) {
				$this->itemtext = trim(mysql_result($result, 0, "itemtext"));
			}
		}

		$this->db->close();
	}

	public function saveText($username, $draft, $itemtext) {
		echo "saveText";
		$this->db->connect();

		// check and see if we already have a draft
		$query = "SELECT itemtext FROM texts WHERE item_id=" . mysql_real_escape_string($this->item_id) . " AND project_id=" . mysql_real_escape_string($this->project_id) . " AND user='" . mysql_real_escape_string($username) . "'";
		$result = mysql_query($query) or die ("Couldn't run: $query");

		if (mysql_numrows($result)) {
			$existing_draft = true;
		} else {
			$existing_draft = false;
		}

		if ($draft) { 
			$status = "draft";
		} else {
			$status = "finished";
		}

		if ($existing_draft) {
			// update texts with $draft status
			$query = "UPDATE texts SET itemtext = '" . mysql_real_escape_string($itemtext) . "', date = NOW(), status = '" . mysql_real_escape_string($status) . "' WHERE item_id=" . mysql_real_escape_string($this->item_id) . " AND project_id=" . mysql_real_escape_string($this->project_id) . " AND user='" . mysql_real_escape_string($username) . "'";
			echo $query;
			$result = mysql_query($query) or die ("Couldn't run: $query");
		} else {
			// insert into texts with $draft status
			$query = "INSERT INTO texts (project_id, item_id, user, date, itemtext, status) VALUES (" . mysql_real_escape_string($this->project_id) . ", " . mysql_real_escape_string($this->item_id) . ", '" . mysql_real_escape_string($username) . "', NOW(), '" . mysql_real_escape_string($itemtext) . "', '" . mysql_real_escape_string($status) . "')";
			echo $query;
			$result = mysql_query($query) or die ("Couldn't run: $query");
		}

		if ($draft == false) {
			// we're finished with this item
			// update user score
			// change item status (if # revisions >= # project revisions, change status to closed)
		}

		$this->db->close();

		return "success";
	}


	public function getJSON() {
		return json_encode(array("item_id" => $this->item_id, "project_id" => $this->project_id, "title" => $this->title, "itemtext" => $this->itemtext, "status" => $this->status, "type" => $this->type, "href" => $this->href, "width" => $this->width, "height" => $this->height, "length" => $this-length));
	}
}
