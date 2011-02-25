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
			switch(data.statuscode) {
				case "success":
					var locstr = siteroot + '/edit/' + project_slug + '/' + data.item_id;
					window.location.href = locstr;
					break;
				case "waiting_for_clearance":
					redirect_to_dashboard("", "Your first item has to be approved before you can proof more items. (Just this once, though.)");
					break;	
				case "have_item_already":
					redirect_to_dashboard("", "You already have one item for this project. Finish it and then you'll be able to get a new one.");
				case "not_a_member":
					redirect_to_dashboard("", "You're not a member of that project.");
					break;
				default:
					redirect_to_dashboard("", "Error getting new item.");
					break;
			}
		}, 'json');
}

function load_items_for_editing(event, data) {
	var items = [];
	var project_slug = $("#project_slug").val();

	$("#file_uploadQueue .fileName").each(function() {
		var filename = $(this).html();
		// strip up to the first dot
		var itemname = filename.substr(0, filename.indexOf('.'));

		items.push(itemname);
	});

	// we need to add them to the database here
	$.post(siteroot + "/unbindery.php?method=create_items", { project_slug: project_slug, items: items },
		function(data) {
			if (data.statuscode == "success") {
				// data.items = list of IDs
				var content = '';

				for (item_index in data.item_ids) {
					var item = data.item_ids[item_index];
					itemname = item.substr(0, item.lastIndexOf('_'));
					itemid = item.substr(item.lastIndexOf('_') + 1, item.length);

					content += '<label>' + itemname + '</label>';
					content += '<textarea class="item_textarea" id="' + itemid + '_text" name="' + itemid + '_text"></textarea>\n';
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
