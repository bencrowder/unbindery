<?php

class Server {
	private $db;

	public function Server($db) {
		$this->db = $db;
	}

	public function getProjects() {
		$projects = $this->db->getProjects();

		foreach ($projects as &$project) {
			$project["title"] = stripslashes($project["title"]);
			$project["author"] = stripslashes($project["author"]);
		}

		return $projects;
	}

	public function getCompletedProjects() {
		return $this->db->getCompletedProjects();
	}

	public function getTopUsers() {
		return $this->db->getTopUsers();
	}

	public function decrementTardies() {
		$users = array();

		$results = $this->db->getUserPointsLost();

		foreach ($results as $result) {
			array_push($users, array("username" => $result["username"], "points_lost" => $result["points_lost"]));
		}

		foreach ($users as $user) {
			$points = intval($user["points_lost"]) * 10;

			$this->db->setUserScore($user['username'], $points);
		}
	}

	public function emailTardies() {
		$results = $this->db->getLateUserEmails();

		$app_url = Settings::getProtected('app_url');
		$email_subject = Settings::getProtected('email_subject');

		foreach ($results as $result) {
			$message = "Just a reminder that you have an item due tomorrow. If you finish it before the deadline, you won't lose any points.\n";
			$message .= "\n";
			$message .= "Edit link: $app_url/edit/" . $result["slug"] . "/" . $result["item_id"] . "\n";
			$message .= "\n";
			$message .= "Thanks! We appreciate your help.";
			
			$subject = "$email_subject Assignment '" . $result["title"] . "' due tomorrow (" . $result["deadline"] . ")";

			Mail::sendMessage($result["email"], $subject, $message);

			echo "\n\nSent email to " . $result["email"] . " about " . $result["slug"] . "/" . $result["item_id"];
		}
	}

	public function getCurrentAssignments() {
		return $this->db->GetCurrentAssignments();
	}
}
