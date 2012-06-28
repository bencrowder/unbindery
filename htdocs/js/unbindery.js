/* Unbindery */
/* -------------------------------------------------- */

var Unbindery = function() {
	this.redirectToDashboard = function(message, error, username) {
		var locStr = app_url + "/users/" + username + "/dashboard";

		if (message || error) locStr += "?";

		if (message) locStr += "message=" + message;

		if (error) {
			if (message) locStr += "&";
			locStr += "error=" + error;
		}

		window.location.href = locStr;
	};

	this.callAPI = function(call, method, data, callback) {
		// Prepare the URL
		switch (call) {
			case 'get-new-item':
				if (data.projectType == 'public') {
					url = '/projects/' + data.projectSlug + '/items/get';
				} else {
					url = '/users/' + data.projectOwner + '/projects/' + data.projectSlug + '/items/get';
				}
				break;
			case 'save-transcript':
				if (data.projectType == 'public') {
					url = '/projects/' + data.projectSlug + '/items/' + data.itemId + '/transcript';
				} else {
					url = '/users/' + data.projectOwner + '/projects/' + data.projectSlug + '/items/' + data.itemId + '/transcript';
				}
				break;
		}

		if (method == 'POST') { 
			$.post(app_url + url, data, callback, 'json');
		} else if (method == 'GET') {
			$.get(app_url + url, data, callback, 'json');
		}
	}

	this.showSpinner = function() {
		$("#spinner").show();
	}

	this.hideSpinner = function() {
		$("#spinner").hide();
	}

	this.getNewItem = function(projectSlug, projectOwner, projectType) {
		// Get the username
		var username = $("#username").html();

		this.callAPI('get-new-item', 'POST', { projectSlug: projectSlug, projectOwner: projectOwner, projectType: projectType, username: username },
			function(data) {
				if (data.status == true) {
					if (projectType == 'public') {
						var locStr = app_url + '/projects/' + projectSlug + '/items/' + data.code + '/proof';
					} else {
						var locStr = app_url + '/users/' + projectOwner + '/projects/' + projectSlug + '/items/' + data.code + '/proof';
					}

					window.location.href = locStr;
				} else {
					unbindery.hideSpinner();
					console.log(data);
					console.log("failure", data.code);

/*					switch (data.code) {
						case "waiting_for_clearance":
							redirect_to_dashboard("", "Your first page has to be approved before you can proof more pages. (Just this once, though.)", username);
							break;	
						case "have_item_already":
							redirect_to_dashboard("", "You already have one page for this project. Finish it and then you'll be able to get a new one.", username);
						case "not_a_member":
							redirect_to_dashboard("", "You're not a member of that project.", username);
							break;
						default:
							redirect_to_dashboard("", "Error getting new page.", username);
							break;

					}
					*/
				}
			}
		);

		return false;
	};

	this.saveTranscript = function(isDraft, isReview, continueSlug) {
		unbindery.showSpinner();

		var itemId = $("#item_id").val();
		var projectSlug = $("#project_slug").val();
		var username = $("#username").html();
		var reviewUsername = $("#review_username").html();
		var transcript = $("#transcript").val();
		var projectOwner = $("#project_owner").val();

		var projectType = 'public';
		if (projectOwner != '') projectType = 'private';

		var status = 'completed';
		if (isDraft) status = 'draft';
		if (isReview) status = 'reviewed';

		unbindery.callAPI('save-transcript', 'POST', { itemId: itemId, projectSlug: projectSlug, projectOwner: projectOwner, projectType: projectType, username: username, draft: isDraft, review: isReview, reviewUsername: reviewUsername, transcript: transcript, status: status },
			function(data) {
				if (data.statuscode == "success") {
					unbindery.hideSpinner();

					if (isReview) {
						var message = "Finished review.";
					} else {
						if (isDraft) {
							var message = "Saved draft.";
						} else {
							var message = "Finished item.";
						}
					}

					if (continueSlug != '') {
						console.log("Get new page", continueSlug);
						//get_new_page(slug);
					} else {
						console.log("Redirect to dashboard");
						unbindery.redirectToDashboard(message, "", username);
					}
				} else {
					console.log("Error");
					// don't redirect to dashboard here, show error thing
					// redirect_to_dashboard("", "Error saving page. Try again.");
				}
			});
	};
}

function save_page_text(is_draft, is_review, slug) {
	unbindery.showSpinner();

	var item_id = $("#item_id").val();
	var project_slug = $("#project_slug").val();
	var transcript;

	if (editbox == "simple") {
		transcript = $("#page_text").val();						// textarea
	} else {
		transcript = editor.session.doc.$lines.join('\n');		// Ace
	}
	var username = $("#username").html();
	var review_username = $("#review_username").html();

	unbindery.callAPI("save_item_transcript", 'POST', { item_id: item_id, project_slug: project_slug, username: username, draft: is_draft, review: is_review, review_username: review_username, transcript: transcript },
		function(data) {
			if (data.statuscode == "success") {
				unbindery.hideSpinner();

				if (is_review) {
					var message = "Finished review.";
				} else {
					if (is_draft) {
						var message = "Saved draft.";
					} else {
						var message = "Finished item.";
					}
				}
				if (slug != '') {
					get_new_page(slug);
				} else {
					redirect_to_dashboard(message, "", username);
				}
			} else {
				// don't redirect to dashboard here, show error thing
				// redirect_to_dashboard("", "Error saving page. Try again.");
			}
		}
	);
}

function save_page() {
	unbindery.showSpinner();

	var item_id = $("#item_id").val();
	var project_slug = $("#project_slug").val();
	var pagetext = $("#pagetext").val();
	var username = $("#username").html();
	var review_username = $("#review_username").html();

	unbindery.callAPI(app_url + "/unbindery.php?method=save_page", 'POST', { item_id: item_id, project_slug: project_slug, username: username, draft: is_draft, review: is_review, review_username: review_username, transcript: transcript },
		function(data) {
			if (data.statuscode == "success") {
				unbindery.hideSpinner();

				if (is_review) {
					var message = "Finished review.";
				} else {
					if (is_draft) {
						var message = "Saved draft.";
					} else {
						var message = "Finished item.";
					}
				}
				redirect_to_dashboard(message, "", username);
			} else {
				redirect_to_dashboard("", "Error saving page. Try again.", username);
			}
		}
	);
}

function load_items_for_editing(event, data) {
	var pages = [];
	var project_slug = $("#project_slug").val();

	pages = '';
	$("#file_uploadQueue .fileName").each(function() {
		var filename = $(this).html();
		// strip up to the space
		filename = filename.substr(0, filename.indexOf(' '));

		pages += filename + '|';
	});

	// add them to the database
	unbindery.callAPI("add_pages", 'POST', { project_slug: project_slug, pages: pages },
		function(data) {
			if (data.statuscode == "success") {
				// load the first page into edit mode
				var firstpage = data.page_ids[0];
				window.location.href = app_url + '/admin/new_page/' + project_slug + '/' + firstpage;
			} else {
				console.log("error!");
			}
		}
	);
}

$(document).ready(function() {
	unbindery = new Unbindery();

	/* New Project page */
	/* -------------------------------------------------- */
	
	$("form#new_project_form select#project_type").on("change", function() {
		if ($(this).val() == "Public") {
			// Change the action
			$("form#new_project_form").attr("action", app_url + "/projects");

			if ($("#step_4:visible").length != 0) {
				$("#step_4").fadeOut(100);
			}
		} else if ($(this).val() == "Private") {
			// Change the action
			$("form#new_project_form").attr("action", app_url + "/users/" + username + "/projects");

			if ($("#step_4:visible").length == 0) {
				$("#step_4").fadeIn(100);
			}
		}
	});

	$("form#new_project_form #step_2 input[type=button]").on("click", function() {
		newFieldName = $(this).siblings("#new_field_name").val().trim();
		newFieldType = $(this).siblings("#new_field_type").val().trim();

		console.log("Here", newFieldName, newFieldType);

		if (newFieldName != '' && newFieldType != '') {
			newFieldHTML = "<li><a class='delete'>x</a><label>";
			newFieldHTML += newFieldName;
			newFieldHTML += "</label><input type='text' value='";
			newFieldHTML += newFieldType;
			newFieldHTML += "' /></li>";

			$(newFieldHTML).appendTo($(this).siblings("ul"));

			$(this).siblings("#new_field_name").val('');	
			$(this).siblings("#new_field_type").val('');	
		}

		return false;
	});

	$("form#new_project_form #step_2").on("click", "a.delete", function() {
		$(this).parents("li:first").fadeOut(100, function() {
			$(this).remove();
		});

		return false;
	});

	// Focus on the transcript textarea
	$("textarea#transcript").focus();

	// Click handlers for the buttons
	$("#action-save-draft").click(function() {
		unbindery.saveTranscript(true, false, '');		// yes draft, no review, don't get another
	});

	$("#action-finish").click(function(e) {
		unbindery.saveTranscript(false, false, '');		// no draft, no review, don't get another
	});

	$("#action-finish-continue").click(function(e) {
		var project_slug = $("#project_slug").val();
		unbindery.saveTranscript(false, false, project_slug); // no draft, no review, do get another one
	});

	$("#action-finish-review").click(function(e) {
		unbindery.saveTranscript(false, true, '');		// no draft, yes review
	});

	$("#action-save-item").click(function(e) {
		save_page();
	});

	// Set up click handler for getting a new item
	$(".getnewitem").click(function(e) {
		// Hide the button (so we don't click it again)
		$(this).hide();

		// Show the spinner
		$(this).siblings('.spinner').show();

		// And get the new page
		var projectSlug = this.getAttribute('data-project-slug');
		var projectOwner = this.getAttribute('data-project-owner');
		var projectType = this.getAttribute('data-project-type');

		unbindery.getNewItem(projectSlug, projectOwner, projectType);
	});
});
