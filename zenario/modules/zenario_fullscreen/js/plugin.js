zenario_fullscreen.init = function(containerId, fullscreenPhrase, shrinkPhrase) {
	var docEl = document.documentElement,
		$button = $('#' + containerId + '_fullscreen_button');
	if (zenario.isFullScreenAvailable(docEl)) {
		var toggleFullScreen = function() {
			if (!zenario.isFullScreen()) {
				zenario.enableFullScreen(docEl);
			} else {
				zenario.exitFullScreen();
			}
		};
		$button.show();
		$button.on('click', function() {
			toggleFullScreen();
		});
		$(document).on('fullscreenchange msfullscreenchange mozfullscreenchange webkitfullscreenchange', function() {
			var fullscreenClass = 'fullscreen_mode',
				shrinkClass = 'shrink_mode';
			if (zenario.isFullScreen()) {
				$button
					.html(fullscreenPhrase)
					.addClass(fullscreenClass)
					.removeClass(shrinkClass)
					
			} else {
				$button
					.html(shrinkPhrase)
					.addClass(shrinkClass)
					.removeClass(fullscreenClass)
			}
		});
	}
};