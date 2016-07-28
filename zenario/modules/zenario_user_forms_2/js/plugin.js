(function(module) {
	'use strict';
	
	module.initForm = function(containerId) {
		// Init date pickers
		$('#' + containerId + '_user_form input.jquery_form_datepicker').each(function(i, el) {
			zenario.loadDatePicker();
			if (el.id) {
				el.value = $.datepicker.formatDate(zenario.dpf, $.datepicker.parseDate('yy-mm-dd', get(el.id + '__0').value));
				$('#' + el.id).datepicker({
					dateFormat: zenario.dpf,
					altField: '#' + el.id + '__0',
					altFormat: 'yy-mm-dd',
					showOn: 'focus'
				});
			}
		});
		// Init select list source fields
		$('#' + containerId + '_user_form select.source_field').on('change', function() {
			$('#' + containerId + '_user_form input.filter_form').val(1);
			$('#' + containerId + '_user_form form').submit();
		});
		// Init visible on condition fields
		$('#' + containerId + '_user_form .form_field.visible_on_condition').each(function(i, el) {
			var cFieldId = $(this).data('cfieldid');
			var cFieldValue = $(this).data('cfieldvalue');
			var fieldId = $(this).data('id');
			if (cFieldId) {
				$('#' + containerId + '_field_' + cFieldId + ' :input').on('change', function() {
					var value;
					if ($(this).is('checkbox')) {
						value = $(this).is(':checked');
					} else {
						value = $(this).val();
					}
					if ((cFieldValue === null && value !== '') || value == cFieldValue) {
						$('#' + containerId + '_field_' + fieldId).show();
					} else {
						$('#' + containerId + '_field_' + fieldId).hide();
					}
				});
			}
		});
		// Init autocomplete lists
		var $autocompleteLists = $('#' + containerId + '_user_form .autocomplete_json');
		$autocompleteLists.each(function() {
			var that = this;
			var values = JSON.parse($(this).text());
			if (values) {
				var list = [];
				for (var i = 0; i < values.length; i++) {
					list.push({value: values[i].v, label: values[i].l});
				}
				$(this).prev().autocomplete({
					minLength: 0,
					source: list,
					select: function(event, ui) {
						event.preventDefault();
						var label = '';
						var value = '';
						if (ui.item) {
							label = ui.item.label;
							value = ui.item.value;
						}
						$(that).next().val(value);
						$(that).prev().val(label);
						// If this is a source field, reload the form to filter any target fields
						if ($(that).data('source_field')) {
							$('#' + containerId + '_user_form input.filter_form').val(1);
							$('#' + containerId + '_user_form form').submit();
						}
					},
					focus: function(event, ui) {
						this.value = ui.item.label;
      					event.preventDefault();
					},
					change: function(event, ui) {
						// If finish typing an option without selecting, search for that value and select it
						if (!ui.item) {
							var value = $(this).val();
							var found = list.find(function(el) {
								return el.label.toLowerCase() === value.toLowerCase();
							});
							$(this).data('ui-autocomplete')._trigger('select', 'autocompleteselect', {item:found});
						}
					},
					open: function(event, ui) {
						var that = this;
						$('#' + containerId + '_user_form').scroll(function() {
							$(that).autocomplete('close');
						});
 					}
				}).on('click', function () {
					if (list.length == 0) {
						var placeholder = $(that).data('auto_placeholder');
						if (placeholder) {
							$(this).prop('placeholder', placeholder);
						}
					}
					$(this).autocomplete("search", "");
				});
			}
		});
	};
	
})(zenario_user_forms_2);