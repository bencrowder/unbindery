$(document).ready(function() {
	// Focus on the transcript textarea
	$("#transcript").focus();

	ubHighlightHeight = $("#image_container .highlight").height();
	ubImageWidth = $("#image_container img").width();

	$("#image_container .highlight").css("width", ubImageWidth);

	$("#image_container img").click(function(e) {
		// find the place to put the highlight and then do it
		barY = e.offsetY + ubHighlightHeight;
		$("#image_container .highlight").show().css("top", barY + "px");

		return false;
	});
});
