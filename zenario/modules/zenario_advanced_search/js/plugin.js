zenario_advanced_search.onKeyUp = function(Container_Id, field, default_tab, mode, delay, usesSpecificResultsPage) {
	if (event) {
		//Close the form if it's the Escape key
		var keyPressed = event.keyCode;
		if (usesSpecificResultsPage && keyPressed && keyPressed == 27) {
			zenario_advanced_search.closeButtonOnClick(Container_Id, mode);
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
							var val;
							
							if (param == 'ctab' || param == 'searchString') {
								val = $resultDom.find('input[name$="' + param + '"]').val();
							} else {
								val = $resultDom.find('select[name$="' + param + '"]').val();
								
								if (val == 0) {
									val = '';
								}
							}
							
							if (val) {
								requestsToRemember[param] = val;
							}
						});
						
						zenario.recordRequestsInURL(Container_Id, requestsToRemember);
					}

					if (mode == 'search_entry_box_show_always') {
						htmlEl = $('#' + containerId + '_results');
						htmlEl.show(200);
					}
					
					$searchResults.html($resultDom.find('.zenario_advanced_search_results').html());
					$searchResults.stop(true, true).animate({opacity: 1}, 150);
				});
			
			}, delay);
		}
	}
};

zenario_advanced_search.searchButtonOnClick = function(containerId) {
	el = $('#' + containerId + '-search_entry_box_panel');
	el.stop().slideToggle(200);

	el = $('#' + containerId + '_search_button_panel');
	el.toggleClass('active');

	$('#' + containerId + '-search_input_box').focus();
	
	htmlEl = $('html');
	htmlEl.addClass('zenario_advanced_search-wrapper_opened');
};

zenario_advanced_search.closeButtonOnClick = function(containerId, mode) {
	if (mode == 'search_entry_box') {
		el = $('#' + containerId + '-search_entry_box_panel');
		if (el.is(":visible")) {
			el.stop().slideToggle(200);
		
			htmlEl = $('html');
			htmlEl.removeClass('zenario_advanced_search-wrapper_opened');
		
			el = $('#' + containerId + '_search_button_panel');
			setTimeout(function(){ el.toggleClass('active'); },400);
		}
	} else if (mode == 'search_entry_box_show_always') {
		htmlEl = $('#' + containerId + '_results');
		htmlEl.hide(200);
		
		$('#' + containerId + '-search_input_box').val('');
	}
};