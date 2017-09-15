(function(module) {
	'use strict';
	
	module.initForm = function(containerId, slotName, ajaxURL, formHTML, formFinalSubmitSuccessfull, inFullScreen, allowProgressBarNavigation, page, maxPageReached, showLeavingPageMessage, isErrors, phrases) {
		this.containerId = containerId;
		var that = this;
		
		phrases = JSON.parse(phrases);
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
		            that.submitForm(containerId, {'target_page': targetPage}, true);
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
		
		//Init navigation buttons
		$('#' + containerId + '_user_form .form_buttons .next.submit').on('click', function() {
			that.submitForm(containerId, {submitForm: true}, true);
		});
		$('#' + containerId + '_user_form .form_buttons .next:not(.submit)').on('click', function() {
			that.submitForm(containerId, {next: true}, true);
		});
		$('#' + containerId + '_user_form .form_buttons .previous').on('click', function() {
			that.submitForm(containerId, {previous: true}, true);
		});
		
		$('#' + containerId + ' input.saveLater').on('click', function() {
			var message = $(this).data('message');
			if (confirm(message)) {
				that.submitForm(containerId, {saveLater: true});
			}
		});
		
		$('#' + containerId + '_user_form .field_attachment .remove_attachment').on('click', function() {
			var fieldId = $(this).data('id');
			var request = {};
			request['remove_attachment_' + fieldId] = true;
			that.submitForm(containerId, request);
		});
		
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
			if (el.id) {
				el.value = $.datepicker.formatDate(zenario.dpf, $.datepicker.parseDate('yy-mm-dd', get(el.id + '__0').value));
				var options = {
					dateFormat: zenario.dpf,
					altField: '#' + el.id + '__0',
					altFormat: 'yy-mm-dd',
					showOn: 'focus',
					onClose: function(dateText, inst) {
						//After closing the datepicker, make sure the entered date is valid. If not, change back to the previous valid date or set to null if none.
						var value = get(el.id + '__0').value;
						var formattedValue = $.datepicker.formatDate(zenario.dpf, $.datepicker.parseDate('yy-mm-dd', get(el.id + '__0').value));
						
						if (!dateText || !formattedValue) {
							$datepicker.datepicker('setDate', null);
						} else if (dateText && formattedValue) {
							el.value = formattedValue;
						}
					}
				};
				if ($(this).data('selectors')) {
					options.changeMonth = true;
					options.changeYear = true;
					options.yearRange = "c-100:c+5";
				}
				if ($(this).data('no_past_dates')) {
					options.minDate = new Date;
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
			module.submitForm(containerId, {'filter': 1});
		});
		//Init visible on condition fields
		$('#' + containerId + '_user_form .form_field.visible_on_condition, #' + containerId + '_user_form .repeat_block.visible_on_condition, .page_switcher li.visible_on_condition').each(function(i, el) {
			var that = this,
				cFields = $(this).data('cfields'),
				fieldId = $(this).data('id');
			
			if (cFields.length) {
				var hidden = false;
				//Visibility can be chained. So one field depends on another, depends on another and so on...
				//so when one of those fields in the chain is changed, each field down to the original field is checked.
				//if any of the closer ones say it's hidden then the field is hidden. Otherwise it uses whatever the changed
				//field says.
				for (var i = 0; i < cFields.length; i++) {
					var cField = cFields[i];
					(function(i) {
						$('#' + containerId + '_field_' + cField.id + ' :input').on('change', function() {
							var visible = true;
							for (var j = i; j < cFields.length; j++) {
								var tcField = cFields[j];
								var $tcField = $('#' + containerId + '_field_' + tcField.id + ' :input');
								if (tcField.type == 'checkboxes') {
									var values = [], cFieldValues;
									$tcField.each(function() {
										if ($(this).is(':checked')) {
											values.push($(this).data('value'));
										}
									});
									cFieldValues = tcField.value ? _.map(tcField.value.toString().split(','), Number) : [];
									
									if (tcField.operator == 'AND') {
										var sharedValues = _.intersection(values, cFieldValues);
										var selectedRequiredValues = _.intersection(values, sharedValues);
						
										visible = _.isEqual(selectedRequiredValues, cFieldValues);
									} else {
										visible = false;
										for (var k = 0; k < values.length; k++) {
											if (cFieldValues.indexOf(values[k]) != -1) {
												visible = true;
												break;
											}
										}	
									}
								} else {
									var value;
									if ($tcField.is(':checkbox')) {
										value = $tcField.is(':checked');
									} else if (tcField.type == 'radios') {
										value = $tcField.filter(':checked').val();
									} else {
										value = $tcField.val();
									}
							
									visible = Boolean((tcField.value === '' && value) || (tcField.value !== '' && (tcField.value == value)));
								}
								
								visible = tcField.invert ? !visible : visible;
								if (!visible || (j == cFields.length - 1)) {
									$(that).toggle(visible);
									break;
								}
							}
						});
					})(i);
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
			return (value === '' || isNaN(value) || value.toString().toLowerCase().indexOf('e') > -1);
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
									if (!fieldValue) {
										fieldValue = 0;
									}
									if (checkFieldValueIsNaN(fieldValue)) {
										xIsNaN = true;
										break;
									}
									//If this field is in a repeat block then it's the sum of all repeat fields
									if ($fields[j]._repeatedFields && $fields[j]._repeatedFields.length) {
										var repeatFieldValues = [fieldValue];
										for (var k = 0; k < $fields[j]._repeatedFields.length; k++) {
											var repeatFieldValue = $fields[j]._repeatedFields[k].val();
											if (!repeatFieldValue) {
												repeatFieldValue = 0;
											}
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
				module.submitForm(containerId, {'add_repeat_row': blockId});
			});
			$(this).find('div.delete').on('click', function() {
				var row = $(this).data('row');
				module.submitForm(containerId, {'delete_repeat_row': blockId, 'row': row});
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
							module.submitForm(containerId, {'filter': 1});
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
		
		//Init file picker fields
		(function() {
			function redrawFiles(fieldId, files) {
				var orderedFiles = getOrderedFiles(files),
					html = zenario.microTemplate('file_upload_row', orderedFiles),
					$files = $('#' + containerId + '_field_' + fieldId + ' .files');
				
				$files.html(html);
				
				//Delete button
				$files.find('.file_row .delete').on('click', function() {
					if (confirm(phrases.delete_file)) {
						var fileId = $(this).parent().data('id');
						delete(files[fileId]);
						redrawFiles(fieldId, files);
					}
				});
				
				//Update files input
				$('input[name="field_' + fieldId + '"]').val(JSON.stringify(files));
			};
			function getOrderedFiles(files) {
				var orderedFiles = [];
				for (var fileId in files) {
					orderedFiles.push(files[fileId]);
				}
				orderedFiles.sort(sortByOrd);
				return orderedFiles;
			};
			function updateProgressBar(fieldId, popupClass, width, show) {
				$('#' + containerId + '_field_' + fieldId +  + ' .progress').toggle(show).find('.progress_bar').css('width', width + '%');
			};
			
			$('#' + containerId + '_user_form .field_file_picker:not(.readonly)').each(function() {
				var fieldId = $(this).data('id');
				var files = JSON.parse($('input[name="field_' + fieldId + '"]').val());
				if (!files) {
					files = {};
				}
				redrawFiles(fieldId, files);
				
				$(this).find('.file_picker_field').fileupload({
					url: ajaxURL + '&fileUpload=1',
					dataType: 'json',
					start: function(e) {
						updateProgressBar(fieldId, 0, true);
					},
					progressall: function(e, data) {
						var progress = parseInt(data.loaded / data.total * 100, 10);
						updateProgressBar(fieldId, progress, true);
					},
					done: function (e, data) {
						var orderedFiles = getOrderedFiles(files),
							ord = orderedFiles.length && orderedFiles[orderedFiles.length - 1].ord ? orderedFiles[orderedFiles.length - 1].ord : 1;
						$.each(data.result.files, function(index, file) {
							ord++;
							file.id = 't' + ord;
							file.ord = ord;
							files[file.id] = file;
						});
						redrawFiles(fieldId, files);
					},
					stop: function(e) {
						updateProgressBar(fieldId, 0, false);
					}
				});
				
				
			});
		})();
		
		//Init PDF upload fields
		(function() {
			function redrawFiles(fieldId, files) {
				var orderedFiles = getOrderedFiles(files),
					html = zenario.microTemplate('document_upload_row', orderedFiles),
					$files = $('#' + containerId + '_field_' + fieldId + ' .popup_1 .files');
				
				$files.html(html);
				
				//Delete button
				$files.find('.file_row .delete').on('click', function() {
					if (confirm(phrases.delete_file)) {
						var fileId = $(this).parent().data('id');
						delete(files[fileId]);
						redrawFiles(fieldId, files);
					}
				});
			};
			function redrawFileFragments(fieldId, files) {
				var orderedFiles = getOrderedFiles(files),
					html = zenario.microTemplate('document_upload_uploaded_file', orderedFiles),
					$files = $('#' + containerId + '_field_' + fieldId + ' .popup_2 .files');
				
				$files.html(html);
				
				//Delete button
				$files.find('.file_row .delete').on('click', function() {
					if (confirm(phrases.delete_file)) {
						var fileId = $(this).parent().data('id');
						delete(files[fileId]);
						redrawFileFragments(fieldId, files);
					}
				});
				//Rotate button
				$files.find('.file_row .rotate').on('click', function() {
					var fileId = $(this).parent().data('id');
					if (!files[fileId].rotate) {
						files[fileId].rotate = 0;
					}
					files[fileId].rotate = (files[fileId].rotate + 90) % 360;
					var requests = {
						file: JSON.stringify(files[fileId])
					};
					
					$files.find('.file_' + fileId + ' .icon img').css({
						'transform':         'rotate(' + files[fileId].rotate + 'deg)',
						'-ms-transform':     'rotate(' + files[fileId].rotate + 'deg)',
						'-moz-transform':    'rotate(' + files[fileId].rotate + 'deg)',
						'-webkit-transform': 'rotate(' + files[fileId].rotate + 'deg)',
						'-o-transform':      'rotate(' + files[fileId].rotate + 'deg)'
					});
				});
				//Reorder
				if (orderedFiles.length) {
					$files.sortable({
						containment: 'parent',
						tolerance: 'pointer',
						items: 'div.file_row',
						start: function(event, ui) {
							that.startIndex = ui.item.index();
						},
						stop: function(event, ui) {
							if (that.startIndex != ui.item.index()) {
								$files.find('.file_row').each(function(i) {
									var fileId = $(this).data('id');
									files[fileId].ord = i + 1;
								});
							}
						}
					});
				}
			};
			function getOrderedFiles(files) {
				var orderedFiles = [];
				for (var fileId in files) {
					orderedFiles.push(files[fileId]);
				}
				orderedFiles.sort(sortByOrd);
				return orderedFiles;
			};
			function updateProgressBar(fieldId, popupClass, width, show) {
				$('#' + containerId + '_field_' + fieldId + ' .' + popupClass + ' .progress').toggle(show).find('.progress_bar').css('width', width + '%');
			};
			function closePopup($overlay, files) {
				var orderedFiles = getOrderedFiles(files);
				if (orderedFiles.length) {
					if (confirm(phrases.are_you_sure_message)) {
						$overlay.hide();
					}
				} else {
					$overlay.hide();
				}
			};
			$('#' + containerId + '_user_form .field_document_upload').each(function() {
				var that = this,
					fieldId = $(this).data('id'),
					$overlay1 = $(this).find('.overlay_1'),
					$overlay2 = $(this).find('.overlay_2'),
					$popup1FileList = $(this).find('.popup_1 .files'),
					$popup2FileList = $(this).find('.popup_2 .files'),
					$filesInput = $(this).find('input[name="field_' + fieldId + '"]'),
					combinedFilename = $(this).data('filename'),
					files = {},
					fileFragments = {};
				
				$(this).find('.open_popup_1').on('click', function() {
					if ($filesInput.val()) {
						files = JSON.parse($filesInput.val());
					}
					if (!files) {
						files = {};
					}
					redrawFiles(fieldId, files);
					$overlay1.show();
				});
				
				$(this).find('.open_popup_2').on('click', function() {
					fileFragments = {};
					redrawFileFragments(fieldId, fileFragments);
					$overlay2.show();
				});
				
				$(this).find('.popup_1 .close').on('click', function() {
					closePopup($overlay1, files);
				});
				
				$(this).find('.popup_2 .close').on('click', function() {
					closePopup($overlay2, fileFragments);
				});
				
				window.onclick = function() {
					if (event.target == $overlay1[0]) {
						closePopup($overlay1, files);
					} else if (event.target == $overlay2[0]) {
						closePopup($overlay2, fileFragments);
					}
				};
				
				$(this).find('.popup_1 .upload_complete_files').fileupload({
					url: ajaxURL + '&fileUpload=1',
					dataType: 'json',
					dropZone: $popup1FileList,
					start: function(e) {
						updateProgressBar(fieldId, 'popup_1', 0, true);
					},
					progressall: function(e, data) {
						var progress = parseInt(data.loaded / data.total * 100, 10);
						updateProgressBar(fieldId, 'popup_1', progress, true);
					},
					done: function (e, data) {
						var orderedFiles = getOrderedFiles(files),
							ord = orderedFiles.length && orderedFiles[orderedFiles.length - 1].ord ? orderedFiles[orderedFiles.length - 1].ord : 1;
						$.each(data.result.files, function(index, file) {
							ord++;
							file.id = 't' + ord;
							file.ord = ord;
							files[file.id] = file;
						});
						redrawFiles(fieldId, files);
					},
					stop: function(e) {
						updateProgressBar(fieldId, 'popup_1', 0, false);
					}
				});
				
				$(this).find('.popup_1 .save').on('click', function() {
					var orderedFiles = getOrderedFiles(files),
						html = '', 
						fileList = [],
						i, file;
					for (i = 0; i < orderedFiles.length; i++) {
						var file = orderedFiles[i];
						fileList.push('<a href="' + file.path + '" target="_blank">' + file.name + '</a>');
					}
					html = fileList.join(', ');
					$(that).find('.files_preview').html(html);
					
					$filesInput.val(JSON.stringify(files));
					$overlay1.hide();
					$overlay2.hide();
				});
				
				$(this).find('.popup_2 .upload_file_fragments').fileupload({
					url: ajaxURL + '&fileUpload=1&thumbnail=1',
					dataType: 'json',
					dropZone: $popup2FileList,
					start: function(e) {
						updateProgressBar(fieldId, 'popup_2', 0, true);
					},
					progressall: function(e, data) {
						var progress = parseInt(data.loaded / data.total * 100, 10);
						updateProgressBar(fieldId, 'popup_2', progress, true);
					},
					done: function (e, data) {
						var orderedFiles = getOrderedFiles(fileFragments),
							ord = orderedFiles.length && orderedFiles[orderedFiles.length - 1].ord ? orderedFiles[orderedFiles.length - 1].ord : 1;
						$.each(data.result.files, function(index, file) {
							ord++;
							file.id = 't' + ord;
							file.ord = ord;
							fileFragments[file.id] = file;
						});
						redrawFileFragments(fieldId, fileFragments);
					},
					stop: function(e) {
						updateProgressBar(fieldId, 'popup_2', 0, false);
					}
				});
				
				$(this).find('.popup_2 .combine').on('click', function() {
					var orderedFiles = getOrderedFiles(fileFragments);
					if (orderedFiles.length) {
						var button = this,
							name;
						$(button).val(phrases.combining);
						
						//If using a default filename, add a number to keep it unique
						if (combinedFilename) {
							var name = combinedFilename,
								number = 1,
								orderedCompleteFiles = getOrderedFiles(files),
								regexp = new RegExp(name + "(?:_(\\d+))?\.pdf$"),
								matches = false, 
								i;
							
							for (i = 0; i < orderedCompleteFiles.length; i++) {
								matches = orderedCompleteFiles[i].name.match(regexp);
								if (matches) {
									number = +matches[1] + 1;
								}
							}
							name += '_' + number;
						} else {
							name = $(that).find('.filename').val();
						}
						
						var requests = {
							name: name,
							files: JSON.stringify(orderedFiles)
						};
						zenario.ajax(ajaxURL + '&combineFiles=1', requests).after(function(response) {
							var newFile = JSON.parse(response);
							if (newFile && newFile.path) {
								var orderedFiles = getOrderedFiles(files),
									ord = orderedFiles.length && orderedFiles[orderedFiles.length - 1].ord ? orderedFiles[orderedFiles.length - 1].ord : 1;
								ord++;
								newFile.id = 't' + ord;
								newFile.ord = ord;
								files[newFile.id] = newFile;
								
								redrawFiles(fieldId, files);
								$(that).find('.filename').val(combinedFilename ? combinedFilename : 'my-combined-file');
								$overlay2.hide();
								
								fileFragments = {};
								redrawFileFragments(fieldId, []);
							}
							$(button).val(phrases.combine);
						});
					}
				});
			});
		})();
		
		function sortByOrd(a, b) {
			if (a.ord < b.ord) 
				return -1;
			if (a.ord > b.ord)
				return 1;
			return 0;
		};
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
	
	module.submitForm = function(containerId, values, scrollToTop) {
		var $form = $('#' + containerId + '_user_form form');
		if (!scrollToTop) {
			zenario.blockScrollToTop = true;
		}
		if (values) {
			for (var i in values) {
				$form.append('<input type="hidden" name="' + i + '" value="' + values[i] + '"/>');
			}
		}
		
		//Remove ajax file inputs to make this an ajax reload rather than a page reload
		$('#' + containerId + '_user_form .field_document_upload, #' + containerId + '_user_form .field_file_picker').each(function() {
			$(this).find('input[type="file"]').remove();
		});
		
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