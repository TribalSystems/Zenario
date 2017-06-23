(function(module) {
	'use strict';
	
	module.initForm = function(containerId, slotName, ajaxURL, formHTML, formFinalSubmitSuccessfull, inFullScreen, allowProgressBarNavigation, page, maxPageReached, showLeavingPageMessage, isErrors) {
		this.containerId = containerId;
		var that = this;
		
		if (isErrors) {
			$('#' + containerId + '_user_form').effect("shake", {distance: 10, times: 3, duration: 300});
		}
		
		$('#' + containerId + '_user_form form').on('submit', function() {
		    window.onbeforeunload = null;
		});
		
		this.startPoking();
		
		if (showLeavingPageMessage) {
            if (maxPageReached > 1) {
                window.onbeforeunload = function() {
                    return true;
                }
            }
            if (formFinalSubmitSuccessfull) {
                window.onbeforeunload = null;
            }
        }
		
		if (allowProgressBarNavigation) {
		    $('#' + containerId + ' .page_switcher li.step').on('click', function() {
		        var targetPage = $(this).data('page');
		        if (targetPage <= maxPageReached && targetPage != page) {
		            window.onbeforeunload = null;
		            that.submitForm(containerId, slotName, {'target_page': targetPage}, true);
		        }
		    });
		}
		
		//Fix for modal forms with file inputs
		if (formHTML) {
			$.colorbox({
				transition: 'none',
				html: formHTML,
				escKey: false,
				overlayClose: false,
				onOpen: function() {
					var cb = get('colorbox');
					cb.className = module.moduleClassName;
					$(cb).hide().fadeIn();
				}
			});
			zenario.resizeColorbox();
		}
		//Init print page button
		$('#' + containerId + '_print_page').on('click', function() {
			that.printFormPage(containerId);
		});
		
		//Init fullscreen button
		var $fullScreenButton = $('#' + containerId + '_fullscreen');
		var $fullScreenInput = $('#' + containerId + ' input[name="inFullScreen"]');
		$fullScreenButton.on('click', function() {
			$('#ui-datepicker-div').detach().appendTo('#' + containerId);
			zenario.enableFullScreen($('#' + containerId)[0]);
		});
		$(document).on(zenario.fullScreenChangeEvent, function() {
			var isFullScreen = zenario.isFullScreen();
			$fullScreenButton.toggle(!isFullScreen);
			$fullScreenInput.val(isFullScreen ? 1 : 0);
			$('#' + containerId + '_form_wrapper').toggleClass('in_fullscreen', isFullScreen);
		});
		if (formFinalSubmitSuccessfull) {
			zenario.exitFullScreen();
		}
		
		//Init date pickers
		$('#' + containerId + '_user_form input.jquery_form_datepicker').each(function(i, el) {
			zenario.loadDatePicker();
			if (el.id) {
				el.value = $.datepicker.formatDate(zenario.dpf, $.datepicker.parseDate('yy-mm-dd', get(el.id + '__0').value));
				var options = {
					dateFormat: zenario.dpf,
					altField: '#' + el.id + '__0',
					altFormat: 'yy-mm-dd',
					showOn: 'focus'
				};
				if ($(this).data('selectors')) {
					options.changeMonth = true;
					options.changeYear = true;
					options.yearRange = "c-100:c+5";
				}
				
				var $datepicker = $('#' + el.id);
				$datepicker.datepicker(options);
				
				$('#' + el.id + '__clear').on('click', function() {
					$datepicker.datepicker('setDate', null);
				});
				
				if (inFullScreen && zenario.isFullScreen()) {
					$('#ui-datepicker-div').detach().appendTo('#' + containerId);
				}
			}
		});
		//Init select list source fields
		$('#' + containerId + '_user_form select.source_field').on('change', function() {
			module.submitForm(containerId, slotName, {'filter': 1});
		});
		//Init visible on condition fields
		$('#' + containerId + '_user_form .form_field.visible_on_condition, #' + containerId + '_user_form .repeat_block.visible_on_condition, .page_switcher li.visible_on_condition').each(function(i, el) {
			var that = this,
				cFieldId = $(this).data('cfieldid'),
				cFieldValue = $(this).data('cfieldvalue'),
				cFieldInvert = $(this).data('cfieldinvert'),
				cFieldType = $(this).data('cfieldtype'),
				cFieldOperator = $(this).data('cfieldoperator'),
				fieldId = $(this).data('id');
			
			if (cFieldId) {
				if (cFieldType == 'checkboxes') {
					$('#' + containerId + '_field_' + cFieldId + ' input').on('change', function() {
						var values = [], cFieldValues, visible, i;
						$('#' + containerId + '_field_' + cFieldId + ' input').each(function() {
							if ($(this).is(':checked')) {
								values.push($(this).data('value'));
							}
						});
						cFieldValues = cFieldValue ? _.map(cFieldValue.toString().split(','), Number) : [];
						
						if (cFieldOperator == 'AND') {
							var sharedValues = _.intersection(values, cFieldValues);
							var selectedRequiredValues = _.intersection(values, sharedValues);
							
							visible = _.isEqual(selectedRequiredValues, cFieldValues);
						} else {
							for (i = 0; i < values.length; i++) {
								if (cFieldValues.indexOf(values[i]) != -1) {
									visible = true;
									break;
								}
							}	
						}
						
						visible = cFieldInvert ? !visible : visible;
						$(that).toggle(visible);
					});
				} else {
					$('#' + containerId + '_field_' + cFieldId + ' :input').on('change', function() {
						var value;
						if ($(this).is(':checkbox')) {
							value = $(this).is(':checked');
						} else {
							value = $(this).val();
						}
						
						var visible = (cFieldValue === '' && value) || (cFieldValue !== '' && (cFieldValue == value));
						visible = cFieldInvert ? !visible : visible;
						$(that).toggle(visible);
					});
				}
			}
		});
		//Init restatement (mirror) fields
		$('#' + containerId + '_user_form .form_field.restatement').each(function(i, el) {
			var that = this;
			var fieldId = $(this).data('fieldid');
			if (fieldId) {
				var $field = $('#' + containerId + '_user_form .form_field :input[name="field_' + fieldId + '"]');
				//If field is on same form page update in real time
				if ($field.length > 0) {
					
					if ($field.is('input')) {
						$field.on('keyup', function() {
							$(that).find(':input').val($(this).val());
						});
					} else if ($field.is('select')) {
						$field.on('change', function() {
							$(that).find(':input').val($(this).val() === '' ? '' : $(this).find('option:selected').text());
						});
					}
				}
			}
		});
		
		var checkFieldValueIsNaN = function(value) {
			return (value == '' || isNaN(value) || value.toLowerCase().indexOf('e') > -1);
		};
		
		//Init calculated fields
		$('#' + containerId + '_user_form .form_field.calculated').each(function(i, el) {
			var $calc = $(this).find('input');
			var prefix = $(this).data('prefix');
			var postfix = $(this).data('postfix');
			var inRepeatBlock = $calc.data('repeated');
			var repeatBlock = $calc.data('repeated_row');
			var repeatBlockId = $calc.data('repeat_id');
			var calculationCode = $('#' + containerId + '_field_' + $(el).data('id') + '_calculation_code').text();
			if (calculationCode) {
				calculationCode = JSON.parse(calculationCode);
				var fieldValues = {};
				var equation = '';
				if (calculationCode) {
					
					var equation = that.buildFieldCalculation(containerId, calculationCode, fieldValues);
					
					if (equation) {
						var $field, $repeatFields, $fields = {};
						for (i in fieldValues) {
							$field = $('#' + containerId + '_user_form .form_field input[name="field_' + i + '"]');
							//Find all the fields on the page so we can add a keyup event to update the calculated field
							if ($field.length > 0) {
								//Target field and calculated field are in the same repeat block
								if ($field.data('repeated') && inRepeatBlock && ($field.data('repeat_id') == repeatBlockId)) {
									if (repeatBlock > 1) {
										$field = $('#' + containerId + '_user_form .form_field input[name="field_' + i + '_' + repeatBlock + '"]');
										if ($field.length == 0) {
											continue;
										}	
									}
									$fields[i] = $field;
									continue;
								}
								//Target field is repeated
								if ($field.data('repeated')) {
									$field._repeatedFields = [];
									$repeatFields = $('#' + containerId + '_user_form .form_field.field_' + i + '_repeat input');
									$repeatFields.each(function(index, e) {
										$field._repeatedFields.push($(this));
										$fields[i + '_' + index] = $(this);
									});
								}
								$fields[i] = $field;
							//Add fields not on page to equation
							} else {
								equation = equation.replace('[[FIELD_' + i + ']]', +fieldValues[i]);
							}
						}
						
						var maxNumberSize = 999999999999999;
						var minNumberSize = -1 * maxNumberSize;
						var j, fieldValue, search;
						for (i in $fields) {
							$fields[i].on('keyup', function() {
								var equationWithMergeFields = equation;
								var xIsNaN = false;
								var x = 0;
								
								for (j in $fields) {
									fieldValue = $fields[j].val();
									if (checkFieldValueIsNaN(fieldValue)) {
										xIsNaN = true;
										break;
									}
									//If this field is in a repeat block then it's the sum of all repeat fields
									if ($fields[j]._repeatedFields && $fields[j]._repeatedFields.length) {
										var repeatFieldValues = [fieldValue];
										for (var k = 0; k < $fields[j]._repeatedFields.length; k++) {
											var repeatFieldValue = $fields[j]._repeatedFields[k].val();
											if (checkFieldValueIsNaN(repeatFieldValue)) {
												xIsNaN = true;
												break;
											}
											repeatFieldValues.push(repeatFieldValue);
										}
										fieldValue = '(' + repeatFieldValues.join(' + ') + ')';
									}
									
									search = '\\[\\[FIELD_' + j + '\\]\\]';
									equationWithMergeFields = equationWithMergeFields.replace(new RegExp(search, 'g'), fieldValue);
								}
								
								if (!xIsNaN) {
									x = Parser.evaluate(equationWithMergeFields);
									if (!_.isFinite(x) || (x > maxNumberSize) || (x < minNumberSize)) {
										xIsNaN = true;
									}
								}
								if (xIsNaN) {
									x = 'NaN';
								} else {
									x = +x.toFixed(2);
									if (prefix) {
										x = prefix + '' + x;
									}
									if (postfix) {
										x = x + '' + postfix;
									}
								}
								
								$calc.val(x);
								
								//Update any restatement fields that target this field on the page
								$('#' + containerId + '_user_form .form_field.restatement[data-fieldid="' + $(el).data('id') + '"] :input').val(x);
							});
						}
					}
				}
			}
		});
		
		//Init repeat blocks
		$('#' + containerId + '_user_form .repeat_block').each(function(i, el) {
			var blockId = $(this).data('id');
			$(this).find('div.add').on('click', function() {
				module.submitForm(containerId, slotName, {'add_repeat_row': blockId});
			});
			$(this).find('div.delete').on('click', function() {
				var row = $(this).data('row');
				module.submitForm(containerId, slotName, {'delete_repeat_row': blockId, 'row': row});
			});
		});
		//Init autocomplete lists
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
						//If this is a source field, reload the form to filter any target fields
						if ($(that).data('source_field')) {
							module.submitForm(containerId, slotName, {'filter': 1});
						}
					},
					focus: function(event, ui) {
						this.value = ui.item.label;
      					event.preventDefault();
					},
					change: function(event, ui) {
						//If finish typing an option without selecting, search for that value and select it
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
		//TODO improve this
		//Init file picker fields
		$('#' + containerId + '_user_form .field_file_picker').each(function() {
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
			
			//Build regex from extensions for file type validation
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
			
			//Init progress bar
			$progressBar.progressbar();
			
			//Init file uploader
			$fileupload.fileupload({
				url: ajaxURL + '&filePickerUpload=1',
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
					
					//Add new files
					$.each(data.result.files, function(i, file) {
						
						var html = '';
						html += '<div class="file_row">';
						if (file.download_link) {
							html += 	'<p><a href="' + file.download_link + '" target="_blank">' + file.name + '</a></p>';
						} else {
							html += 	'<p>' + file.name + '</p>';
						}
						html += 	'<input name="field_' + field_id + '_' + (++fileNumber) + '" type="hidden" value="' + file.id + '" />';
						html += 	'<span class="delete_file_button">' + zenario.phrase('zenario_user_forms', 'Delete') + '</span>';
						html += '</div>';
						$(that).find('.files').append($(html));
						
						if (count >= maxNumberOfFiles) {
							$fileUploadButton.hide();
						}
					});
					
					//Init file buttons
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
			
			//Get existing files
			var data = {};
			var filesJSON = $(this).find('.loaded_files').text();
			data.files = JSON.parse(filesJSON);
			
			//Add existing files
			if (data.files.length > 0) {
				count = data.files.length;
				$fileupload.fileupload('option', 'done').call($fileupload, $.Event('done'), {result: data});
			}
		});
	};
	
	module.printFormPage = function() {
		var htmlToPrint = $('#' + this.containerId + ' .form_fields').html();
		var left = window.screenX + ($(window).width() / 2);
		var top = window.screenY;
		var newWin = window.open('','Print-Window','width=300,height=300,left=' + left + ',top=' + top );
		
		newWin.document.open();
		newWin.document.write('<html><body onload="window.print()">' + htmlToPrint + '</body></html>');
		newWin.document.close();
		
		setTimeout(function(){newWin.close();},10);
	};
	
	module.buildFieldCalculation = function(containerId, calculationCode, fieldValues) {
		var equation = '';
		for (var i = 0; i < calculationCode.length; i++) {
			switch (calculationCode[i]['type']) {
				case 'static_value':
					equation += +calculationCode[i]['value'];
					break;
				case 'field':
					if (calculationCode[i]['v'] == 'NaN') {
						if ($('#' + containerId + '_user_form .form_field input[name="field_' + calculationCode[i]['value'] + '"]').length <= 0) {
							return false;
						}
					}
					fieldValues[calculationCode[i]['value']] = calculationCode[i]['v']; 
					equation += '[[FIELD_' + calculationCode[i]['value'] + ']]';
					break;
				case 'parentheses_open':
					equation += '(';
					break;
				case 'parentheses_close':
					equation += ')';
					break;
				case 'operation_addition':
					equation += '+';
					break;
				case 'operation_subtraction':
					equation += '-';
					break;
				case 'operation_multiplication':
					equation += '*';
					break;
				case 'operation_division':
					equation += '/';
					break;
			}
		}
		return equation;
	};
	
	module.submitForm = function(containerId, slotName, values, scrollToTop) {
		var $form = $('#' + containerId + '_user_form form');
		if (!scrollToTop) {
			zenario.blockScrollToTop = true;
		}
		if (values) {
			for (var i in values) {
				$form.append('<input type="hidden" name="' + i + '" value="' + values[i] + '"/>');
			}
		}
		$form.submit();
	};
	
	module.stopPoking = function() {
        if (module.poking) {
            clearInterval(module.poking);
        }
        module.poking = false;
    };

    module.startPoking = function() {
        if (!module.poking) {
            module.poking = setInterval(module.poke, 2 * 60 * 1000);
        }
    };

    module.poke = function() {
        zenario.ajax(URLBasePath + 'zenario/admin/quick_ajax.php?keep_session_alive=1')
    };
	
})(zenario_user_forms);

zenario_user_forms.recaptchaCallback = function(){
	recaptchaCallback();
}; 