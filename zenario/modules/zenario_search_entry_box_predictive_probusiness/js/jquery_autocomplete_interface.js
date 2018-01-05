zenario_search_entry_box_predictive_probusiness.autocomplete = function(containerId, searchURL, dropdownPosition) {	
	
	switch (dropdownPosition) {
		case 'slot_bottom_right':
			dropdownPosition = {my: 'right top', at: 'right bottom', collision: 'none', of: '#' + containerId};
			break;
		
		case 'slot_bottom_left':
			dropdownPosition = {my: 'left top', at: 'left bottom', collision: 'none', of: '#' + containerId};
			break;
		
		case 'form_bottom_right':
			dropdownPosition = {my: 'right top', at: 'right bottom', collision: 'none', of: '#' + containerId + ' form'};
			break;
		
		case 'form_bottom_left':
			dropdownPosition = {my: 'left top', at: 'left bottom', collision: 'none', of: '#' + containerId + ' form'};
			break;
		
		case 'search_bottom_right':
			dropdownPosition = {my: 'right top', at: 'right bottom', collision: 'none'};
			break;
		
		default:
			dropdownPosition = {my: 'left top', at: 'left bottom', collision: 'none'};
	}
	
	$('#search_field_' + containerId).autocomplete({
		minLength: 3,
		position: dropdownPosition,
		source: function(request, response) {
			var data, req = {searchString: request.term};
			
			if (data = zenario.checkSessionStorage(searchURL, req, true)) {
				response(data);
			
			} else {
				zenario_search_entry_box_predictive_probusiness.lastRequest =
				$.ajax({
					url: searchURL,
					data: req,
					dataType: 'json',
					success: function(data, status, xhr) {
						if (xhr === zenario_search_entry_box_predictive_probusiness.lastRequest) {
							zenario.setSessionStorage(data, searchURL, req, true)
							response( data );
						}
					}
				});
			}
		},
		
		select: function(event, ui) {
			zenario.goToURL(ui.item.url);
			return false;
		}
		
	});
	
	
	$('#search_field_' + containerId).data( "ui-autocomplete" )._renderItem =
		function (ul, item) {
			return $("<li></li>").append($('<a>' + item.label + '</a>')).appendTo(ul);
		};
}