/* Unbindery */
/* -------------------------------------------------- */

var Unbindery = function() {

	// Redirect to dashboard (with optional message or error)
	// --------------------------------------------------

	this.redirectToDashboard = function(message, error) {
		var locStr = app_url + "/users/" + username + "/dashboard";

		// Set the message/error session variables before we redirect
		$.post(app_url + "/messages", { message: message, error: error }, function() {
			window.location.href = locStr;
		});
	};


	// Call the API
	// --------------------------------------------------

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
			case 'save-project-and-activate':
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

			case 'delete-user':
				url = '/users/' + data.username;
				break;

			case 'split-transcript':
				if (data.projectType == 'system') {
					url = '/projects/' + data.projectSlug + '/transcript/split';
				} else {
					url = '/users/' + data.projectOwner + '/projects/' + data.projectSlug + '/transcript/split';
				}
				break;

			case 'import-transcript':
				if (data.projectType == 'system') {
					url = '/projects/' + data.projectSlug + '/import';
				} else {
					url = '/users/' + data.projectOwner + '/projects/' + data.projectSlug + '/import';
				}
				break;

			case 'save-settings':
				url = '/users/' + data.username + '/settings';
				break;

			case 'add-user-to-project':
				if (data.projectType == 'system') {
					url = '/projects/' + data.projectSlug + '/membership';
				} else {
					url = '/users/' + data.projectOwner + '/projects/' + data.projectSlug + '/membership';
				}
				break;

			case 'remove-user-from-project':
				if (data.projectType == 'system') {
					url = '/projects/' + data.projectSlug + '/membership/leave';
				} else {
					url = '/users/' + data.projectOwner + '/projects/' + data.projectSlug + '/membership/leave';
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

			case 'DELETE':
				$.ajax({
					url: app_url + url,
					type: 'DELETE',
					dataType: 'json',
					success: callback,
					error: callback
				});
				break;
		}
	};


	// Show/hide the spinner
	// --------------------------------------------------

	this.showSpinner = function(id) {
		if (typeof id === 'undefined') id = '#spinner';

		$(id).show();
	};

	this.hideSpinner = function(id) {
		if (typeof id === 'undefined') id = '#spinner';

		$(id).hide();
	};


	// Insert a character from the character pad
	// --------------------------------------------------

	this.insertText = function(text) {
		// Insert the character into the text box
		var textbox = $("textarea#transcript");
		textbox.append(text);
	};


	// Get a new item
	// --------------------------------------------------

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

					// Set up error code
					errorCode = "error." + data.code.replace(/-/, "_"); 

					unbindery.redirectToDashboard("", errorCode);
				}
			}
		);

		return false;
	};


	// Save transcript
	// --------------------------------------------------

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

		// Get any fields and serialize them
		var fields = {};
		$("#fields_container [id^='fields_']").each(function() {
			// Get the type (text, dropdown, checkbox, radio)
			var type = ($(this).prop("tagName") == "SELECT") ? "dropdown" : $(this).attr("type");

			// And the ID, which we use as the key
			var key = $(this).attr("data-id");

			// Get the value
			var value = '';
			switch (type) {
				case 'text':
					value = $(this).val().trim();
					break;
				
				case 'dropdown':
					value = $(this).find("option:selected").val();
					break;

				case 'radio':
					value = ($(this).attr('checked')) ? $(this).val() : '';
					break;

				case 'checkbox':
					value = ($(this).attr('checked')) ? true : false;
					break;
			}
			
			// Special case for radio buttons
			if (typeof fields[key] == 'undefined' || (fields[key] == '' && value != '')) {
				fields[key] = value;
			}
		});
		
		// Save
		unbindery.callAPI('save-transcript', 'POST', { itemId: itemId, projectSlug: projectSlug, projectOwner: projectOwner, projectType: projectType, username: username, draft: isDraft, proofType: proofType, proofUser: proofUser, transcript: transcript, fields: JSON.stringify(fields), status: status },
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
					unbindery.redirectToDashboard("", "error.saving_transcript");
				}
			});
	};


	// Save project
	// --------------------------------------------------

	this.saveProject = function() {
		unbindery.showSpinner();

		var projectSlug = $("#project_slug").val();
		var projectType = $("#project_type").val();
		var projectOwner = $("#project_owner").val().trim();
		var projectName = $("#project_name").val().trim();
		var projectStatus = $("#project_status").val().trim();
		var projectPublic = ($("#project_public").val() == 'public') ? 1 : 0;
		var projectDesc = $("#project_desc").val().trim();
		var projectLang = $("#project_lang").val().trim();
		var projectWorkflow = $("#project_workflow").val().trim();
		var projectDownloadTemplate = $("#project_download_template").val().trim();
		var projectCharacters = $("#project_characters").val().trim();
		var projectFields = $("#project_fields").val().trim();
		
		unbindery.callAPI('save-project', 'POST', { projectSlug: projectSlug, projectType: projectType, projectOwner: projectOwner, projectName: projectName, projectStatus: projectStatus, projectPublic: projectPublic, projectDesc: projectDesc, projectLang: projectLang, projectWorkflow: projectWorkflow, projectDownloadTemplate: projectDownloadTemplate, projectCharacters: projectCharacters, projectFields: projectFields },
			function(data) {
				if (data.statuscode == "success") {
					unbindery.hideSpinner();
					unbindery.redirectToDashboard("admin.project.saved", "");
				} else {
					unbindery.redirectToDashboard("", "error.saving_project");
				}
			});
	};


	// Add items to a project after they've been uploaded
	// --------------------------------------------------

	this.addItemsToProject = function(fileList) {
		var projectSlug = $("#project_slug").val();
		var projectType = $("#project_type").val();

		unbindery.showSpinner('#uploadspinner');

		this.callAPI('add-items', 'POST', { projectSlug: projectSlug, projectType: projectType, fileList: fileList },
			function(data) {
				if (data.status == 'success') {
					unbindery.hideSpinner('#uploadspinner');

					// If success, add items to item list
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

					// Also remove the items from the queue
					fileList = [];
					$("#file_uploadQueue").html('');
				}
			}
		);
	};


	// Delete an item
	// --------------------------------------------------

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


	// Delete a user
	// --------------------------------------------------

	this.deleteUser = function(username) {
		this.callAPI('delete-user', 'DELETE', { username: username },
			function(data) {
				if (data.status == 'success') {
					$("ul.items.users li[data-username=" + username + "]").remove();
				}
			}
		);
	};


	// Add a user/role to a project
	// --------------------------------------------------

	this.addUserToProject = function(username, role) {
		var projectSlug = $("#project_slug").val();
		var projectType = $("#project_type").val();
		var projectOwner = $("#project_owner").val();

		this.callAPI('add-user-to-project', 'POST', { username: username, role: role, projectSlug: projectSlug, projectType: projectType, projectOwner: projectOwner },
			function(data) {
				if (data.status == 'success') {
					$("<li data-username='" + username + "' data-role='" + role + "'><span class='itemcontrols'><a href='' class='delete'>×</a></span><b>" + username + "</b>: " + role + "</li>").appendTo("ul.items.members");

					// Clear out to defaults
					$(".addbox input[type=text]").val('');
					$(".addbox select").val("proofer");
				}
			}
		);
	}


	// Remove a user/role from a project
	// --------------------------------------------------

	this.removeUserFromProject = function(username, role) {
		var projectSlug = $("#project_slug").val();
		var projectType = $("#project_type").val();
		var projectOwner = $("#project_owner").val();

		this.callAPI('remove-user-from-project', 'POST', { username: username, role: role, projectSlug: projectSlug, projectType: projectType, projectOwner: projectOwner },
			function(data) {
				if (data.status == 'success') {
					$("ul.items.members li[data-username='" + username + "'][data-role='" + role + "']").remove();
				}
			}
		);
	}


	// Update the import preview (project admin page)
	// --------------------------------------------------

	this.updateImportPreview = function() {
		var template = $("#import-template").val();
		var transcript = $("#import-transcript").val().trim();
		var projectSlug = $("#project_slug").val();
		var projectType = $("#project_type").val();
		var projectOwner = $("#project_owner").val();

		if (template == '' || transcript == '') return;

		this.callAPI('split-transcript', 'POST', { template: template, transcript: transcript, projectType: projectType, projectOwner: projectOwner, projectSlug: projectSlug },
			function(data) {
				if (data.status == 'success') {
					$("#preview-header").html("Preview (" + data.transcripts.length + " items)");
					
					var html = '';
					for (i=0; i<data.transcripts.length; i++) {
						html += "<p><label>";
						html += $(".sidebar .items li:nth-child(" + (i + 1) + ")").find("a:not(.delete)").html();
						html += ":</label> " + data.transcripts[i] + "</p>";
					}
					$("#import-preview").html(html);

					// Make sure the # of items matches
					if ($("#import-preview p").length != $(".sidebar ul.items li").length) {
						html = "<div class='error'>";
						html += "Number of items doesn't match up."; // TODO: get from translations
						html += "</div>";

						$(html).prependTo("#main.import .sidebar");
						return false;
					}
				}
			}
		);
	};


	// Import a transcript
	// --------------------------------------------------

	this.importTranscript = function() {
		var template = $("#import-template").val();
		var transcript = $("#import-transcript").val().trim();
		var projectSlug = $("#project_slug").val();
		var projectType = $("#project_type").val();
		var projectOwner = $("#project_owner").val();

		if (template == '' || transcript == '') return;

		// Make sure the # of items matches
		if ($("#import-preview p").length != $(".sidebar ul.items li").length) {
			return false;
		}

		// Create array of item IDs
		items = [];
		$(".sidebar ul.items li").each(function() {
			items.push($(this).attr("data-id"));
		});

		this.callAPI('import-transcript', 'POST', { template: template, transcript: transcript, projectType: projectType, projectOwner: projectOwner, projectSlug: projectSlug, items: items },
			function(data) {
				if (data.status == 'success') {
					// Redirect to project admin page
					if (projectType == 'system') {
						var locStr = app_url + '/projects/' + projectSlug + '/admin';
					} else {
						var locStr = app_url + '/users/' + projectOwner + '/projects/' + projectSlug + '/admin';
					}

					window.location.href = locStr;
				}
			}
		);
	};


	// Save a user's settings
	// --------------------------------------------------

	this.saveSettings = function() {
		unbindery.showSpinner();

		var username = $("#username").html();
		var name = $("#user_name").val().trim();
		var email = $("#user_email").val().trim();

		var prefs = {
			'sidebyside': ($("#sidebyside").attr("checked")) ? 1 : 0,
			'theme': $("#theme").find("option:selected").val()
		};

		// Loop through notifications and add
		prefs.notifications = {};
		$(".notifications input[type=checkbox]").each(function() {
			prefs.notifications[$(this).attr("name")] = ($(this).attr("checked")) ? 1 : 0;
		});

		unbindery.callAPI('save-settings', 'POST', { username: username, name: name, email: email, prefs: prefs },
			function(data) {
				if (data.statuscode == "success") {
					unbindery.hideSpinner();
					unbindery.redirectToDashboard("settings.saved", "");
				} else {
					unbindery.redirectToDashboard("", "settings.error");
				}
			}
		);
	};

}


$(document).ready(function() {
	unbindery = new Unbindery();

	// Click handlers for the buttons
	// --------------------------------------------------

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

	$("#action-save-project").click(function(e) {
		unbindery.saveProject();
	});

	$("#action-save-project-and-activate").click(function(e) {
		// Set to active first
		$("#project_status").val("active");

		// And now save the project as usual
		unbindery.saveProject();
	});


	// Click handler for getting a new item
	// --------------------------------------------------
	
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


	// Install page
	// --------------------------------------------------

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


	// Project page (joining/leaving)
	// --------------------------------------------------

	$('.proj_details .membership a').click(function() {
		// The URL to call
		var url = ($(this).hasClass('join')) ? $(this).attr('href') : $(this).attr('href') + '/leave';

		var message = ($(this).hasClass('join')) ? 'You are now a member of this project.' : 'You have left this project.';

		// call URL with appropriate method
		$.ajax({
			url: url,
			type: 'POST',
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


	// Item deletion (project admin page)
	// --------------------------------------------------

	$("#main.add section.items ul.items").on("click", "a.delete", function() {
		var itemId = $(this).parents("li:first").attr("data-id");

		if (confirm("Are you sure you want to delete that item?")) {
			unbindery.deleteItem(itemId);
		}
		
		return false;
	});


	// Adding users to projects (project admin page)
	// --------------------------------------------------

	$("#main.add .addbox input[type=submit]").on("click", function() {
		var username = $(".addbox input[type=text]").val().trim();
		var role = $(".addbox select option:selected").val();

		unbindery.addUserToProject(username, role);

		return false;
	});


	// Removing users from projects (project admin page)
	// --------------------------------------------------

	$("#main.add ul.items.members").on("click", "a.delete", function() {
		var username = $(this).parents("li:first").attr("data-username");
		var role = $(this).parents("li:first").attr("data-role");

		if (confirm("Are you sure you want to remove that user from this project?")) {
			unbindery.removeUserFromProject(username, role);
		}
		
		return false;
	});


	// Character pad
	// --------------------------------------------------

	$("#controls #characters").on("click", function() {
		$("#characterpad").toggle();
		
		return false;
	});

	$("#characterpad li").on("click", function() {
		var character = $(this).html();

		unbindery.insertText(character);

		return false;
	});


	// Import transcript page
	// --------------------------------------------------

	$(".import #import-template").on("change", function() {
		unbindery.updateImportPreview();
	});

	$(".import #import-transcript").on("change", function() {
		unbindery.updateImportPreview();
	});

	$("#action-import-transcript").on("click", function() {
		unbindery.importTranscript();
		return false;
	});

	$("#main.import .sidebar ul.items").on("click", "li .itemcontrols a.delete", function() {
		var parentList = $(this).parents("ul:first");

		$(this).parents("li:first").remove();

		parentList.siblings("h3").html("Selected Items (" + parentList.find("li").length + " items)");

		// Make sure the # of items matches
		if ($("#import-preview p").length != $(".sidebar ul.items li").length) {
			html = "<div class='error'>";
			html += "Number of items doesn't match up."; // TODO: get from translations
			html += "</div>";

			$(html).prependTo("#main.import .sidebar");
		} else {
			$(".sidebar div.error").remove();
		}

		return false;
	});


	// User settings page
	// --------------------------------------------------

	$("#action-save-settings").click(function() {
		unbindery.saveSettings();
	});


	// Deleting users (admin dashboard)
	// --------------------------------------------------

	$("#main.dashboard ul.items.users").on("click", "li .itemcontrols a.delete", function() {
		var username = $(this).parents("li:first").find("b").html();

		if (confirm("Are you sure you want to delete that user?")) {
			unbindery.deleteUser(username);
		}

		return false;
	});
});
