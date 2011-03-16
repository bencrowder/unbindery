var editor;

$(document).ready(function() {
	editor = ace.edit("page_text");
	var session = editor.getSession();
	var renderer = editor.renderer;

	editor.setTheme("ace/theme/unbindery");			// copy of Eclipse theme

	session.setUseWrapMode(true);					// soft wrap
});
