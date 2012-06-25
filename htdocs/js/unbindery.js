/* Unbindery */
/* -------------------------------------------------- */

var Unbindery = function() {
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
		var username = $("nav ul .username").html();

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
	}
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
});

function redirect_to_dashboard(message, error, username) {
	var locstr = app_url + "/users/" + username + "/dashboard";

	if (message || error) { locstr += "?"; }

	if (message) {
		locstr += "message=" + message;
	}
	if (error) {
		if (message) { locstr += "&"; }
		locstr += "error=" + error;
	}

	window.location.href = locstr;
}

function save_page_text(is_draft, is_review, slug) {
	Unbindery.showSpinner();

	var item_id = $("#item_id").val();
	var project_slug = $("#project_slug").val();
	var itemtext;

	if (editbox == "simple") {
		itemtext = $("#page_text").val();						// textarea
	} else {
		itemtext = editor.session.doc.$lines.join('\n');		// Ace
	}
	var username = $("nav ul .username").html();
	var review_username = $("#review_username").html();

	Unbindery.callAPI("save_item_transcript", 'POST', { item_id: item_id, project_slug: project_slug, username: username, draft: is_draft, review: is_review, review_username: review_username, itemtext: itemtext },
		function(data) {
			if (data.statuscode == "success") {
				Unbindery.hideSpinner();

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
	Unbindery.showSpinner();

	var item_id = $("#item_id").val();
	var project_slug = $("#project_slug").val();
	var pagetext = $("#pagetext").val();
	var username = $("nav ul .username").html();
	var review_username = $("#review_username").html();

	Unbindery.callAPI(app_url + "/unbindery.php?method=save_page", 'POST', { item_id: item_id, project_slug: project_slug, username: username, draft: is_draft, review: is_review, review_username: review_username, itemtext: itemtext },
		function(data) {
			if (data.statuscode == "success") {
				Unbindery.hideSpinner();

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
	Unbindery.callAPI("add_pages", 'POST', { project_slug: project_slug, pages: pages },
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
	$("textarea#page_text").focus();

	$("#save_as_draft_button").click(function() {
		save_page_text(true, false, ''); // yes draft, no review, no get
	});

	$("#finished_button").click(function(e) {
		save_page_text(false, false, ''); // no draft, no review, no get
	});

	$("#finish_get_next_button").click(function(e) {
		var project_slug = $("#project_slug").val();
		save_page_text(false, false, project_slug); // no draft, no review, do get another one
	});

	$("#finished_review_button").click(function(e) {
		save_page_text(false, true, ''); // no draft, yes review
	});

	$("#save_page_button").click(function(e) {
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
