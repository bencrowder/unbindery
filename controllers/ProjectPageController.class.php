<?php

class ProjectPageController {

	// --------------------------------------------------
	// Projects handler
	// URL: /projects OR /users/[USER]/projects
	// Methods: GET = get list of projects
	//          POST = create new project

	static public function projects($params) {
		$format = Utils::getFormat($params['args'], 0, 2);
		$pageType = Utils::getProjectType($params['args']);

		$user = User::getAuthenticatedUser();

		switch ($params['method']) {
			// GET: Get list of projects
			case 'GET':
				$userProjects = Project::getActiveProjectsForUser($user->username);
				$userProjectSlugList = array();
				foreach ($userProjects as $project) {
					array_push($userProjectSlugList, $project['slug']);
				}

				// Get available projects
				$owner = '';
				if ($pageType == 'system') {
					$projectsList = Project::getAvailableProjects($user->username);
					$completedProjects = Project::getPublicCompletedProjects('', true);
				} else {
					// Get specified user's available projects
					$owner = $params['args'][1];
					$projectsList = Project::getAvailableProjects($user->username, $owner);
					$completedProjects = Project::getPublicCompletedProjects($owner, true);
				}

				// If it's in the userProjects list, don't include it in the available list
				$availableProjects = array();
				foreach ($projectsList as $project) {
					if ($pageType == 'system') {
						if (!in_array($project['slug'], $userProjectSlugList)) {
							array_push($availableProjects, $project);
						}
					} else {
						array_push($availableProjects, $project);
					}
				}

				$pageTitle = 'Projects';
				if ($owner) $pageTitle .= " owned by $owner";

				$response = array(
					'page_title' => $pageTitle,
					'user' => $user->getResponse(),
					'user_projects' => $userProjects,
					'available_projects' => $availableProjects,
					'completed_projects' => $completedProjects,
					'type' => $pageType,
					'owner' => $owner,
				);

				switch ($format) {
					case 'json':
						echo json_encode($response);
						break;
					case 'html':
						Template::render('projects', $response);
						break;
				}

				break;

			// POST: Create new project
			// Required parameters:
			// - name (string)
			// - type (public/private)
			// - owner (string)
			case 'POST':
				// Verify the POST elements
				// See what type it is (public or private)
				// Verify that the user is who they say they are
					// How are we going to do this?
				// Verify that the user can actually create the project
				// Verify that there isn't already a project with the same name/slug in that scope

				// Create the project
				$project = new Project();
				$project->title = Utils::POST('project_name');
				$project->type = Utils::POST('project_type');
				$project->public = (Utils::POST('project_public') == 'public') ? true : false;
				$project->description = Utils::POST('project_desc');
				$project->language = Utils::POST('project_lang');
				$project->workflow = Utils::POST('project_workflow');
				$project->fields = Utils::POST('project_fields');
				$project->whitelist = Utils::POST('project_whitelist');
				$project->guidelines = Utils::POST('project_guidelines');
				$project->owner = Utils::POST('project_owner');
				$project->characters = Utils::POST('project_characters');
				$project->status = 'pending';

				// Import the system download template
				$project->downloadTemplate = Settings::getProtected('download_template');

				// Convert to lowercase and strip out punctuation
				$project->slug = str_replace(' ', '-', strtolower($project->title));
				$project->slug = preg_replace('/[^a-z0-9-]+/i', '', $project->slug);

				// And add it to the database
				$status = $project->save();

				if ($status == true) {
					switch ($project->type) {
						case 'system':
							$project->url = "projects/" . $project->slug;
							$project->admin_url = "projects/" . $project->slug . "/admin";
							break;
						case 'user':
							$project->url = "users/" . $project->owner . "/projects/" . $project->slug;
							$project->admin_url = "users/" . $project->owner . "/projects/" . $project->slug . "/admin";
							break;
					}
				}

				$response = array(
					"code" => $status,
					"project" => array(
						"url" => $project->url,
						"admin_url" => $project->admin_url
					)
				);

				switch ($format) {
					case 'json':
						// Return JSON
						$response["project"]["url"] .= ".json";
						$response["project"]["admin_url"] .= ".json";
						echo json_encode($response);
						break;

					case 'html':
						// Return HTML

						if ($status) {
							$app_url = Settings::getProtected('app_url');

							header("Location: $app_url/{$project->admin_url}");
						} else {
							echo "Error";
						}
						break;
				}

				break;
		}
	}


	// --------------------------------------------------
	// New project handler
	// URL: /projects/new-project OR /users/[USER]/projects/new-project
	// Methods: GET = get new project page
	// Formats: HTML

	static public function newProject($params) {
		// Parse parameters
		$format = Utils::getFormat($params['args'], 0, 1);
		$projectType = Utils::getProjectType($params['args']);

		// Authenticate
		$user = User::getAuthenticatedUser();

		// Creators can add user projects; admins can add both user and system projects
		$requiredRole = ($projectType == 'system') ? 'admin' : 'creator';

		// Get the current user's role and make sure they can access this page
		$roleManager = new Role();
		$roleManager->forceClearance(array('role' => "user:$requiredRole", 'user' => $user));

		// Output data
		switch ($params['method']) {
			// GET: Get new project page
			case 'GET':
				$options = array(
					'user' => $user->getResponse(),
					'project' => array(
						'type' => $projectType,
					),
				);

				Template::render('new_project', $options);
				break;
		}
	}


	// --------------------------------------------------
	// Project page handler
	// URL: /projects/[PROJECT] or /users/[USER]/projects/[PROJECT]
	// Methods: GET = get project info
	//          POST = save project info
	//          DELETE = delete project

	static public function projectPage($params) {
		$format = Utils::getFormat($params['args'], 1, 3);
		$projectSlug = (Utils::getProjectType($params['args']) == 'system') ? $params['args'][0] : $params['args'][2];

		$project = new Project($projectSlug);

		$user = User::getAuthenticatedUser();

		$isMember = $user->isMember($projectSlug);

		if ($project->numItems > 0) {
			$percentComplete = round($project->itemsCompleted / $project->numItems * 100, 0);
		} else {
			$percentComplete = 0;
		}

		// TODO: make sure current user has access to see this project

		$projectArray = $project->getResponse();
		$projectArray['percent_complete'] = $percentComplete;

		switch ($params['method']) {
			case 'GET':
				$response = array(
					'page_title' => $project->title,
					'user' => $user->getResponse($projectSlug),
					'project' => $projectArray,
					'proofers' => $project->getProoferStats('proof'),
					'reviewers' => $project->getProoferStats('review'),
				);

				switch ($format) {
					case 'json':
						echo json_encode($response);
						break;
					case 'html':
						Template::render('project', $response);
						break;
				}

				break;

			case 'POST':
				$project = new Project(Utils::POST('projectSlug'));

				$project->title = Utils::POST('projectName');
				$project->type = Utils::POST('projectType');
				$project->public = Utils::POST('projectPublic');
				$project->description = Utils::POST('projectDesc');
				$project->language = Utils::POST('projectLang');
				$project->workflow = Utils::POST('projectWorkflow');
				$project->fields = Utils::POST('projectFields');
				$project->whitelist = Utils::POST('projectWhitelist');
				$project->guidelines = Utils::POST('projectGuidelines');
				$project->owner = Utils::POST('projectOwner');
				$project->status = Utils::POST('projectStatus');
				$project->downloadTemplate = Utils::POST('projectDownloadTemplate');
				$project->characters = Utils::POST('projectCharacters');

				// Save the changes to the database
				$status = $project->save();

				if ($status == true) {
					switch ($project->type) {
						case 'system':
							$project->url = "projects/" . $project->slug;
							$project->admin_url = "projects/" . $project->slug . "/admin";
							break;
						case 'user':
							$project->url = "users/" . $project->owner . "/projects/" . $project->slug;
							$project->admin_url = "users/" . $project->owner . "/projects/" . $project->slug . "/admin";
							break;
					}
				}

				$statusCode = ($status) ? 'success' : 'error';

				$response = array(
					"statuscode" => $statusCode,
					"project" => array(
						"url" => $project->url,
						"admin_url" => $project->admin_url
					)
				);

				$response["project"]["url"] .= ".json";
				$response["project"]["admin_url"] .= ".json";

				// Always return JSON
				echo json_encode($response);

				break;

			case 'DELETE':
				break;
		}
	}


	// --------------------------------------------------
	// Project membership handler
	// URL: /projects/[PROJECT]/membership OR /users/[USER]/projects/[PROJECT]/membership
	// Methods: POST = join project
	//          DELETE = leave project

	static public function membership($params) {
		// Parse parameters
		$format = Utils::getFormat($params['args'], 1, 3);
		$projectType = Utils::getProjectType($params['args']);
		$projectSlugIndex = ($projectType == 'system') ? 0 : 2;
		$projectSlug = $params['args'][$projectSlugIndex];

		$user = User::getAuthenticatedUser();

		switch ($params['method']) {
			// POST: join project
			case 'POST':
				// Load project
				$project = new Project($projectSlug);

				// If the project is public OR the user is on the whitelist, let them join
				if ($project->public || $project->allowedToJoin($user->username)) {
					$status = ($user->assignToProject($projectSlug)) ? 'success' : 'error';
				} else {
					$status = 'access-denied';
				}

				echo json_encode(array('status' => $status));

				break;

			case 'DELETE':
				$status = ($user->removeFromProject($projectSlug)) ? 'success' : 'error';

				echo json_encode(array('status' => $status));

				break;
		}
	}


	// --------------------------------------------------
	// Project transcript handler
	// URL: /projects/[PROJECT]/transcript OR /users/[USER]/projects/[PROJECT]/transcript
	// Methods: GET = download project transcript

	static public function transcript($params) {
		$db = Settings::getProtected('db');

		// Parse parameters
		$format = Utils::getFormat($params['args'], 1, 3);
		$projectType = Utils::getProjectType($params['args']);
		$projectSlugIndex = ($projectType == 'system') ? 0 : 2;
		$projectSlug = $params['args'][$projectSlugIndex];

		$user = User::getAuthenticatedUser();

		// TODO: make sure user has access to download this

		switch ($params['method']) {
			// GET: download project transcript
			case 'GET':
				// Load project
				$project = new Project($projectSlug);
				$project->getItems();

				$finalText = "";

				// Go through each item and get the relevant transcript
				foreach ($project->items as $item) {
					$proofTranscripts = $db->loadItemTranscripts($item['project_id'], $item['id'], "proof");
					$reviewTranscripts = $db->loadItemTranscripts($item['project_id'], $item['id'], "review");

					// If there are reviewed transcripts, get the diff of those
					if (count($reviewTranscripts) > 0) {
						if (count($reviewTranscripts) > 1) {
							$text = Transcript::diff($reviewTranscripts);
						} else {
							$text = $reviewTranscripts[0]['transcript'];
						}
					} else if (count($proofTranscripts) > 0) {
						// If there are proofed transcripts, get the diff of those
						if (count($proofTranscripts) > 1) {
							$text = Transcript::diff($proofTranscripts);
						} else {
							$text = $proofTranscripts[0]['transcript'];
						}
					} else {
						// Otherwise just get the item's original transcript
						$text = $item['transcript'];
					}

					// Get the list of proofers/reviewers as comma-separated usernames
					$stats = $db->getStatsForItem($item['id']);
					$proofers = array();
					$reviewers = array();
					foreach ($stats['proofs'] as $stat) {
						array_push($proofers, $stat['user']);
					}
					foreach ($stats['reviews'] as $stat) {
						array_push($reviewers, $stat['user']);
					}
					$proofers = join(',', $proofers);
					$reviewers = join(',', $reviewers);
					
					// If there's a project template, use it, otherwise use default from config
					$defaultTemplate = Settings::getProtected("download_template");
					$template = ($project->downloadTemplate != '') ? $project->downloadTemplate : $defaultTemplate;

					// Apply the download template
					$finalText .= TranscriptController::replaceVariables($template, array(
							'transcript' => $text,
							'project' => $project,
							'item' => $item,
							'proofers' => $proofers,
							'reviewers' => $reviewers,
						)
					);
				}

				switch ($format) {
					case 'json':
						echo json_encode(array('transcript' => htmlentities($finalText)));
						break;

					case 'html':
						$filename = "{$project->slug}.txt";

						header("Content-Type: text/html");
						header("Content-Disposition: attachment; filename=$filename");

						echo trim(str_replace('\n', "\n", $finalText));

						break;
				}

				break;
		}
	}


	// --------------------------------------------------
	// Split transcript handler
	// URL: /projects/[PROJECT]/transcript/split OR /users/[USER]/projects/[PROJECT]/transcript/split
	// Methods: POST = send in transcript and template, get split array in return

	// TODO: should this move somewhere else? It's not project-specific...
	static public function splitTranscript($params) {
		switch ($params['method']) {
			// POST: split transcript
			case 'POST':
				$template = Utils::POST("template");
				$transcript = Utils::POST("transcript");

				$splitTranscripts = TranscriptController::splitTranscript($transcript, $template);

				echo json_encode(array('status' => 'success', 'transcripts' => $splitTranscripts));

				break;
		}
	}


	// --------------------------------------------------
	// Project admin handler
	// URL: /projects/[PROJECT]/admin OR /users/[USER]/projects/[PROJECT]/admin
	// Methods: GET = show admin page

	static public function admin($params) {
		$i18n = Settings::getProtected('i18n');
		$appUrl = Settings::getProtected('app_url');
		$themeRoot = Settings::getProtected('theme_root');

		$format = Utils::getFormat($params['args'], 1, 3);
		$projectType = Utils::getProjectType($params['args']);
		$projectSlug = ($projectType == 'system') ? $params['args'][0] : $params['args'][2];

		$user = User::getAuthenticatedUser();

		// TODO: Verify clearance

		// Load the project
		$project = new Project($projectSlug);

		if ($project->title == '') {
			Utils::redirectToDashboard('', $i18n->t("error.loading_project"));
		}

		if ($project->type == 'system') {
			$projectUrl = "projects/" . $project->slug;
		} else if ($project->type == 'user') {
			$projectUrl = "users/" . $project->owner . "/projects/" . $project->slug;
		}

		$project->getItems();

		$projectArray = $project->getResponse();
		$projectArray['items'] = $project->items;
		$projectArray['url'] = "$appUrl/$projectUrl";

		switch ($params['method']) {
			// GET: Get new project page
			case 'GET':
				$response = array(
					'page_title' => 'Project Admin',
					'user' => $user->getResponse(),
					'project' => $projectArray,
					'css' => array(
						'uploadify.css'
					),
					'sysjs' => array(
						'uploadify/swfobject.js',
						'uploadify/jquery.uploadify.v2.1.4.min.js'
					),
					'jsinclude' => "$(document).ready(function() {
							var fileList = [];
							$('#file_upload').uploadify({
								'uploader'  : '$appUrl/js/uploadify/uploadify.swf',
								'cancelImg' : '$appUrl/js/uploadify/cancel.png',
								'script'    : '$appUrl/$projectUrl/upload',
								'fileDataName' : 'items',
								'removeCompleted' : false,
								'fileTypeExts'  : '*.jpg; *.jpeg; *.gif; *.png; *.mp3; *.mp4; *.wav;',
								'multi'     : true,
								'auto'      : true,
								'onComplete' : function(event, ID, fileObj, response, data) {
									fileList.push(fileObj.name);
								},
								'onAllComplete' : function(event, data) {
									unbindery.addItemsToProject(fileList);
								}
							});
						});",
				);

				switch ($format) {
					case 'json':
						echo json_encode($response);
						break;

					case 'html':
						Template::render('project_admin', $response);
						break;
				}

				break;
		}
	}
	

	// --------------------------------------------------
	// Upload items handler
	// URL: /projects/PROJECT/upload or /users/USER/projects/PROJECT/upload
	// Methods: POST

	static public function upload($params) {
		$format = Utils::getFormat($params['args'], 0, 2);
		$projectType = Utils::getProjectType($params['args']);

		$projectSlugIndex = ($projectType == 'system') ? 0 : 2;
		$projectSlug = $params['args'][$projectSlugIndex];

		switch ($params['method']) {
			// POST: Upload file handler
			case 'POST':
				Media::moveUploadedFilesToTempDir($projectSlug);
				break;
		}
	}


	// --------------------------------------------------
	// Project import transcript handler
	// URL: /projects/[PROJECT]/import OR /users/[USER]/projects/[PROJECT]/import
	// Methods: GET = show transcript import page
	//          POST = save imported transcript

	static public function import($params) {
		$appUrl = Settings::getProtected('app_url');
		$themeRoot = Settings::getProtected('theme_root');

		$format = Utils::getFormat($params['args'], 1, 3);
		$projectType = Utils::getProjectType($params['args']);
		$projectSlug = ($projectType == 'system') ? $params['args'][0] : $params['args'][2];

		$user = User::getAuthenticatedUser();

		// TODO: Verify clearance

		// Load the project
		$project = new Project($projectSlug);

		if ($project->title == '') {
			Utils::redirectToDashboard('', 'Error loading project.');
		}

		if ($project->type == 'system') {
			$projectUrl = "projects/" . $project->slug;
		} else if ($project->type == 'user') {
			$projectUrl = "users/" . $project->owner . "/projects/" . $project->slug;
		}

		$project->getItems();

		$projectArray = $project->getResponse();
		$projectArray['items'] = $project->items;
		$projectArray['url'] = "$appUrl/$projectUrl";

		switch ($params['method']) {
			// GET: Get transcript import page
			case 'GET':
				$response = array(
					'page_title' => 'Import Transcript',
					'user' => $user->getResponse(),
					'project' => $projectArray,
				);

				switch ($format) {
					case 'json':
						echo json_encode(array('status' => 'success', 'response' => $response));
						break;

					case 'html':
						Template::render('import', $response);
						break;
				}

				break;

			// POST: Update transcripts for items
			case 'POST':
				$template = Utils::POST('template');
				$transcript = Utils::POST('transcript');
				$items = Utils::POST('items');
				$projectSlug = Utils::POST('projectSlug');

				$status = 'success';

				// Split the transcript
				$splitTranscripts = TranscriptController::splitTranscript($transcript, $template);

				// Make sure the number of items still matches, otherwise return error
				if (count($splitTranscripts) != count($items)) {
					$status = 'error';
				}

				// Update each item's transcript
				for ($i=0; $i<count($items); $i++) {
					$item = new Item($items[$i], $projectSlug);
					$item->transcript = $splitTranscripts[$i];

					if (!$item->save()) {
						$status = 'error';
						break;
					}
				}

				echo json_encode(array('status' => $status));

				break;
		}
	}
}

?>
