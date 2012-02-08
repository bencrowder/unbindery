<?php

class WebServiceHandlers {
	static public function saveItemTranscriptHandler($args) {
		$siteroot = Settings::getProtected('siteroot');
		$db = Settings::getProtected('db');

		// Make sure the user is authenticated and that we can save this

		// Get info from POST
		$item_id = (array_key_exists('item_id', $_POST)) ? $_POST['item_id'] : '';
		$project_slug = (array_key_exists('project_slug', $_POST)) ? $_POST['project_slug'] : '';
		$username = (array_key_exists('username', $_POST)) ? $_POST['username'] : '';
		$review_username = (array_key_exists('review_username', $_POST)) ? $_POST['review_username'] : '';
		$itemtext = (array_key_exists('itemtext', $_POST)) ? $_POST['itemtext'] : '';

		// convert to boolean
		$draft = (array_key_exists('draft', $_POST) && $_POST['draft'] == 'true') ? true : false;
		$review = (array_key_exists('review', $_POST) && $_POST['review'] == 'true') ? true : false;

		if ($item_id && $project_slug && $username) {
			$item = new Item();
			$item->load($item_id, $project_slug, $username);
			$status = $item->saveText($username, $draft, $review, $review_username, $itemtext);
			echo json_encode(array("statuscode" => $status));
		} else {
			echo json_encode(array("statuscode" => "error"));
		}
	}

	static public function getNewPageHandler($args) {
		$siteroot = Settings::getProtected('siteroot');
		$db = Settings::getProtected('db');

		$username = $_POST['username'];
		$project_slug = $_POST['project_slug'];

		if ($username && $project_slug) {
			$user = new User($username);
			$result = $user->getNewPage($project_slug);

			echo json_encode($result);
		}
	}

	static public function addPagesHandler($args) {
		$siteroot = Settings::getProtected('siteroot');
		$db = Settings::getProtected('db');

		$project_slug = $_POST['project_slug'];
		$pages = $_POST['pages'];

		if ($project_slug && $pages) {
			$project = new Project($project_slug);
			$result = $project->addPages($pages);

			echo json_encode($result);
		} else {
			echo json_encode(array("statuscode" => "error", "slug" => $project_slug, "pages" => $pages));
		}
	}
}

?>
