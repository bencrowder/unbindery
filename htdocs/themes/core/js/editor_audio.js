
AudioController = function() {
	this.volume = 50;

	this.playToggle = function() {
		soundManager.togglePause('audio');

		return false;
	};

	this.rewind = function() {
		mySound = soundManager.getSoundById('audio');
		mySound.setPosition(mySound.position - 5);

		return false;
	};

	this.rewind30 = function() {
		mySound = soundManager.getSoundById('audio');
		mySound.setPosition(mySound.position - 30);

		return false;
	};

	this.fastForward = function() {
		mySound = soundManager.getSoundById('audio');
		mySound.setPosition(mySound.position + 5);

		return false;
	};

	this.fastForward30 = function() {
		mySound = soundManager.getSoundById('audio');
		mySound.setPosition(mySound.position + 30);

		return false;
	};

	this.jumpToBeginning = function() {
		mySound = soundManager.getSoundById('audio');
		mySound.setPosition(0);

		return false;
	};

	this.jumpToEnd = function() {
		mySound = soundManager.getSoundById('audio');
		mySound.setPosition(duration);

		return false;
	};

	this.incVolume = function() {
		mySound = soundManager.getSoundById('audio');
		this.volume += 5;
		mySound.setVolume(this.volume);

		return false;
	}

	this.decVolume = function() {
		mySound = soundManager.getSoundById('audio');
		this.volume -= 5;
		mySound.setVolume(this.volume);

		return false;
	};
};

$(document).ready(function() {
	$("#transcript").focus();

	// Set up SoundManager 2
	soundManager.setup({
		url: theme_root + '/js/editor_audio/swf/',
		flashVersion: 9,
		useFlashBlock: false,
		useHTML5Audio: true,
		onready: function() {
			soundManager.createSound({
				id: 'audio',
				url: $("audio#audio").attr("src"),
				autoLoad: true,
				autoPlay: false,
				volume: 50
			});
		}
	});

	audioController = new AudioController();

	// Play/pause toggle
	$("#transcript").bind("keydown", "shift+space", audioController.playToggle);

	// Rewind and fast forward
	$("#transcript").bind("keydown", "ctrl+h", audioController.rewind30);	
	$("#transcript").bind("keydown", "ctrl+j", audioController.rewind);	
	$("#transcript").bind("keydown", "ctrl+k", audioController.fastForward);
	$("#transcript").bind("keydown", "ctrl+l", audioController.fastForward30);

	// Jump to beginning/end
	$("#transcript").bind("keydown", "ctrl+0", audioController.jumpToBeginning);
	$("#transcript").bind("keydown", "ctrl+9", audioController.jumpToEnd);

	// Volume controls
	$("#transcript").bind("keydown", "ctrl+1", audioController.incVolume);
	$("#transcript").bind("keydown", "ctrl+2", audioController.decVolume);
});
