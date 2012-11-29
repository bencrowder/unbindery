$(document).ready(function() {
	// Focus on the transcript textarea
	$("#transcript").focus();

	player = new MediaElementPlayer('#audio', { 'enableKeyboard': false });

	// Play/pause toggle
	$("#transcript").bind("keydown", "shift+space", function() {
		if (player.media.paused) {
			player.media.play();
		} else {
			player.media.pause();
		}

		$("#audio").blur();

		return false;
	});

	// Rewind 30 seconds
	$("#transcript").bind("keydown", "ctrl+h", function() {
		player.media.setCurrentTime(player.media.currentTime - 30);
		return false;
	});

	// Rewind 5 seconds
	$("#transcript").bind("keydown", "ctrl+j", function() {
		player.media.setCurrentTime(player.media.currentTime - 5);
		return false;
	});

	// Fast forward 5 seconds
	$("#transcript").bind("keydown", "ctrl+k", function() {
		player.media.setCurrentTime(player.media.currentTime + 5);
		return false;
	});

	// Fast forward 30 seconds
	$("#transcript").bind("keydown", "ctrl+l", function() {
		player.media.setCurrentTime(player.media.currentTime + 30);
		return false;
	});

	// Jump to beginning
	$("#transcript").bind("keydown", "ctrl+0", function() {
		player.media.setCurrentTime(0);
		return false;
	});
});
