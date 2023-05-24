zenario_advanced_search.onKeyUp = function(Container_Id, field, default_tab, mode, delay) {
	if (event) {
		//Close the form if it's the Escape key
		var keyPressed = event.keyCode;
		if (keyPressed && keyPressed == 27) {
			zenario_advanced_search.closeButtonOnClick(Container_Id);
		} else {
			zenario.actAfterDelayIfNotSuperseded('zenario_advanced_search', function() {
				
				var containerId = zenario.getContainerIdFromEl(field),
					$searchResults = $('#' + containerId + ' .zenario_advanced_search_results');
				
				$searchResults.stop(true, true).animate({opacity: .5}, 150);
				
				zenario.submitFormReturningHtml(field.form, function(html) {
					var $resultDom = $(html);

					if (mode == 'search_page') {
						var requestsToRemember = {};
						
						var params = ['ctab', 'language_id', 'category00_id', 'category01_id', 'category02_id', 'searchString'];
						params.forEach((param) => {
							var val = $resultDom.find('input[name$="' + param + '"]').val();
							if (val) {
								requestsToRemember.param = val;
							}
						});
						
						zenario.recordRequestsInURL(Container_Id, requestsToRemember);
					}

					$searchResults.html($resultDom.find('.zenario_advanced_search_results').html());
					$searchResults.stop(true, true).animate({opacity: 1}, 150);
				});
			
			}, delay);
		}
	}
};

zenario_advanced_search.searchButtonOnClick = function(containerId) {
	el = $('#' + containerId + '_search_entry_box_panel');
	el.stop().slideToggle(175);

	el = $('#' + containerId + '_search_button_panel');
	el.toggleClass('active');

	$('#' + containerId + '_search_input_box').focus();
};

zenario_advanced_search.closeButtonOnClick = function(containerId) {
	el = $('#' + containerId + '_search_entry_box_panel');
	if (el.is(":visible")) {
		el.stop().slideToggle(175);
	}

	el = $('#' + containerId + '_search_button_panel');
	el.toggleClass('active');
};