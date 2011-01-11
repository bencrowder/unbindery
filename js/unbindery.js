var siteroot = "http://bencrowder.net/sandbox/unbindery";

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

function save_item_text(is_draft) {
	$("#spinner").show();

	var item_id = $("#item_id").val();
	var project_slug = $("#project_slug").val();
	var itemtext = $("#itemtext").val();
	var username = $("ul#nav .username").html();

	$.post(siteroot + "/unbindery.php?method=save_item_text", { item_id: item_id, project_slug: project_slug, username: username, draft: is_draft, itemtext: itemtext },
		function(data) {
			if (data.statuscode == "success") {
				$("#spinner").hide();

				if (is_draft) {
					var message = "Saved draft.";
				} else {
					var message = "Finished item.";
				}
				redirect_to_dashboard(message, "");
			} else {
				redirect_to_dashboard("", "Error saving item. Try again.");
			}
		}, 'json');
}

function get_new_item(project_slug) {
	var username = $("ul#nav .username").html();

	$.post(siteroot + "/unbindery.php?method=get_next_item", { project_slug: project_slug, username: username },
		function(data) {
			if (data.statuscode == "success") {
				var locstr = siteroot + '/edit/' + project_slug + '/' + data.item_id;
				window.location.href = locstr;
			} else {
				redirect_to_dashboard("", "Error getting new item.");
			}
		}, 'json');
}

$(document).ready(function() {
	$("textarea#transcript").focus();

	$("#save_as_draft_button").click(function() {
		save_item_text(true);
	});

	$("#finished_button").click(function(e) {
		save_item_text(false);
	});

	$(".getnewitem").click(function(e) {
		var project_slug = this.getAttribute('data-project-slug');
		get_new_item(project_slug);
	});
});