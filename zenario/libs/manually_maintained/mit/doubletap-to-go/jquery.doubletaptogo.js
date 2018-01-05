/* 
Double Tap to Go
Authors: Osvaldas Valutis (www.osvaldas.info), Graffino (http://www.graffino.com), Tribal Systems
Available for use under the MIT License
*/

(function($) {
	
	var lastItem,
		currentItem,
		eventAdded = false,
		isTouchScreen = zenario.isTouchScreen();
	
	$.fn.doubleTapToGo = function() {
		
		if (isTouchScreen) {
			
			if (!eventAdded) {
				$(document).on('click touchstart MSPointerDown', function() {
					lastItem = currentItem;
					currentItem = undefined;
				});
				eventAdded = true;
			}
			
			this.each(function() {

				$(this).on('click', function(e) {
					var $item = $(this);
				
					if ($item[0] != lastItem) {
						e.preventDefault();
					
						setTimeout(function() {
							lastItem = currentItem = $item[0];
						}, 1);
					}
				});
			});
		}
	
		return this;
	};
})(jQuery);
