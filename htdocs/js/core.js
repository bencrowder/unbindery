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
				if (data.projectType == 'system') {
					url = '/projects/' + data.projectSlug + '/items/get';
				} else {
					url = '/users/' + data.projectOwner + '/projects/' + data.projectSlug + '/items/get';
				}
				break;

			case 'save-transcript':
				if (data.projectType == 'system') {
					url = '/projects/' + data.projectSlug + '/items/' + data.itemId + '/transcript';
				} else {
					url = '/users/' + data.projectOwner + '/projects/' + data.projectSlug + '/items/' + data.itemId + '/transcript';
				}
				break;

			case 'save-project':
				if (data.projectType == 'system') {
					url = '/projects/' + data.projectSlug;
				} else {
					url = '/users/' + data.projectOwner + '/projects/' + data.projectSlug;
				}
				break;

			case 'add-items':
				if (data.projectType == 'system') {
					url = '/projects/' + data.projectSlug + '/items';
				} else {
					url = '/users/' + data.projectOwner + '/projects/' + data.projectSlug + '/items';
				}
				break;

			case 'delete-item':
				if (data.projectType == 'system') {
					url = '/projects/' + data.projectSlug + '/items/' + data.itemId + '/delete';
				} else {
					url = '/users/' + data.projectOwner + '/projects/' + data.projectSlug + '/items/' + data.itemId + '/delete';
				}

				break;
		}

		switch (method) {
			case 'POST':
				$.post(app_url + url, data, callback, 'json');
				break;

			case 'GET':
				$.get(app_url + url, data, callback, 'json');
				break;
		}
	};

	this.showSpinner = function(id) {
		if (typeof id === 'undefined') id = '#spinner';

		$(id).show();
	};

	this.hideSpinner = function(id) {
		if (typeof id === 'undefined') id = '#spinner';

		$(id).hide();
	};

	this.getNewItem = function(projectSlug, projectOwner, projectType, actionType) {
		// Get the username
		var username = $("#username").html();

		this.callAPI('get-new-item', 'POST', { projectSlug: projectSlug, projectOwner: projectOwner, projectType: projectType, username: username, type: actionType },
			function(data) {
				if (data.status == true) {
					if (projectType == 'system') {
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
		var transcript = $("#transcript").val();
		var projectOwner = $("#project_owner").val();
		var proofUser = $("#proof_user").val();
		var proofType = $("#proof_type").val();

		var projectType = 'system';
		if (projectOwner != '') projectType = 'user';

		var status = 'completed';
		if (isDraft) status = 'draft';
		if (isReview) status = 'reviewed';

		unbindery.callAPI('save-transcript', 'POST', { itemId: itemId, projectSlug: projectSlug, projectOwner: projectOwner, projectType: projectType, username: username, draft: isDraft, proofType: proofType, proofUser: proofUser, transcript: transcript, status: status },
			function(data) {
				if (data.statuscode == "success") {
					if (getAnother) {
						// And get the new item
						var projectSlug = $('#project_slug').val();
						var projectOwner = $('#project_owner').val();
						var projectType = $('#project_type').val();

						unbindery.getNewItem(projectSlug, projectOwner, projectType, proofType);
					} else {
						unbindery.redirectToDashboard("", "");
					}

					unbindery.hideSpinner();
				} else {
					unbindery.redirectToDashboard("", "Error saving transcript. Try again.");
				}
			});
	};

	this.saveProject = function() {
		unbindery.showSpinner();

		var projectSlug = $("#project_slug").val();
		var projectType = $("#project_type").val();
		var projectOwner = $("#project_owner").val().trim();
		var projectName = $("#project_name").val().trim();
		var projectStatus = $("#project_status").val().trim();
		var projectPublic = ($("#project_public").val() == 'public') ? true : false;
		var projectDesc = $("#project_desc").val().trim();
		var projectLang = $("#project_lang").val().trim();
		var projectWhitelist = ($("#project_whitelist").length > 0) ? $("#project_whitelist").val().trim() : '';
		var projectWorkflow = $("#project_workflow").val().trim();
		var projectDownloadTemplate = $("#project_download_template").val().trim();
		
		// TODO: add fields

		unbindery.callAPI('save-project', 'POST', { projectSlug: projectSlug, projectType: projectType, projectOwner: projectOwner, projectName: projectName, projectStatus: projectStatus, projectPublic: projectPublic, projectDesc: projectDesc, projectLang: projectLang, projectWhitelist: projectWhitelist, projectWorkflow: projectWorkflow, projectDownloadTemplate: projectDownloadTemplate },
			function(data) {
				if (data.statuscode == "success") {
					unbindery.hideSpinner();
				} else {
					unbindery.redirectToDashboard("", "Error saving transcript. Try again.");
				}
			});
	};

	this.addItemsToProject = function(fileList) {
		var projectSlug = $("#project_slug").val();
		var projectType = $("#project_type").val();

		unbindery.showSpinner('#uploadspinner');

		this.callAPI('add-items', 'POST', { projectSlug: projectSlug, projectType: projectType, fileList: fileList },
			function(data) {
				if (data.status == 'success') {
					unbindery.hideSpinner('#uploadspinner');

					// if success, add items to item list
					for (item in data.items) {
						item = data.items[item];

						html = "<li class='new available " + item.type + "' data-id='" + item.id + "'>";
						html += "<span class='itemcontrols'>";
						html +=		"<a href='' class='delete'>×</a>";
						html += "</span>";
						html += "<a href='/items/" + item.id + "'>" + item.title + "</a>";
						html += " <span class='status'>available</span>";
						html += "</li>";

						$(html).appendTo($("section.items ul.items"));
					}
				}
			}
		);
	};

	this.deleteItem = function(itemId) {
		var projectSlug = $("#project_slug").val();
		var projectType = $("#project_type").val();

		this.callAPI('delete-item', 'POST', { projectSlug: projectSlug, projectType: projectType, itemId: itemId },
			function(data) {
				if (data.status == 'success') {
					$("ul.items li[data-id=" + itemId + "]").remove();
				}
			}
		);
	};
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

	// Project admin form
	$("#action-save-project").click(function(e) {
		unbindery.saveProject();
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


	/* Project page (joining/leaving) */
	/* -------------------------------------------------- */

	$('.proj_details .membership a').click(function() {
		// Find out whether we're joining or leaving the project
		var action = ($(this).hasClass('join')) ? 'POST' : 'DELETE';

		// And the URL to call (same for joining/leaving)
		var url = $(this).attr('href');

		var message = (action == 'POST') ? 'You are now a member of this project.' : 'You have left this project.';

		// call URL with appropriate method
		$.ajax({
			url: url,
			type: action,
			dataType: 'json',
			success: function(data) {
				$(".proj_details .membership a").slideUp(50, function() {
					$("<div class='" + data.status + "'>" + message + "</div>").appendTo(".proj_details .membership");	
				});
			},
			error: function(data) {
				$(".proj_details .membership a").slideUp(50, function() {
					$("<div class='error'>Error connecting to web service. Contact the administrator.</div>").appendTo(".proj_details .membership");	
				});
			}
		});

		return false;
	});


	/* Item deletion (project admin page) */
	/* -------------------------------------------------- */

	$("ul.items").on("click", "a.delete", function() {
		var itemId = $(this).parents("li:first").attr("data-id");

		unbindery.deleteItem(itemId);
		
		return false;
	});
});
