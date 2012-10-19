/* Unbindery */
/* -------------------------------------------------- */

var Unbindery = function() {
	this.redirectToDashboard = function(message, error) {
		var locStr = app_url + "/users/" + username + "/dashboard";

		// Set the message/error session variables before we redirect
		$.post(app_url + "/messages", { message: message, error: error }, function() {
			window.location.href = locStr;
		});
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

	this.getNewItem = function(projectSlug, projectOwner, projectType, actionType) {
		// Get the username
		var username = $("#username").html();

		this.callAPI('get-new-item', 'POST', { projectSlug: projectSlug, projectOwner: projectOwner, projectType: projectType, username: username, type: actionType },
			function(data) {
				if (data.status == true) {
					if (projectType == 'public') {
						var locStr = app_url + '/projects/' + projectSlug + '/items/' + data.code + '/' + actionType;
					} else {
						var locStr = app_url + '/users/' + projectOwner + '/projects/' + projectSlug + '/items/' + data.code + '/' + actionType;
					}

					window.location.href = locStr;
				} else {
					unbindery.hideSpinner();

					switch (data.code) {
						case "not-authenticated-as-correct-user":
							unbindery.redirectToDashboard("", "You're not the user you say you are.");
							break;
						case "not-cleared":
							unbindery.redirectToDashboard("", "Your first item has to be approved before you can proof more items. (Just this once, though.)");
							break;	
						case "not-a-member":
							unbindery.redirectToDashboard("", "You're not a member of that project.");
							break;
						case "has-unfinished-item":
							unbindery.redirectToDashboard("", "You already have an item for this project. Finish it and then you'll be able to get a new one.");
						case "no-item-available":
							unbindery.redirectToDashboard("", "There aren't any more items available to you for that project.");
						default:
							unbindery.redirectToDashboard("", "Error getting new item.");
							break;

					}
				}
			}
		);

		return false;
	};

	this.saveTranscript = function(isDraft, isReview, getAnother) {
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

		var type = (isReview) ? 'review' : 'proof';

		unbindery.callAPI('save-transcript', 'POST', { itemId: itemId, projectSlug: projectSlug, projectOwner: projectOwner, projectType: projectType, username: username, draft: isDraft, review: isReview, reviewUsername: reviewUsername, transcript: transcript, status: status, type: type },
			function(data) {
				if (data.statuscode == "success") {
					if (getAnother) {
						// And get the new item
						var projectSlug = $('#project_slug').val();
						var projectOwner = $('#project_owner').val();
						var projectType = $('#project_type').val();

						unbindery.getNewItem(projectSlug, projectOwner, projectType, type);
					} else {
						unbindery.redirectToDashboard("", "");
					}

					unbindery.hideSpinner();
				} else {
					unbindery.redirectToDashboard("", "Error saving transcript. Try again.");
				}
			});
	};
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
		isReview = ($("#transcript_type").val() == 'review') ? true : false;
		unbindery.saveTranscript(true, isReview, false);		// yes draft, review depends, don't get another
		return false;
	});

	$("#action-finish, #action-save-changes").click(function(e) {
		isReview = ($("#transcript_type").val() == 'review') ? true : false;
		unbindery.saveTranscript(false, isReview, false);		// no draft, review depends, don't get another
		return false;
	});

	$("#action-finish-continue").click(function(e) {
		isReview = ($("#transcript_type").val() == 'review') ? true : false;
		unbindery.saveTranscript(false, isReview, true);		// no draft, review depends, do get another one
		return false;
	});

	$("#action-save-item").click(function(e) {
		save_page();
		return false;
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
		var actionType = ($(this).parents("ul.action_list").hasClass("proof")) ? 'proof' : 'review';

		unbindery.getNewItem(projectSlug, projectOwner, projectType, actionType);
	});


	/* Install page */
	/* -------------------------------------------------- */

	$("#install_form input[type=submit]").click(function() {
		if ($(this).siblings("#username").val().trim() == '') {
			$(this).siblings("#error").show();
			return false;
		}

		if ($(this).siblings("#password").length > 0 && $(this).siblings("#password").val().trim() == '') {
			$(this).siblings("#error").show();
			return false;
		}
	});
});
