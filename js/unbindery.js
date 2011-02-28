function redirect_to_dashboard(message, error) {
	var locstr = siteroot + "/dashboard";

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

function save_item_text(is_draft, is_review) {
	$("#spinner").show();

	var item_id = $("#item_id").val();
	var project_slug = $("#project_slug").val();
	var itemtext = $("#itemtext").val();
	var username = $("ul#nav .username").html();
	var review_username = $("#review_username").html();

	$.post(siteroot + "/unbindery.php?method=save_item_text", { item_id: item_id, project_slug: project_slug, username: username, draft: is_draft, review: is_review, review_username: review_username, itemtext: itemtext },
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
				redirect_to_dashboard(message, "");
			} else {
				redirect_to_dashboard("", "Error saving page. Try again.");
			}
		}, 'json');
}

function get_new_item(project_slug) {
	var username = $("ul#nav .username").html();

	$.post(siteroot + "/unbindery.php?method=get_next_item", { project_slug: project_slug, username: username },
		function(data) {
			switch(data.statuscode) {
				case "success":
					var locstr = siteroot + '/edit/' + project_slug + '/' + data.item_id;
					window.location.href = locstr;
					break;
				case "waiting_for_clearance":
					redirect_to_dashboard("", "Your first page has to be approved before you can proof more pages. (Just this once, though.)");
					break;	
				case "have_item_already":
					redirect_to_dashboard("", "You already have one page for this project. Finish it and then you'll be able to get a new one.");
				case "not_a_member":
					redirect_to_dashboard("", "You're not a member of that project.");
					break;
				default:
					redirect_to_dashboard("", "Error getting new page.");
					break;
			}
		}, 'json');
}

function load_items_for_editing(event, data) {
	var pages = [];
	var project_slug = $("#project_slug").val();

	$("#file_uploadQueue .fileName").each(function() {
		var filename = $(this).html();
		// strip up to the first dot
		var pagename = filename.substr(0, filename.indexOf('.'));

		pages.push(pagename);
	});

	// add them to the database
	$.post(siteroot + "/unbindery.php?method=add_pages", { project_slug: project_slug, pages: pages },
		function(data) {
			if (data.statuscode == "success") {
				// load the first page into edit mode

				// data.items = list of IDs
				var content = '';

				for (i in data.page_ids) {
					var page = data.page_ids[i];
					pagename = page.substr(0, page.lastIndexOf('_'));
					pageid = page.substr(page.lastIndexOf('_') + 1, page.length);

					content += '<label>' + pagename + '</label>';
					content += '<textarea class="item_textarea" id="' + pageid + '_text" name="' + pageid + '_text"></textarea>\n';
				}

				$("#save_items #itemlist").html(content);
				$("#save_items").show();
			} else {
				console.log("error!");
			}
		}, 'json');

}

$(document).ready(function() {
	$("textarea#itemtext").focus();

	$("#save_as_draft_button").click(function() {
		save_item_text(true, false); // yes draft, no review
	});

	$("#finished_button").click(function(e) {
		save_item_text(false, false); // no draft, no review
	});

	$("#finished_review_button").click(function(e) {
		save_item_text(false, true); // no draft, yes review
	});

	$(".getnewitem").click(function(e) {
		var project_slug = this.getAttribute('data-project-slug');
		get_new_item(project_slug);
	});
});
