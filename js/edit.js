var editbox = "simple";
var editor;

$(document).ready(function() {
	if ($("div#page_text").length) {					// we're using the advanced editor
		editbox = "advanced";

		editor = ace.edit("page_text");
		var session = editor.getSession();
		var renderer = editor.renderer;

		editor.setTheme("ace/theme/unbindery");			// copy of Eclipse theme

		session.setUseWrapMode(true);					// soft wrap
	}
});
