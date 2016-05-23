zenario_user_forms.recaptcha = function() {
	onloadCallback();
};

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
	$('#'+containerId+'_field_value_'+field).val(value);
};



zenario_user_forms.toggleFieldVisibility = function(containerId, fieldId, visibleConditionFieldId, visibleConditionFieldValue, visibleConditionFieldType) {
	$('#'+containerId+'_field_'+visibleConditionFieldId+' :input').on('change', function() {
		var value;
		if (visibleConditionFieldType == 'checkbox') {
			value = $(this).is(':checked');
		} else {
			value = $(this).val();
		}
		if ((visibleConditionFieldValue === null && value !== '') || value == visibleConditionFieldValue) {
			$('#'+containerId+'_field_'+fieldId).show();
		} else {
			$('#'+containerId+'_field_'+fieldId).hide();
		}
	});
};

zenario_user_forms.initJQueryElements = function(containerId) {
	//jQuery datepickers
	$('#' + containerId + ' input.jquery_form_datepicker').each(function(i, el) {
		zenario.loadDatePicker();
		
		//Flexible Form functionality for date pickers that degrade gracefully into three select lists
		//if JavaScript is not enabled.
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
};

zenario_user_forms.submitMultiPageForm = false;

zenario_user_forms.initMultiPageForm = function(AJAXURL, containerId, identifier, isFloatingBox, pageCount) {
	// Enter key advances to next stage instead of submitting form
	$('#'+identifier+' :input:not(textarea)').on('keydown', function(event) {
		if (event.keyCode == 13) {
			event.preventDefault();
			$(this).parents('fieldset').find('input.next').click();
		}
	});
	
	// Navigate to next section
	$('#'+identifier+' input.next').on('click', function(event) {
		
		if (zenario_user_forms.submitMultiPageForm) {
			return;
		}
		
		event.preventDefault();
		var current = $(this).parent(),
			next = $(this).parent().next(),
			currentPageId = current.prop('id'),
			post = false,
			pageNo = currentPageId.match(/\d+$/),
			submitForm = $(this).is('input[type="submit"]'),
			$form;
		
		// Entire form should be valid if submitting form
		if (submitForm) {
			$form = $('#'+identifier);
		} else {
			$form = $('#'+currentPageId);
		}
		
		// Get fields in form section
		post = $form.find(':input').serialize();
		
		// Add any file fields
		$form.find(':input[type="file"]').each(function(i, el) {
			var $el = $(el);
			post += '&' + $el.prop('name') + '=' + $el.val();
		});
		
		// Add page data
		post += '&_pageNo='+pageNo+'&_pageCount='+pageCount;
		
		var captcha_code = $('#' + containerId + '_math_captcha_input').val();
		if (captcha_code) {
			post += '&captcha_code=' + captcha_code;
		}
		
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
					if (fieldId == 'captcha') {
						html = '<div class="form_error">'+ errors[fieldId] +'</div>';
						$('#' + containerId + '_math_captcha div.field_title').after(html);
					} else {
						errorSelector = '#'+containerId+'_field_'+fieldId;
						html = '<div class="form_error">'+ errors[fieldId].message +'</div>';
						if ((errors[fieldId].type == 'checkbox' || errors[fieldId].type == 'group') || !$(errorSelector+' div.field_title').length) {
							$(errorSelector).append(html);
						} else {
							$(errorSelector+' div.field_title').after(html);
						}
					}
				}
				var selector = containerId;
				if (isFloatingBox) {
					selector = 'colorbox';
				}
				$('#'+selector).effect( "shake", {distance: 10, times: 3, duration: 300});
			} else {
				// Submit if final button
				if (submitForm) {
					zenario_user_forms.submitMultiPageForm = true;
					$('#'+identifier+'__form input:submit').click();
				} else {
					current.css('visibility', 'visible');
					next.show('slide', {direction: 'right'}, 500);
					current.hide();
					if (!isFloatingBox) {
						$('html, body').animate({scrollTop:$('#'+containerId).offset().top - 20});
					}
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


zenario_user_forms.initFilePickerFields = function(containerId, AJAXURL) {
	
	$('#' + containerId + ' .form_field.field_file_picker').each(function() {
		var that = this,
			$fileupload = $(this).find('.file_picker_field'),
			$progressBar = $(this).find('.progress_bar'),
			$fileUploadButton = $(this).find('.file_upload_button'),
			maxNumberOfFiles = $fileupload.data('limit'),
			extensions = $fileupload.data('extensions'),
			count = 0,
			fileNumber = 0,
			that = this,
			field_id = $(this).data('id'),
			acceptFileTypes = undefined;
		
		// Build regex from extensions for file type validation
		if (extensions) {
			acceptFileTypes = '\\.(';
			var temp = extensions.split(','),
				cleanExtensions = [];
			for (var i = 0; i < temp.length; i++) {
				var extension = temp[i].replace(/\./i, '').trim();
				if (extension) {
					cleanExtensions.push(extension);
				}
			}
			acceptFileTypes += cleanExtensions.join('|');
			acceptFileTypes += ')$';
			
			acceptFileTypes = new RegExp(acceptFileTypes, 'i');
		}
		
		// Init progress bar
		$progressBar.progressbar();
		
		// Init file uploader
		$fileupload.fileupload({
			url: AJAXURL,
			dataType: 'json',
			maxFileSize: 10000000, //10MB
			acceptFileTypes: acceptFileTypes,
			maxNumberOfFiles: maxNumberOfFiles,
			getNumberOfFiles: function() {
				return count;
			},
			submit: function(e, data) {
				count++;
			},
			start: function(e) {
				$progressBar.show();
			},
			done: function(e, data) {
				
				// Add new files
				$.each(data.result.files, function(i, file) {
					
					var html = '';
					html += '<div class="file_row">';
					if (file.download_link) {
						html += 	'<p><a href="' + file.download_link + '" target="_blank">' + file.name + '</a></p>';
					} else {
						html += 	'<p>' + file.name + '</p>';
					}
					html += 	'<input name="file_picker_' + field_id + '_' + (++fileNumber) + '" type="hidden" value="' + file.id + '" />';
					html += 	'<span class="delete_file_button">' + zenario.phrase('zenario_user_forms', 'Delete') + '</span>';
					html += '</div>';
					$(that).find('.files').append($(html));
					
					if (count >= maxNumberOfFiles) {
						$fileUploadButton.hide();
					}
				});
				
				// Init file buttons
				$(that).find('.delete_file_button').off().on('click', function() {
					$(this).parent().remove();
					count--; 
					if (count < maxNumberOfFiles) {
						$fileUploadButton.show();
					}
				});
				
				
			},
			always: function(e, data) {
				$progressBar.hide();
			},
			processfail: function(e, data) {
				alert(data.files[data.index].error);
			},
			progressall: function(e, data) {
				var progress = parseInt(data.loaded / data.total * 100, 10);
				$progressBar.progressbar('option', 'value', progress);
				
			},
			messages: {
				acceptFileTypes: zenario.phrase('zenario_user_forms', 'Allowed file types: [[types]]', {types: extensions}),
				maxFileSize: zenario.phrase('zenario_user_forms', 'File exceeds maximum allowed size of 10MB'),
				maxNumberOfFiles: zenario.phrase('zenario_user_forms', 'Maximum number of files exceeded')
			}
		});
		
		// Get existing files
		var data = {};
		var filesJSON = $(this).find('.loaded_files').text();
		data.files = JSON.parse(filesJSON);
		
		// Add existing files
		if (data.files.length > 0) {
			count = data.files.length;
			$fileupload.fileupload('option', 'done').call($fileupload, $.Event('done'), {result: data});
		}
	});
	
};
