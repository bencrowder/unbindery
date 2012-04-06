/* Unbindery */
/* -------------------------------------------------- */

$(document).ready(function() {
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
	$("#spinner").show();

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

	$.post(app_url + "/ws/save_item_transcript", { item_id: item_id, project_slug: project_slug, username: username, draft: is_draft, review: is_review, review_username: review_username, itemtext: itemtext },
		function(data) {
			if (data.statuscode == "success") {
				$("#spinner").hide();

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
		}, 'json');
}

function get_new_page(project_slug) {
	var username = $("nav ul .username").html();

	$.post(app_url + "/ws/get_new_page", { project_slug: project_slug, username: username },
		function(data) {
			switch (data.statuscode) {
				case "success":
					var locstr = app_url + '/edit/' + project_slug + '/' + data.item_id;
					window.location.href = locstr;
					break;
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
		}, 'json');

	return false;
}

function save_page() {
	$("#spinner").show();

	var item_id = $("#item_id").val();
	var project_slug = $("#project_slug").val();
	var pagetext = $("#pagetext").val();
	var username = $("nav ul .username").html();
	var review_username = $("#review_username").html();

	$.post(app_url + "/unbindery.php?method=save_page", { item_id: item_id, project_slug: project_slug, username: username, draft: is_draft, review: is_review, review_username: review_username, itemtext: itemtext },
		function(data) {
			if (data.statuscode == "success") {
				$("#spinner").hide();

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
		}, 'json');
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
	$.post(app_url + "/ws/add_pages", { project_slug: project_slug, pages: pages },
		function(data) {
			if (data.statuscode == "success") {
				// load the first page into edit mode
				var firstpage = data.page_ids[0];
				window.location.href = app_url + '/admin/new_page/' + project_slug + '/' + firstpage;
			} else {
				console.log("error!");
			}
		}, 'json');
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

	$(".getnewitem").click(function(e) {
		$(this).hide();
		$(this).siblings('.spinner').show();
		var project_slug = this.getAttribute('data-project-slug');

		get_new_page(project_slug);
	});
});
