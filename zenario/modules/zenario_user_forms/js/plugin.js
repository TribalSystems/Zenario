

zenario_user_forms.updateRestatementFields = function(id, mode) {
	var selector;
	
	if (id) {
		selector = ':input[data-mirror-of="' + id + '"]';
	} else {
		selector = ':input[data-mirror-of]';
	};
	
	$(selector).each(function(i, el) {
		
		var $mirror = $(el),
			$source = $('#' + $mirror.data('mirror-of'));
		if (mode == 'checkbox' || mode == 'radio') {
			$mirror.prop('checked', $source.prop('checked'));
		} else {
			$mirror.val($source.val());
		}
	});
};

zenario_user_forms.initCalculateField = function(containerId, field, sourceField1, sourceField2, calculationType, mirrorFields) {
	var 
		sourceField1 = $('input#'+containerId+'_field_value_'+sourceField1),
		sourceField2 = $('input#'+containerId+'_field_value_'+sourceField2);
	
	sourceField1.on('keyup', function() {
		zenario_user_forms.calculate(containerId, field,  sourceField1, sourceField2, calculationType, mirrorFields);
	});
	
	sourceField2.on('keyup', function() {
		zenario_user_forms.calculate(containerId, field, sourceField1, sourceField2, calculationType, mirrorFields);
	});
};

zenario_user_forms.calculate = function(containerId, field, soruceField1, sourceField2, calculationType, mirrorFields) {
	var 
		value1 = soruceField1.val(),
		value2 = sourceField2.val(),
		sum = 0;
	
	value1 = Number(value1);
	if (!isNaN(value1) && isFinite(value1)) {
		sum += value1;
	}
	
	value2 = Number(value2);
	if (!isNaN(value2) && isFinite(value1)) {
		if (calculationType == '-') {
			value2 *= -1;
		}
		sum += value2;
	}
	
	sum = parseFloat(sum.toFixed(2));
	
	zenario_user_forms.setJSCalculatedField(containerId, field, sum);
	
	if (mirrorFields) {
		for (field in mirrorFields) {
			if (mirrorFields.hasOwnProperty(field)) {
				$(':input[data-mirror-of="'+containerId+'_field_value_'+mirrorFields[field]+'"]').val(sum);
			}
		}
	}
};

zenario_user_forms.setJSCalculatedField = function(containerId, field, value) {
	$('input#'+containerId+'_field_value_'+field).val(value);
};



zenario_user_forms.toggleFieldVisibility = function(
	containerId, fieldId, visibleConditionFieldId, visibleConditionFieldValue, visibleConditionFieldType) {
	
	$('div#'+containerId+'_field_'+visibleConditionFieldId+' :input').on('change', function() {
		var value;
		if (visibleConditionFieldType == 'checkbox') {
			value = $(this).is(':checked');
		} else {
			value = $(this).val();
		}
		if (value == visibleConditionFieldValue) {
			$('div#'+containerId+'_field_'+fieldId).show();
		} else {
			$('div#'+containerId+'_field_'+fieldId).hide();
		}
	});
};

zenario_user_forms.initMultiPageForm = function(AJAXURL, containerId, identifier) {
	// Enter key advances to next stage instead of submitting form
	$('#'+identifier+' :input:not(textarea)').on('keydown', function(event) {
		if (event.keyCode == 13) {
			event.preventDefault();
			$(this).parents('fieldset').find('input.next').click();
		}
	});
	
	// Navigate to next section
	$('#'+identifier+' input.next').on('click', function(event) {
		event.preventDefault();
		var current = $(this).parent(),
			next = $(this).parent().next(),
			currentPageId = current.prop('id'),
			post = false,
			pageNo = currentPageId.match(/\d+$/),
			submitForm = $(this).is('input[type="submit"]');
		
		// Entire form should be valid if submitting form
		if (submitForm) {
			post = $('#'+identifier).find(':input').serialize();
		} else {
			post = $('#'+currentPageId).find(':input').serialize();
		}
		
		post += '&_pageNo='+pageNo;
		
		// Validate fields on current page
		zenario.ajax(AJAXURL, post).after(function(errors) {
			errors = JSON.parse(errors);
			// Remove any old errors from current page
			$('#'+currentPageId+' div.form_error').remove();
			if (!_.isEmpty(errors)) {
				// Show any errors
				var html = '',
					errorSelector = '';
				for (fieldId in errors) {
					errorSelector = '#'+containerId+'_field_'+fieldId;
					html = '<div class="form_error">'+ errors[fieldId].message +'</div>';
					if ((errors[fieldId].type == 'checkbox' || errors[fieldId].type == 'group') || !$(errorSelector+' div.field_title').length) {
						$(errorSelector).append(html);
					} else {
						$(errorSelector+' div.field_title').after(html);
					}
				}
				$('#'+containerId).effect( "shake", {distance: 10, times: 3, duration: 300});
			} else {
				// Submit if final button
				if (submitForm) {
					$('#'+identifier+'__form').submit();
				} else {
					//current.hide();
					current.css('visibility', 'visible');
					next.show('slide', {direction: 'right'}, 500);
					current.hide();
					$('html, body').animate({scrollTop:$('#'+containerId).offset().top - 20});
				}
			}
		});
	});
	
	// Navigate to previous section
	$('#'+identifier+' input.previous').on('click', function() {
		var current = $(this).parent();
			prev = $(this).parent().prev();
		
		current.css('visibility', 'visible');
		prev.show('slide', {direction: 'left'}, 500);
		current.hide();
		$('html, body').animate({scrollTop:$('#'+containerId).offset().top - 20});
		
	});
};