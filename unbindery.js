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
	console.log("in save_item_text");
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

$(document).ready(function() {
	$("textarea#transcript").focus();

	$("#save_as_draft_button").click(function() {
		save_item_text(true);
	});

	$("#finished_button").click(function(e) {
		save_item_text(false);
	});
});
