/*
 * Copyright (c) 2017, Tribal Limited
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of Zenario, Tribal Limited nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL TRIBAL LTD BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/*
	This file contains JavaScript source code.
	The code here is not the code you see in your browser. Before this file is downloaded:
	
		1. Compilation macros are applied (e.g. "foreach" is a macro for "for .. in ... hasOwnProperty").
		2. It is minified (e.g. using Google Closure Compiler).
		3. It may be wrapped togther with other files (this is to reduce the number of http requests on a page).
	
	For more information, see js_minify.shell.php for steps (1) and (2), and organizer.wrapper.js.php for step (3).
*/


zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioT, zenarioAB, zenarioAT, zenarioO,
	encodeURIComponent, get, engToBoolean, htmlspecialchars, jsEscape, phrase,
	extensionOf, methodsOf, has,
	panelTypes, extraVar2, s$s
) {
	"use strict";


//Note: extensionOf() and methodsOf() are our shortcut functions for class extension in JavaScript.
	//extensionOf() creates a new class (optionally as an extension of another class).
	//methodsOf() allows you to get to the methods of a class.
var methods = methodsOf(
	panelTypes.form_builder = extensionOf(panelTypes.form_builder_base_class)
);

//Draw the panel, as well as the header at the top and the footer at the bottom
//This is called every time the panel is loaded, refreshed or when something in the header toolbar is changed.
methods.showPanel = function($header, $panel, $footer) {
	$header.html('').hide();
	$footer.html('').show();
	
	this.sortOutItems();
	
	//Load main panel
	var html = this.loadPanelHTML();
	$panel.html(html).show();
	
	//On initial load select the first tab
	if (!this.currentTabId) {
		var tabs = this.getOrderedTabs();
		if (tabs.length > 0) {
			this.currentTabId = tabs[0].id;
		}
	}
	
	this.deletedFields = [];
	this.changeDetected = false;
	this.maxNewCustomField = 1;
	this.maxNewCustomFieldValue = 1;
	
	this.selectedDetailsTab = this.selectedDetailsTab ? this.selectedDetailsTab : false;
	this.selectedFieldId = this.selectedFieldId ? this.selectedFieldId : false;
	this.selectedTabId = this.selectedTabId ? this.selectedTabId : false;
	
	//Load right panel
	this.loadTabsList(this.currentTabId);
	this.loadFieldsList(this.currentTabId);
	
	//Load left panel
	if (this.selectedFieldId) {
		this.loadFieldDetailsPanel(this.selectedFieldId, true);
		this.highlightField(this.selectedFieldId);
	} else if (this.selectedTabId) {
		this.loadTabDetailsPanel(this.selectedTabId, true);
	} else {
		this.loadNewFieldsPanel(true);
	}
	
	//Show Growl message if saved changes
	if (this.changesSaved) {
		this.changesSaved = false;
		var toast = {
			message_type: 'success',
			message: 'Your changes have been saved!'
		};
		zenarioA.toast(toast);
	}
};

methods.sortOutItems = function() {
	var tabId, tab, fieldId, field;
	foreach (this.tuix.items as tabId => tab) {
		foreach (tab.fields as fieldId => field) {
			//This array is turned into an object when parsed. Turn it back into an array...
			if (field.invalid_responses) {
				field.invalid_responses = _.toArray(field.invalid_responses);
			}
			if (field.visible_condition_checkboxes_field_value) {
				field.visible_condition_checkboxes_field_value = _.toArray(field.visible_condition_checkboxes_field_value);
			}
			if (field.mandatory_condition_checkboxes_field_value) {
				field.mandatory_condition_checkboxes_field_value = _.toArray(field.mandatory_condition_checkboxes_field_value);
			}
		}
	}
};

methods.loadPanelHTML = function() {
	var mergeFields = {
		form_title: this.tuix.form_title
	};
	var html = this.microTemplate('zenario_organizer_form_builder', mergeFields);
	return html;
};

methods.loadTabDetailsPanel = function(tabId, stopEffect) {
	this.loadFieldDetailsPanel(tabId, stopEffect, true);
	
	var that = this,
		label, tab, message, fields;
	$('#field__name').on('keyup', function() {
		label = that.getTabLabel($(this).val());
		$('#organizer_form_tab_' + tabId + ' span').text(label);
	});
	
	$('#organizer_remove_form_tab').on('click', function(e) {
		tab = that.tuix.items[tabId];
		if (tab) {
			message = '<p>Are you sure you want to delete this page?</p>';
			fields = that.getOrderedFields(tabId);
			if (fields.length) {
				message += '<p>All fields on this page will be moved onto the previous page.</p>';
			}
			zenarioA.floatingBox(message, 'Delete', 'warning', true, false, function() {
				that.deleteTab(tabId);
			});
		}
	});
};

methods.loadFieldDetailsPanel = function(fieldId, stopEffect, isTab) {
	var field;
	if (isTab) {
		field = this.tuix.items[fieldId];
	} else {
		field = this.tuix.items[this.currentTabId].fields[fieldId];
	}
	
	//Load HTML
	var mergeFields = {
		mode: 'field_details',
		name: field.name,
		type: this.getFieldReadableType(field.type),
		just_added: field.just_added,
		hide_tab_bar: this.tuix.field_details.hide_tab_bar,
		is_tab: isTab
	};
	if (field.dataset_field_id) {
		mergeFields.type += ', dataset field';
		if (field.db_column) {
			mergeFields.type += ' (' + field.db_column + ')';
		}
	}
	
	var html = this.microTemplate('zenario_organizer_form_builder_left_panel', mergeFields);
	var $div = $('#organizer_form_builder .form_fields_palette .form_fields_palette_outer').html(html);
	
	if (!stopEffect) {
		$div.hide().show('drop', {direction: 'left'}, 200);
	}
	
	//Load TUIX tabs HTML
	var tuix = this.tuix.field_details.tabs;
	var tabs = this.sortAndLoadTUIXTabs(tuix, field, this.selectedDetailsTab);
	var html = this.getTUIXTabsHTML(tabs);
	$('#organizer_field_details_tabs').html(html);
	
	//Add field details tab events
	var that = this;
	$('#organizer_field_details_tabs .tab').on('click', function() {
		var tab = $(this).data('name');
		if (tab && tab != that.selectedDetailsTab) {
			var errors = that.saveCurrentOpenDetails();
			if (!errors) {
				that.loadFieldDetailsTab(tab, fieldId);
			}
		}
	});
	
	//Load TUIX fields html
	this.loadFieldDetailsTab(this.selectedDetailsTab, fieldId);
	
	//Add panel events
	
	//Go to new fields panel
	$('#organizer_field_details_inner span.add_new_field').on('click', function() {
		var errors = that.saveCurrentOpenDetails();
		if (!errors) {
			that.highlightField(false);
			that.loadNewFieldsPanel();
		}
	});
	
	//Editing field name
	$('#zenario_field_details_header_content .edit_field_name_button').on('click', function() {
		$('#zenario_field_details_header_content .view_mode').hide();
		$('#zenario_field_details_header_content .edit_mode').show();
	});
	$('#zenario_field_details_header_content .done_field_name_button').on('click', function() {
		$('#zenario_field_details_header_content .edit_mode').hide();
		$('#zenario_field_details_header_content .view_mode').show();
		
		var name = $('#zenario_field_details_header_content .edit_mode input[name="name"]').val();
		field.name = name;
		$('#zenario_field_details_header_content .view_mode h5').text(name);
	});
	$('#field__name').on('keyup', function() {
		that.changeMadeToPanel();
	});
	
	//Edit form settings
	$('input.form_settings').on('click', function() {
		that.openFormSettings();
	});
};

methods.openFormSettings = function() {
	zenarioAB.open(
		'zenario_user_form', 
		{id: zenarioO.pi.refiner.id},
		undefined, undefined,
		function(key, values) {
			//Update form title (note if lots of changes might be better to redraw entire form)
			var title = '';
			if (values.details.show_title) {
				title = values.details.title;
			}
			this.tuix.title = title;
			$('#organizer_form_builder .form_outer .form_header h5').text(title);
		}
	);
};

methods.loadTabDetailsTab = function(tab, tabId, errors) {
	this.loadFieldDetailsTab(tab, tabId, errors);
};

methods.loadFieldDetailsTab = function(tab, fieldId, errors) {
	this.selectedDetailsTab = tab;
	var item;
	if (this.selectedFieldId && this.tuix.items[this.currentTabId].fields[fieldId]) {
		item = this.tuix.items[this.currentTabId].fields[fieldId]
	} else if (this.selectedTabId && this.tuix.items[fieldId]) {
		item = this.tuix.items[fieldId];
		//Final page must be visible
		var tabs = this.getOrderedTabs();
		for (var i = 0; i < tabs.length; i++) {
			if (tabs[i].id == item.id) {
				if (i == (tabs.length - 1)) {
					item.visibility = 'visible';
				}
				break;
			}
		}
	}
	
	var path = 'field_details/' + tab;
	var tuix = this.tuix.field_details.tabs[tab].fields;
	var fields = this.loadFields(path, tuix, item);
	var formattedFields = this.formatFieldDetails(fields, item, 'field', tab);
	var sortedFields = this.sortFields(formattedFields, item);
	var html = '';
	
	//Add any errors
	if (!_.isEmpty(errors)) {
		errors = this.sortErrors(errors);
		for (var i = 0; i < errors.length; i++) {
			html += '<p class="error">' + errors[i] + '</p>';
		}
	}
	
	html += this.getTUIXFieldsHTML(sortedFields);
	$('#organizer_field_details').html(html);
	$('#organizer_field_details_tabs .tab').removeClass('on');
	$('#field_tab__' + tab).addClass('on');
	if (!_.isEmpty(errors)) {
		$('#organizer_field_details_form').effect({
			effect: 'bounce',
			duration: 125,
			direction: 'right',
			times: 2,
			distance: 5,
			mode: 'effect'
		});
	}
	
	//Add events
	
	var that = this;
	
	if (tab == 'details') {
		$('#field__field_label').on('keyup', function() {
			$('#organizer_form_field_' + fieldId + ' .label').text($(this).val());
			if (item.just_added) {
				$('#field__name').val($(this).val());
			}
		});
		
		$('#field__note_to_user').on('keyup', function() {
			var value = $(this).val().trim();
			$('#organizer_form_field_note_below_' + fieldId + ' div.zenario_note_content').text(value);
			$('#organizer_form_field_note_below_' + fieldId).toggle(value !== '');
		});
		
		if (item.type == 'text' || item.type == 'textarea') {
			$('#field__placeholder').on('keyup', function() {
				$('#organizer_form_field_' + fieldId + ' :input').prop('placeholder', $(this).val());
			});
		} else if (item.type == 'section_description') {
			$('#field__description').on('keyup', function() {
				$('#organizer_form_field_' + fieldId + ' .description').text($(this).val());
			});
		} else if (item.type == 'calculated') {
			$('#edit_field_calculation').on('click', function() {
				//Get all numeric text fields
				var numericFields = {};
				foreach (that.tuix.items as var tTabId => tTab) {
					foreach (tTab.fields as var tFieldId => var tField) {
						if ((tField.type == 'text' && ['number', 'integer', 'floating_point'].indexOf(tField.field_validation) != -1) 
							|| (tField.type == 'calculated' && fieldId != tFieldId)
						) {
							numericFields[tFieldId] = {label: tField.name, ord: tField.ord};
						}
					}
				}
				
				var key = {
					id: fieldId, 
					title: 'Editing the calculation for the field "' + item.name + '"',
					calculation_code: JSON.stringify(item.calculation_code)
				};
				var values = {
					details: {
						dummy_field: JSON.stringify(numericFields)
					}
				};
				
				zenarioAB.open(
					'zenario_field_calculation', 
					key,
					undefined, values,
					function(key, values) {
						if (values.details.calculation_code) {
							item.calculation_code = JSON.parse(values.details.calculation_code);
						} else {
							item.calculation_code = '';
						}
						that.changeMadeToPanel();
						that.saveCurrentOpenDetails();
						that.loadFieldDetailsTab('details', fieldId);
					}
				);
			});
		}
		
	} else if (tab == 'advanced') {
		$('#field__css_classes').on('keyup', function() {
			$('#organizer_form_field_' + fieldId + ' .form_field_classes .css').toggle(!!$(this).val()).prop('title', $(this).val());
		});
		$('#field__div_wrap_class').on('keyup', function() {
			$('#organizer_form_field_' + fieldId + ' .form_field_classes .div').toggle(!!$(this).val()).prop('title', $(this).val());
		});
		
	} else if (tab == 'values') {
		if (formattedFields.values && !formattedFields.values._hidden) {
			var values = this.getOrderedFieldValues(this.currentTabId, fieldId);
			html = this.microTemplate('zenario_organizer_admin_box_builder_field_value', values);
			
			$('#field_values_list').html(html).sortable({
				containment: 'parent',
				tolerance: 'pointer',
				axis: 'y',
				start: function(event, ui) {
					that.startIndex = ui.item.index();
				},
				stop: function(event, ui) {
					if (that.startIndex != ui.item.index()) {
						that.saveFieldListOfValues(fieldId);
						that.loadFieldValuesListPreview(fieldId);
						that.changeMadeToPanel();
					}
				}
			});
			
			$('#field_values_list input').on('keyup', function() {
				var id = $(this).data('id');
				$('#organizer_field_value_' + id + ' label').text($(this).val());
				that.changeMadeToPanel();
			});
			
			//Add new field value
			$('#organizer_add_a_field_value').on('click', function() {
				that.addFieldValue(item);
				that.saveItemTUIXTab('field_details', tab, item);
				that.loadFieldDetailsTab(tab, fieldId);
				that.loadFieldValuesListPreview(fieldId);
				that.changeMadeToPanel();
			});
			
			//Delete field value
			$('#field_values_list .delete_icon').on('click', function() {
				var id = $(this).data('id');
				if (item.lov[id]) {
					if (!item._deleted_lov) {
						item._deleted_lov = [];
					}
					item._deleted_lov.push(id);
					delete(item.lov[id]);
				}
				that.saveItemTUIXTab('field_details', tab, item);
				that.loadFieldDetailsTab(tab, fieldId);
				that.loadFieldValuesListPreview(fieldId);
				that.changeMadeToPanel();
			});
		}
	} else if (tab == 'translations') {
		var transFieldNamesList = _.toArray(this.tuix.field_details.tabs[tab].translatable_fields);
		var transFieldNames = {};
		for (var i = 0; i < transFieldNamesList.length; i++) {
			transFieldNames[transFieldNamesList[i]] = true;
		}
		
		//Currently translations only support fields on the details tab
		tab = 'details';
		path = 'field_details/' + tab;
		tuix = this.tuix.field_details.tabs[tab].fields;
		fields = this.loadFields(path, tuix, item);
		formattedFields = this.formatFieldDetails(fields, item, 'field', tab);
		sortedFields = this.sortFields(formattedFields, item);
		
		var visibleTransFieldNamesList = [];
		foreach (sortedFields as var i => var sField) {
			if (transFieldNames[sField.id] && !sField._hidden) {
				visibleTransFieldNamesList.push(sField.id);
			}
		}
		
		var languages = this.sortLanguages(this.tuix.languages);
		var transFields = [];
		foreach (visibleTransFieldNamesList as var i => var transFieldName) {
			if (formattedFields[transFieldName] && !formattedFields[transFieldName]._hidden) {
				var existingTranslations = {};
				if (item._translations && item._translations[transFieldName]) {
					existingTranslations = item._translations[transFieldName];
				}
				var transField = {};
				if (tuix[transFieldName] && tuix[transFieldName].label) {
					transField.label = tuix[transFieldName].label;
				}
				if (item[transFieldName]) {
					transField.value = item[transFieldName];
				} else {
					transField.value = '(No text is defined in the default language)';
					transField.disabled = true;
				}
				
				transField.phrases = [];
				foreach (languages as var j => var language) {
					if (parseInt(language.translate_phrases)) {
						var phrase = (existingTranslations.phrases && existingTranslations.phrases[language.id] && !transField.disabled) ? existingTranslations.phrases[language.id] : '';
						transField.phrases.push({
							field_column: transFieldName,
							language_id: language.id,
							language_name: language.english_name,
							phrase: phrase,
							disabled: transField.disabled
						});
					}
				}
				transFields.push(transField);
			}
		}
		
		var html = this.microTemplate('zenario_organizer_form_builder_translatable_field', transFields);
		$('#organizer_field_translations').html(html);
		
		$('#organizer_field_translations input').on('keyup', function() {
			that.changeMadeToPanel();
		});
		
	} else if (tab == 'crm') {
		$('#organizer_crm_button__set_labels').on('click', function() {
			$('#organizer_field_crm_values input.crm_value_input').each(function(i, e) {
				var id = $(this).data('id');
				var value = '';
				if (that.tuix.items[that.currentTabId].fields[fieldId].lov && that.tuix.items[that.currentTabId].fields[fieldId].lov[id]) {
					value = that.tuix.items[that.currentTabId].fields[fieldId].lov[id].label;
				}
				$(this).val(value);
			});
		});
		
		$('#organizer_crm_button__set_values').on('click', function() {
			$('#organizer_field_crm_values input.crm_value_input').each(function(i, e) {
				var id = $(this).data('id');
				$(this).val(id);
			});
		});
		
		var mergeFields = {
			values: []
		};
		
		if (item.type == 'checkbox') {
			values = {
				'unchecked': {
					label: 0, 
					ord: 1
				}, 
				'checked': {
					label: 1, 
					ord: 2
				}
			};
			if (item._crm_data && item._crm_data.values) {
				foreach (item._crm_data.values as var valueId => var value) {
					if (values[valueId] && value.crm_value !== undefined) {
						values[valueId].crm_value = value.crm_value;
					}
				}
			}
			foreach (values as var valueId => var value) {
				value.id = valueId;
				mergeFields.values.push(value);
			}
			
			mergeFields.values.sort(this.sortByOrd);
		} else {
			mergeFields.values = this.getOrderedFieldValues(this.currentTabId, fieldId);
		}
		
		var html = this.microTemplate('zenario_organizer_form_builder_crm_values', mergeFields);
		$('#organizer_field_crm_values').html(html);
		
		$('#organizer_field_crm_values input.crm_value_input').on('keyup', function() {
			that.changeMadeToPanel();
		});
	}
};

//Calculation admin box methods
methods.calculationAdminBoxAddSomthing = function(type, value) {
	var code = false;
	switch (type) {
		case 'operation_addition':
		case 'operation_subtraction':
		case 'operation_multiplication':
		case 'operation_division':
		case 'parentheses_open':
		case 'parentheses_close':
			code = {type: type};
			break;
		case 'static_value':
			if (value !== '' && !isNaN(+value)) {
				code = {type: type, value: +value};
				$('#static_value').val('');
			}
			break;
		case 'field':
			code = {type: type, value: value};
			$('#numeric_field').val('');
			break;
	}
	if (code) {
		var calculationCode = [];
		if ($('#calculation_code').val()) {
			calculationCode = JSON.parse($('#calculation_code').val());
		}
		calculationCode.push(code);
		$('#calculation_code').val(JSON.stringify(calculationCode));
		
		this.calculationAdminBoxUpdateDisplay(calculationCode);
	}
};
methods.calculationAdminBoxDelete = function() {
	var calculationCode = [];
	if ($('#calculation_code').val()) {
		calculationCode = JSON.parse($('#calculation_code').val());
		if (calculationCode) {
			calculationCode.pop();
			$('#calculation_code').val(JSON.stringify(calculationCode));
		}
	}
	this.calculationAdminBoxUpdateDisplay(calculationCode);
};
methods.calculationAdminBoxUpdateDisplay = function(calculationCode) {
	var calculationDisplay = this.getCalculationCodeDisplay(calculationCode);
	$('#zenario_calculation_display').text(calculationDisplay);
};
methods.getCalculationCodeDisplay = function(calculationCode) {
	var calculationDisplay = '';
	if (calculationCode) {
		var fields = {};
		foreach (this.tuix.items as var tabId => var tab) {
			foreach (tab.fields as var fieldId => var field) {
				fields[fieldId] = field;
			}
		}
		
		var lastIsParenthesisOpen = false;
		for (var i = 0; i < calculationCode.length; i++) {
			if (!lastIsParenthesisOpen && calculationCode[i].type != 'parentheses_close') {
				calculationDisplay += ' ';
			} else if (lastIsParenthesisOpen) {
				lastIsParenthesisOpen = false;
			}
			
			switch (calculationCode[i].type) {
				case 'operation_addition':
					calculationDisplay += '+';
					break;
				case 'operation_subtraction':
					calculationDisplay += '-';
					break;
				case 'operation_multiplication':
					calculationDisplay += '×';
					break;
				case 'operation_division':
					calculationDisplay += '÷';
					break;
				case 'parentheses_open':
					calculationDisplay += '(';
					lastIsParenthesisOpen = true;
					break;
				case 'parentheses_close':
					calculationDisplay += ')';
					break;
				case 'static_value':
					calculationDisplay += calculationCode[i].value;
					break;
				case 'field':
					var name = '';
					if (fields[calculationCode[i].value]) {
						name = fields[calculationCode[i].value].name;
					} else {
						name = 'UNKNOWN FIELD';
					}
					calculationDisplay += '"' + name + '"';
					break;
			}
			calculationDisplay = calculationDisplay.trim();
		}
	}
	return calculationDisplay;
};


methods.formatFieldDetails = function(fields, item, mode, tab) {
	if (mode == 'field') {
		if (tab == 'details') {
			if (item.dataset_field_id) {
				fields.values_source._disabled = true;
				fields.values_source_filter._readonly = true;
			}
			
			var values_source = fields.values_source._value;
			if (values_source && this.tuix.centralised_lists.values[values_source]) {
				var list = this.tuix.centralised_lists.values[values_source];
				if (!list.info.can_filter) {
					fields.values_source_filter._hidden = true;
				} else {
					fields.values_source_filter.label = list.info.filter_label;
				}
			}
			
			if (item.visibility == 'visible_on_condition' && item.visible_condition_field_id) {
				var conditionField = this.getField(item.visible_condition_field_id);
				if (conditionField) {
					if (conditionField.type == 'checkboxes') {
						fields.visible_condition_checkboxes_field_value.values = conditionField.lov;
						fields.visible_condition_field_value._hidden = true;
						fields.visible_condition_checkboxes_operator._hidden = false;
					} else {
						if (conditionField.type != 'checkbox' && conditionField.type != 'group') {
							fields.visible_condition_field_value.values = conditionField.lov;
							fields.visible_condition_field_value.empty_value = '-- Any value --';
						}
						fields.visible_condition_checkboxes_field_value._hidden = true;
					}
				}
			}
			if (item.readonly_or_mandatory == 'conditional_mandatory' && item.mandatory_condition_field_id) {
				var conditionField = this.getField(item.mandatory_condition_field_id);
				if (conditionField.type == 'checkboxes') {
					fields.mandatory_condition_checkboxes_field_value.values = conditionField.lov;
					fields.mandatory_condition_field_value._hidden = true;
					fields.mandatory_condition_checkboxes_operator._hidden = false;
				} else {
					if (conditionField && conditionField.type != 'checkbox' && conditionField.type != 'group') {
						fields.mandatory_condition_field_value.values = conditionField.lov;
						fields.mandatory_condition_field_value.empty_value = '-- Any value --';
					}
					fields.mandatory_condition_checkboxes_field_value._hidden = true;
				}
			}
			
			if (item.type == 'repeat_start') {
				fields.field_label.label = 'Section heading:';
				fields.field_label.note_below = 'This will be the heading at the start of the repeating section.';
			} else if (item.type == 'calculated') {
				if (item.calculation_code) {
					//Make sure this is an array not an object
					if (!Array.isArray(item.calculation_code)) {
						item.calculation_code = $.map(item.calculation_code, function(i) { return i });
					}
					fields.calculation_code._value = this.getCalculationCodeDisplay(item.calculation_code);
				}
			} else if (item.type == 'page_break') {
				var tabs = this.getOrderedTabs();
				for (var i = 0; i < tabs.length; i++) {
					if (tabs[i].id == item.id) {
						if (i == 0) {
							fields.previous_button_text._hidden = true;
						} else if (i == (tabs.length - 1)) {
							fields.next_button_text._hidden = true;
							fields.visibility._disabled = true;
						}
						break;
					}
				}
			}
			
		} else if (tab == 'advanced') {
			if (item.default_value_options == 'value') {
				if (['radios', 'centralised_radios', 'select', 'centralised_select'].indexOf(item.type) != -1) {
					fields.default_value_lov.values = item.lov;
				}
			}
		}
	}
	return fields;
};

methods.getField = function(fieldId) {
	foreach (this.tuix.items as var tTabId => var tTab) {
		foreach (tTab.fields as var tFieldId => var tField) {
			if (tFieldId == fieldId) {
				return tField;
			}
		}
	}
	return false;
};

methods.getFieldValuesList = function(field) {
	var sortedValues = [];
	var fieldName = field.id;
	var values = field.values;
	var value = field._value;
	var disabled = field._disabled;
	var usesOptGroups = false;
	
	
	if (typeof(values) == 'object') {
		var i = 0;
		foreach (values as var tValueId => var tValue) {
			var option = {
				label: tValue.label,
				value: tValueId,
				ord: ++i
			};
			sortedValues.push(option);
		}
	} else if (values == 'centralised_lists') {
		var i = 0;
		foreach (this.tuix.centralised_lists.values as var func => var details) {
			var option = {
				ord: ++i,
				label: details.label,
				value: func
			};
			sortedValues.push(option);
		}
	} else if (values == 'centralised_list_filter_fields'
		|| values == 'conditional_fields'
		|| values == 'mirror_fields'
	) {
		var tTabs = this.getOrderedTabs();
		usesOptGroups = tTabs.length > 1;
		var ord = 1;
		for (var i = 0; i < tTabs.length; i++) {
			var tFields = this.getOrderedFields(tTabs[i].id);
			var parentIndex = false;
			var tTab = tTabs[i];
			for (var j = 0; j < tFields.length; j++) {
				var tField = tFields[j];
				if (this.canAddFieldToList(values, tField)) {
					if (parentIndex === false && usesOptGroups) {
						parentIndex = sortedValues.push({
							ord: ord++,
							label: tTab.name,
							hasChildren: true,
							options: []
						}) - 1;
					}
					var option = {
						ord: ord++,
						label: tField.name,
						value: tField.id,
						parent: parentIndex
					};
					sortedValues.push(option);
				}
			}
		}
	} else if (values == 'field_lov') {
		if (this.tuix.items[this.currentTabId].fields[this.selectedFieldId] && this.tuix.items[this.currentTabId].fields[this.selectedFieldId].lov) {
			foreach (this.tuix.items[this.currentTabId].fields[this.selectedFieldId].lov as var tValueId => var tValue) {
				var option = {
					ord: tValue.ord,
					label: tValue.label,
					value: tValueId
				};
				sortedValues.push(option);
			}
		}
	}
	
	foreach (sortedValues as var index => var option) {
		if (field.type == 'checkboxes') {
			if (value) {
				if (value.indexOf(option.value) != -1) {
					option.selected = true;
				}
			}
		} else {
			if (option.value == value) {
				option.selected = true;
			}
		}
		if (disabled) {
			option.disabled = true;
		}
		option.name = fieldName;
		option.path = field.path;
		
		if (usesOptGroups && option.parent !== undefined) {
			sortedValues[option.parent].options.push(option);
			delete(sortedValues[index]);
		}
	}
	
	sortedValues.sort(this.sortByOrd);
	return sortedValues;	
};

methods.canAddFieldToList = function(values, field) {
	if (values == 'centralised_list_filter_fields') {
		return (field.id != this.selectedFieldId) && (field.type == 'centralised_select' || (field.type == 'text' && field.autocomplete && field.values_source));
	} else if (values == 'conditional_fields') {
		return (field.id != this.selectedFieldId) && (['checkbox', 'group', 'radios', 'select', 'centralised_radios', 'centralised_select', 'checkboxes'].indexOf(field.type) != -1);
	} else if (values == 'mirror_fields') {
		return (field.id != this.selectedFieldId) && (['text', 'calculated', 'select', 'centralised_select'].indexOf(field.type) != -1);
	}
	return false;
};


methods.loadNewFieldsPanel = function(stopEffect) {
	this.selectedTabId = false;
	this.selectedFieldId = false;
	
	//Load HTML
	var mergeFields = {
		mode: 'new_fields',
		datasetTabs: this.getOrderedDatasetTabsAndFields()
	};
	var html = this.microTemplate('zenario_organizer_form_builder_left_panel', mergeFields);
	var $div = $('#organizer_form_builder .form_fields_palette .form_fields_palette_outer').html(html);
	
	if (!stopEffect) {
		$div.hide().show('drop', {direction: 'right'}, 200);
	}
	
	//Add events
	
	var that = this;
	
	//Allow fields to be dragged onto list
	$('#organizer_field_type_list div.field_type, #organizer_centralised_field_type_list div.field_type, #organizer_linked_field_type_list div.dataset_field').draggable({
		connectToSortable: '#organizer_form_fields .form_section',
		appendTo: '#organizer_form_builder',
		helper: 'clone'
	});
	
	//Edit form settings
	$('input.form_settings').on('click', function() {
		that.openFormSettings();
	});
};

methods.loadTabsList = function(tabId) {
	foreach (this.tuix.items as var tTabId => var tab) {
		if (tabId == tTabId) {
			tab._selected = true;
		} else {
			delete(tab._selected);
		}
	}
	
	//Only show page tabs if there is at least 2
	var tabs = this.getOrderedTabs();
	if (tabs.length <= 1) {
		tabs = false;
	}
	
	//Load HTML
	var mergeFields = {tabs: tabs};
	var html = this.microTemplate('zenario_organizer_form_builder_tabs', mergeFields);
	$('#organizer_form_tabs').html(html);
	
	//Add events
	
	var that = this;
	//Click on a tab
	$('#organizer_form_tabs .tab').on('click', function() {
		var tTabId = $(this).data('id');
		if (that.tuix.items[tTabId]) {
			that.clickTab(tTabId);
		}
	});
	//Add a new tab
	$('#organizer_add_new_tab').on('click', function() {
		var errors = that.saveCurrentOpenDetails();
		if (!errors) {
			that.addNewTab();
			that.changeMadeToPanel();
		}
	});
	//Tab sorting
	$('#organizer_form_tabs').sortable({ 
		containment: 'parent',
		tolerance: 'pointer',
		items: 'div.sort',
		start: function(event, ui) {
			that.startIndex = ui.item.index();
		},
		stop: function(event, ui) {
			if (that.startIndex != ui.item.index()) {
				//Update ordinals
				$('#organizer_form_tabs .tab.sort').each(function(i) {
					var tTabId = $(this).data('id');
					that.tuix.items[tTabId].ord = (i + 1);
				});
				that.changeMadeToPanel();
				
				//If currently viewing a tabs details, reload because the content changes depending on position
				if (that.selectedTabId) {
					that.saveCurrentOpenDetails();
					that.loadTabDetailsPanel(that.selectedTabId, true);
				}
			}
		}
	});
	
	//Moving fields between tabs
	$('#organizer_form_tabs .tab').droppable({
		accept: 'div.form_field:not(.repeat_end)',
		greedy: true,
		hoverClass: 'ui-state-hover',
		tolerance: 'pointer',
		drop: function(event, ui) {
			var tTabId = $(this).data('id');
			var fieldId = $(ui.draggable).data('id');
			
			that.moveFieldToTab(that.currentTabId, tTabId, fieldId);
			
			if (fieldId == that.selectedFieldId) {
				that.loadNewFieldsPanel();
			}
			that.loadFieldsList(that.currentTabId);
		}
	});
};

methods.moveFieldToTab = function(fromTabId, toTabId, fieldId, addToStart) {
	if (fromTabId != toTabId 
		&& this.tuix.items[toTabId] 
		&& this.tuix.items[fromTabId] 
		&& this.tuix.items[fromTabId].fields[fieldId]
		&& this.tuix.items[fromTabId].fields[fieldId].type != 'repeat_end'
	) {
		var ord = 1;
		var toFields = this.getOrderedFields(toTabId);
		if (toFields.length) {
			if (addToStart) {
				ord = toFields[0].ord - 1;
			} else {
				ord = toFields[toFields.length - 1].ord + 1;
			}
		}
		
		//Move matching repeat_end for repeat_start
		if (this.tuix.items[fromTabId].fields[fieldId].type == 'repeat_start') {
			var repeatEndId = this.getMatchingRepeatEnd(fieldId);
			this.tuix.items[toTabId].fields[repeatEndId] = this.tuix.items[fromTabId].fields[repeatEndId];
			this.tuix.items[toTabId].fields[repeatEndId].ord = ord + 0.001;
			delete(this.tuix.items[fromTabId].fields[repeatEndId]);
		}
		
		this.tuix.items[toTabId].fields[fieldId] = this.tuix.items[fromTabId].fields[fieldId];
		this.tuix.items[toTabId].fields[fieldId].ord = ord;
		delete(this.tuix.items[fromTabId].fields[fieldId]);
		
		this.changeMadeToPanel();
	}
};

methods.addNewTab = function() {
	var ord = 1;
	var tabs = this.getOrderedTabs();
	if (tabs.length) {
		ord = tabs[tabs.length - 1].ord + 1;
	}
	this.addNewField('page_break', ord);
};

methods.clickTab = function(tabId, isNewTab) {
	if (this.selectedTabId != tabId) {
		var errors = this.saveCurrentOpenDetails();
		if (!errors) {
			//By clicking on the current tab you can access it's details
			if (this.currentTabId == tabId) {
				this.selectedFieldId = false;
				this.selectedTabId = tabId;
				this.selectedDetailsTab = false;
				this.loadTabDetailsPanel(tabId);
				return;
			}
			this.clickTab2(tabId, isNewTab);
		}
	}
};

methods.clickTab2 = function(tabId, isNewTab) {
	this.currentTabId = tabId;
	if (isNewTab) {
		this.selectedTabId = tabId;
		this.loadTabDetailsPanel(tabId);
	} else {
		var stopEffect = false;
		if (!this.selectedFieldId && !this.selectedTabId) {
			stopEffect = true;
		}
		this.loadNewFieldsPanel(stopEffect);
	}
	this.selectedFieldId = false;
	this.loadTabsList(tabId);
	this.loadFieldsList(tabId);
};


methods.getOrderedTabs = function() {
	var sortedTabs = [];
	foreach (this.tuix.items as var tabId => var tab) {
		sortedTabs.push(tab);
	}
	sortedTabs.sort(this.sortByOrd);
	return sortedTabs;
};


methods.fieldDetailsChanged = function(path, field) {
	path = path.split('/');
	var mode = path[0],
		tab = path[1],
		tuixField, value, valuesSource, actionRequests, that, item, title;
	
	if (tab && mode && this.tuix[mode] && this.tuix[mode].tabs[tab] && this.tuix[mode].tabs[tab].fields[field]) {
		this.changeMadeToPanel();
		tuixField = this.tuix[mode].tabs[tab].fields[field];
		if (tuixField.format_onchange) {
			if (mode == 'field_details') {
				//Save
				if (this.selectedFieldId) {
					item = this.tuix.items[this.currentTabId].fields[this.selectedFieldId];
				} else {
					item = this.tuix.items[this.selectedTabId];
				}
				
				this.saveItemTUIXTab(mode, this.selectedDetailsTab, item);
				if (tab == 'details' && field == 'field_validation') {
					if (item.field_validation == 'email') {
						item.field_validation_error_message = 'The email address you have entered is not valid, please enter a valid email address.';
					} else if (item.field_validation == 'URL') {
						item.field_validation_error_message = 'Please enter a valid URL.';
					} else if (item.field_validation == 'number') {
						item.field_validation_error_message = 'Please enter a valid number.';
					} else if (item.field_validation == 'integer') {
						item.field_validation_error_message = 'Please enter a valid integer.';
					} else if (item.field_validation == 'floating_point') {
						item.field_validation_error_message = 'Please enter a valid floating point number.';
					}
				}
				
				//Load
				this.loadFieldDetailsTab(this.selectedDetailsTab, item.id);
				
				if (tab == 'details') {
					if (field == 'visibility') {
						value = $('#field__visibility').val();
						if (value == 'hidden') {
							title = 'Hidden';
						} else if (value == 'visible_on_condition') {
							title = 'Visible on condition';
						}
						$('#organizer_form_field_' + this.selectedFieldId + ' .form_field_classes .not_visible').toggle(value != 'visible').prop('title', title);
					} else if (field == 'readonly_or_mandatory') {
						value = $('#field__readonly_or_mandatory').val();
						if (value == 'mandatory') {
							title = 'Mandatory';
						} else if (value == 'conditional_mandatory') {
							title = 'Mandatory on condition';
						}
						$('#organizer_form_field_' + this.selectedFieldId + ' .form_field_classes .mandatory').toggle(value == 'mandatory' || value == 'conditional_mandatory').prop('title', title);
					} else if (field == 'field_validation') {
						value = $('#field__field_validation').val();
						$('#organizer_form_field_' + this.selectedFieldId + ' .form_field_classes .validation').toggle(value != 'none');
					} else if (field == 'values_source') {
						valuesSource = $('#field__values_source').val();
						if (valuesSource) {
							actionRequests = {
								mode: 'get_centralised_lov',
								method: valuesSource,
								filter: $('#field__values_source_filter').val()
							};
							that = this;
							this.sendAJAXRequest(actionRequests).after(function(lov) {
								lov = JSON.parse(lov);
								item.lov = lov;
								that.loadFieldValuesListPreview(that.selectedFieldId);
							});
						} else {
							item.lov = {};
							this.loadFieldValuesListPreview(this.selectedFieldId);
						}
					}
				}
			}
		}
	}
};

methods.loadFieldValuesListPreview = function(fieldId) {
	var tabId = this.currentTabId;
	if (this.tuix.items[tabId].fields[fieldId]) {
		var field = this.tuix.items[tabId].fields[fieldId];
		var values = this.getOrderedFieldValues(tabId, fieldId, true);
		var html = false;
		if (field.type == 'select' || field.type == 'centralised_select') {
			html = this.microTemplate('zenario_organizer_admin_box_builder_select_values', values);
			$('#organizer_form_field_' + fieldId + ' select').html(html);
		} else if (field.type == 'radios' || field.type == 'centralised_radios') {
			html = this.microTemplate('zenario_organizer_admin_box_builder_radio_values_preview', values);
			$('#organizer_form_field_values_' + fieldId).html(html);
		} else if (field.type == 'checkboxes') {
			html = this.microTemplate('zenario_organizer_admin_box_builder_checkbox_values_preview', values);
			$('#organizer_form_field_values_' + fieldId).html(html);
		}
	}
};

methods.loadFieldsList = function(tabId) {
	var orderedFields = this.getOrderedFields(tabId, true);
	
	//Load HTML
	var mergeFields = {
		fields: orderedFields
	};
	var html = this.microTemplate('zenario_organizer_form_builder_section', mergeFields);
	$('#organizer_form_fields').html(html);
	
	//Add events
	
	var that = this;
	//Select a field
	$('#organizer_form_fields div.form_field').on('click', function() {
		var fieldId = $(this).data('id');
		if (that.tuix.items[that.currentTabId].fields[fieldId]) {
			that.clickField(fieldId);
		}
	});
	
	var hoverTimeoutId;
	$('#organizer_form_fields div.form_field').hover(
		function() {
			var that2 = this;
			if (!hoverTimeoutId) {
				hoverTimeoutId = setTimeout(function() {
					hoverTimeoutId = null;
					var fieldId = $(that2).data('id');
					$('#organizer_form_field_' + fieldId + ' .form_field_classes').show();
				}, 200)
			}
		}, function() {
			if (hoverTimeoutId) {
				clearTimeout(hoverTimeoutId);
				hoverTimeoutId = null;
			} else {
				var fieldId = $(this).data('id');
				if (fieldId != that.selectedFieldId) {
					$('#organizer_form_field_' + fieldId + ' .form_field_classes').hide();
				}
			}
		}
	);
	$('#organizer_form_fields').tooltip();
	
	//Make fields sortable
	$('#organizer_form_fields .form_section').sortable({
		items: 'div.form_field',
		tolerance: 'pointer',
		placeholder: 'preview',
		//Add new field to the form
		receive: function(event, ui) {
			$(this).find('div.field_type, div.dataset_field').each(function() {
				var fieldType = $(this).data('type');
				var datasetFieldId = $(this).data('id');
				var datasetTabId = $(this).data('tab_name');
				var ord = 0.1;
				var previousFieldId = $(this).prev().data('id');
				if (previousFieldId && that.tuix.items[tabId].fields[previousFieldId]) {
					ord = that.tuix.items[tabId].fields[previousFieldId].ord + 0.1;
				} else {
					var nextFieldId = $(this).next().data('id');
					if (nextFieldId && that.tuix.items[tabId].fields[nextFieldId]) {
						ord = that.tuix.items[tabId].fields[nextFieldId].ord - 0.1;
					}
				}
				
				that.addNewField(fieldType, ord, datasetFieldId, datasetTabId);
				that.updateFieldOrds();
			});
		},
		start: function(event, ui) {
			that.startIndex = ui.item.index();
		},
		//Detect reorder/new/deleted fields
		stop: function(event, ui) {
			if (that.startIndex != ui.item.index()) {
				//Update ordinals
				that.updateFieldOrds();
			}
		}
	});
	//Delete a field
	$('#organizer_form_fields .delete_icon').on('click', function(e) {
		e.stopPropagation();
		var fieldId = $(this).data('id');
		if (that.tuix.items[that.currentTabId].fields[fieldId]) {
			//Make sure we can delete this field
			var errors = that.saveCurrentOpenDetails(true);
			if (!errors) {
				var field = that.tuix.items[that.currentTabId].fields[fieldId];
				var transferFields = {};
				foreach (that.tuix.items as var tTabId => var tTab) {
					foreach (tTab.fields as var tFieldId => var tField) {
						if (tField.type == field.type && tFieldId != fieldId) {
							transferFields[tTabId + '__' + tFieldId] = tField.name;
						}
					}
				}
			
				var keys = {
					id: fieldId,
					field_name: field.name,
					field_type: field.type,
					field_english_type: that.getFieldReadableType(field.type)
				};
				//Pass the fields that responses can be transfered to a dummy field via values because this may be too large for the key
				//which is passed in the URL
				var values = {details: {dummy_field: JSON.stringify(transferFields)}};
				zenarioAB.open(
					'zenario_delete_form_field',
					keys,
					undefined, values,
					function(key, values) {
						//Migrate data to another field
						var migrateResponsesTo = undefined;
						if (values.details.delete_field_options == 'delete_field_but_migrate_data' 
							&& values.details.migration_field
						) {
							var ids = values.details.migration_field.split('__');
							if (ids.length == 2 
								&& that.tuix.items[ids[0]] 
								&& that.tuix.items[ids[0]].fields[ids[1]]
							) {
								that.tuix.items[ids[0]].fields[ids[1]]._migrate_responses_from = fieldId;
							}
						}
						that.deleteField(fieldId);
					}
				);
			}
		}
	});
};

methods.updateFieldOrds = function() {
	var that = this;
	$('#organizer_form_fields div.form_field').each(function(i) {
		var fieldId = $(this).data('id');
		that.tuix.items[that.currentTabId].fields[fieldId].ord = (i + 1);
	});
	this.changeMadeToPanel();
};

methods.deleteTab = function(tabId) {
	var tabs = this.getOrderedTabs(),
		fields, i, j, nextTabId;
	//Do not allow the final tab to be deleted
	if (tabs.length >= 2) {
		fields = this.getOrderedFields(tabId);
		for (i = 0; i < tabs.length; i++) {
			if (tabs[i].id == tabId) {
				if (tabs[i - 1]) {
					nextTabId = tabs[i - 1].id;
					for (j = 0; j < fields.length; j++) {
						this.moveFieldToTab(tabId, nextTabId, fields[j].id);
					}
				} else {
					nextTabId = tabs[i + 1].id;
					for (j = fields.length - 1; j >= 0; j--) {
						this.moveFieldToTab(tabId, nextTabId, fields[j].id, true);
					}
				}
				this.deletedFields.push(tabId);
				delete(this.tuix.items[tabId]);
				
				this.clickTab(nextTabId);
				this.changeMadeToPanel();
				break;
			}
		}
	}
};

methods.deleteField = function(fieldId) {
	tabId = this.currentTabId;
	if (this.tuix.items[tabId].fields[fieldId]) {
		this.changeMadeToPanel();
		this.deletedFields.push(fieldId);
		
		var field = this.tuix.items[tabId].fields[fieldId];
		//Delete a repeat start's matching repeat end
		if (field.type == 'repeat_start') {
			var repeatEndId = this.getMatchingRepeatEnd(fieldId);
			if (repeatEndId) {
				this.deletedFields.push(repeatEndId);
				delete(this.tuix.items[tabId].fields[repeatEndId]);
			}
		}
		
		//Select next field if one exists
		var fields = this.getOrderedFields(tabId);
		var deletedFieldIndex = false;
		var nextFieldId = false;
		
		for (var i = 0; i < fields.length; i++) {
			if (fields[i].id == field.id) {
				deletedFieldIndex = i;
			}
			if (deletedFieldIndex !== false) {
				if (deletedFieldIndex > 0 && fields[deletedFieldIndex - 1].type != 'repeat_end') {
					nextFieldId = fields[deletedFieldIndex - 1].id;
					break;
				} else if (fieldId != fields[i].id) {
					nextFieldId = fields[i].id;
					break;
				}
			}
		}
		delete(this.tuix.items[tabId].fields[fieldId]);
		
		this.loadFieldsList(tabId);
		if (nextFieldId) {
			this.clickField(nextFieldId);
		} else {
			this.loadNewFieldsPanel();
		}
	}
};

methods.getMatchingRepeatEnd = function(fieldId) {
	var repeatEndId = false,
		fieldIndex = false,
		tabId = this.currentTabId,
		fields = this.getOrderedFields(tabId),
		i;
	for (i = 0; i < fields.length; i++) {
		if (fields[i].type == 'repeat_end') {
			repeatEndId = fields[i].id;
		}
		if (fields[i].id == fieldId) {
			fieldIndex = i;
		} else if (fieldIndex !== false && fields[i].type == 'repeat_end') {
			repeatEndId = fields[i].id;
			break;
		}
	}
	return repeatEndId;
};

methods.addNewField = function(type, ord, datasetFieldId, datasetTabId) {
	var newFieldId = 't' + this.maxNewCustomField++;
	var newField = false;
	if (datasetFieldId && this.tuix.dataset_fields[datasetTabId] && this.tuix.dataset_fields[datasetTabId].fields[datasetFieldId]) {
		var datasetField = this.tuix.dataset_fields[datasetTabId].fields[datasetFieldId];
		newField = {
			type: datasetField.type,
			name: datasetField.label,
			field_label: datasetField.label,
			dataset_field_id: datasetFieldId,
			values_source: datasetField.values_source,
			values_source_filter: datasetField.values_source_filter,
			db_column: datasetField.db_column
		};
		if (datasetField.lov) {
			newField.lov = datasetField.lov;
		}
	} else if (type) {
		newField = {
			type: type,
			field_label: 'Untitled',
			name: 'Untitled ' + this.getFieldReadableType(type).toLowerCase()
		};
		
		if (type == 'checkboxes' || type == 'radios' || type == 'select') {
			newField.lov = {};
			for (var i = 1; i <= 3; i++) {
				this.addFieldValue(newField, 'Option ' + i);
			}
		} else if (type == 'repeat_start') {
			this.addNewField('repeat_end', ord + 0.01);
		}
	}
	
	//Load default values for fields
	foreach (this.tuix.field_details.tabs as var tabName => tab) {
		if (tab.fields) {
			foreach (tab.fields as var fieldName => var field) {
				if (field.value !== undefined && newField[fieldName] === undefined) {
					newField[fieldName] = field.value;
				}	
			}
		}
	}
	
	if (newField) {
		newField.id = newFieldId;
		newField.ord = ord;
		newField.is_new_field = true;
		newField.just_added = true;
		
		if (type == 'page_break') {
			var tabs = this.getOrderedTabs();
			newField.name = 'Page ' + (tabs.length + 1);
			newField.fields = {};
			this.tuix.items[newFieldId] = newField;
			this.clickTab(newFieldId, true);
		} else {
			this.tuix.items[this.currentTabId].fields[newFieldId] = newField;
			this.loadFieldsList(this.currentTabId);
			this.clickField(newFieldId);
		}
	}
};

methods.clickField = function(fieldId) {
	if (this.selectedFieldId != fieldId 
		&& this.tuix.items[this.currentTabId]
		&& this.tuix.items[this.currentTabId].fields[fieldId]
		&& this.tuix.items[this.currentTabId].fields[fieldId].type != 'repeat_end'
	) {
		var errors = this.saveCurrentOpenDetails();
		if (!errors) {
			this.highlightField(fieldId);
			this.selectedFieldId = fieldId;
			this.selectedTabId = false;
			this.selectedDetailsTab = false;
			this.loadFieldDetailsPanel(fieldId);
		}
	}
};

methods.saveCurrentOpenDetails = function(deletingField) {
	var errors = false,
		item;
	if (this.selectedFieldId 
		&& this.tuix.items[this.currentTabId] 
		&& this.tuix.items[this.currentTabId].fields[this.selectedFieldId]
	) {
		//Save custom field
		var $name = $('#field__name');
		if ($name.length) {
			this.tuix.items[this.currentTabId].fields[this.selectedFieldId].name = $name.val();
		}
		
		if (deletingField) {
			errors = this.getDeleteFieldErrors(this.selectedFieldId);
		} else {
			item = this.tuix.items[this.currentTabId].fields[this.selectedFieldId];
			errors = this.saveItemTUIXTab('field_details', this.selectedDetailsTab, item);
		}
		
		if (!_.isEmpty(errors)) {
			this.loadFieldDetailsTab(this.selectedDetailsTab, this.selectedFieldId, errors);
		}
	} else if (this.selectedTabId && this.tuix.items[this.selectedTabId]) {
		//Save custom field
		var $name = $('#field__name');
		if ($name.length) {
			this.tuix.items[this.selectedTabId].name = $name.val();
		}
		
		item = this.tuix.items[this.selectedTabId];
		errors = this.saveItemTUIXTab('field_details', this.selectedDetailsTab, item);
		if (!_.isEmpty(errors)) {
			this.loadTabDetailsTab(this.selectedDetailsTab, this.selectedTabId, errors);
		}
	}
	return !_.isEmpty(errors);
};

methods.displayGlobalErrors = function() {
	var errors = {};
	var fields = this.getOrderedFields();
	var inRepeatBlock = false;
	for (var i = 0; i < fields.length; i++) {
		var field = fields[i];
		if (inRepeatBlock && (['page_break', 'repeat_start', 'calculated', 'restatement', 'file_picker'].indexOf(field.type) != -1)) {
			errors[field.id] = 'Field type "' + this.getFieldReadableType(field.type).toLowerCase() + '" is not allowed in repeat blocks.';
		}
		if (field.type == 'repeat_start') {
			inRepeatBlock = true;
		} else if (field.type == 'repeat_end') {
			if (!inRepeatBlock) {
				errors[field.id] = 'You cannot have a Repeat End before a Repeat Start.';
				break;
			}
			inRepeatBlock = false;
		}
	}
	var $errorDiv = $('#organizer_fields_error');
	$errorDiv.html('');
	foreach (errors as i => var error) {
		$errorDiv.append('<p class="error">' + error + '</p>');
	}
	$errorDiv.show().delay(3000).fadeOut(function() {
		$(this).html('');
	});
	
	return !_.isEmpty(errors);
};

methods.getDeleteFieldErrors = function(deleteFieldId) {
	var errors = {};
	foreach (this.tuix.items[this.currentTabId].fields as var fieldId => var field) {
		if (deleteFieldId != fieldId) {
			var usedInCalcField = false;
			if (field.type == 'calculated' && field.calculation_code) {
				foreach (field.calculation_code as var index => var step) {
					if (step.type == 'field' && step.value == deleteFieldId) {
						usedInCalcField = true;
						break;
					}
				}
			}
			
			if ((field.visibility && field.visibility == 'visible_on_condition' && field.visible_condition_field_id == deleteFieldId)
				|| (field.readonly_or_mandatory && field.readonly_or_mandatory == 'conditional_mandatory' && field.mandatory_condition_field_id == deleteFieldId)
				|| usedInCalcField
			) {
				errors[fieldId] = 'Unable to delete field because the field "' + field.name + '" depends on it.';
			}
		}
	}
	return errors;
};


methods.validateFieldDetails = function(fields, item, mode, tab, errors) {
	var conditionField, tTabId, tTab, fieldId, field, i;
	if (mode == 'field_details') {
		if (!item.name) {
			errors.name = 'Please enter a name for this form field.';
		}
		
		if (tab == 'details') {
			if (item.type == 'checkbox' || item.type == 'group') {
				if (!item.field_label) {
					errors.field_label = 'Please enter a label for this checkbox.';
				}
			} else if (item.type == 'repeat_start') {
				if (!item.min_rows) {
					errors.min_rows = 'Please enter the minimum rows.';
				} else if (+item.min_rows != item.min_rows) {
					errors.min_rows = 'Please a valid number for mininum rows.';
				} else if (item.min_rows < 1 || item.min_rows > 10) {
					errors.min_rows = 'Mininum rows must be between 1 and 10.';
				}
				
				if (!item.max_rows) {
					errors.max_rows = 'Please enter the maximum rows.';
				} else if (+item.max_rows != item.max_rows) {
					errors.max_rows = 'Please a valid number for maximum rows.';
				} else if (item.max_rows < 1 || item.max_rows > 20) {
					errors.max_rows = 'Maximum rows must be between 1 and 20.';
				}
				
				if (!errors.min_rows && !errors.max_rows && (+item.min_rows > +item.max_rows)) {
					errors.min_rows = 'Minimum rows cannot be greater than maximum rows.';
				}
			} else if (item.type == 'restatement') {
				if (!item.restatement_field) {
					errors.restatement_field = 'Please select the field to mirror.';
				}
			} else if (item.type == 'centralised_radios' || item.type == 'centralised_select') {
				if (!item.values_source) {
					errors.values_source = 'Please select a values source.';
				}
			}
			
			if (item.visibility && item.visibility == 'visible_on_condition') {
				if (!item.visible_condition_field_id) {
					errors.visible_condition_field_id = 'Please select a visible on conditional form field.';
				} else if (!item.visible_condition_field_value) {
					conditionField = this.getField(item.visible_condition_field_id);
					if (conditionField && ['checkbox', 'group'].indexOf(conditionField.type) != -1) {
						errors.visible_condition_field_value = 'Please select a visible on condition form field value.';
					}
				}
			}
			
			if (item.readonly_or_mandatory) {
				if ((item.readonly_or_mandatory == 'mandatory' || item.readonly_or_mandatory == 'conditional_mandatory') && !item.required_error_message) {
					errors.required_error_message = 'Please enter an error message when this field is incomplete.';
				}
				
				if (item.readonly_or_mandatory == 'mandatory') {
					if (item.visibility == 'hidden') {
						errors.readonly_or_mandatory = 'A field cannot be mandatory while hidden.';
					} else if (item.visibility == 'visible_on_condition') {
						errors.readonly_or_mandatory = "This field is always mandatory but is conditionally visible. You must change this field to be either visible all the time or conditionally mandatory with the same condition as it's visibility.";
					}
				} else if (item.readonly_or_mandatory == 'conditional_mandatory') {
					if (!item.mandatory_condition_field_id) {
						errors.mandatory_condition_field_id = 'Please select a mandatory on condition form field.';
					} else if (!item.mandatory_condition_field_value) {
						conditionField = this.getField(item.mandatory_condition_field_id);
						if (conditionField && ['checkbox', 'group'].indexOf(conditionField.type) != -1) {
							errors.mandatory_condition_field_value = 'Please select a mandatory on condition form field value.';
						}
					}
					
					if (item.visibility == 'hidden') {
						errors.visibility = 'A field cannot be mandatory while hidden.';
					} else if (item.visibility == 'visible_on_condition' && item.readonly_or_mandatory == 'conditional_mandatory'
						&& item.visible_condition_field_id && item.mandatory_condition_field_id
						&& ((item.visible_condition_field_id != item.mandatory_condition_field_id)
							|| item.visible_condition_field_type == 'visible_if' && item.mandatory_condition_field_type == 'mandatory_if_not'
							|| item.visible_condition_field_type == 'visible_if_not' && item.mandatory_condition_field_type == 'mandatory_if'
							|| ((conditionField = this.getField(item.mandatory_condition_field_id))
								&& (conditionField.type != 'checkboxes'
									&& (item.visible_condition_field_value != item.mandatory_condition_field_value)
								)
								|| (conditionField.type == 'checkboxes'
									&& (item.visible_condition_checkboxes_operator != item.mandatory_condition_checkboxes_operator
										|| !_.isEqual(item.visible_condition_checkboxes_field_value, item.mandatory_condition_checkboxes_field_value)
									)
								)
							)
						)
					) {
						errors.visibility = 'A field cannot be mandatory while hidden. If this field is both mandatory and visible on a condition, both fields and values must be the same.';
					}
				}
			}
			
			if (item.field_validation && item.field_validation != 'none') {
				if (!item.field_validation_error_message) {
					errors.field_validation_error_message = 'Please enter a validation error message for this field.';
				}
			}
			
			foreach (this.tuix.items as tTabId => tTab) {
				foreach (tTab.fields as fieldId => field) {
					if (field.type == 'calculated' && field.calculation_code && item.field_validation && ['number', 'integer', 'floating_point'].indexOf(item.field_validation) == -1) {
						for (i = 0; i < field.calculation_code.length; i++) {
							if (field.calculation_code[i].type == 'field' && field.calculation_code[i].value == item.id) {
								errors.field_validation = 'The field "' + field.name + '" requires this field to be numeric. You must first remove this from that field.';
								break;
							}
						}
					}
				}
			}
			
		} else if (tab == 'advanced') {
			if (item.default_value_options) {
				if (item.default_value_options == 'value') {
					if (!item.default_value_lov && !item.default_value_text) {
						errors.default_value = 'Please enter a default value.';
					}
				} else if (item.default_value_options == 'method') {
					if (!item.default_value_class_name) {
						errors.default_value_class_name = 'Please enter a class name.';
					}
					if (!item.default_value_method_name) {
						errors.default_value_method_name = 'Please enter the name of a static method.';
					}
				}
			}
			
			if (item.custom_code_name) {
				foreach (this.tuix.items as tTabId => tTab) {
					foreach (tTab.fields as fieldId => field) {
						if (fieldId != item.id && (item.custom_code_name == field.custom_code_name)) {
							errors.custom_code_name = 'Another field already has that code name on this form.';
						}
					}
				}
			}
			
			if (item.autocomplete) {
				if (item.autocomplete_options == 'method') {
					if (!item.autocomplete_class_name) {
						errors.autocomplete_class_name = 'Please enter a class name.';
					}
					if (!item.autocomplete_method_name) {
						errors.autocomplete_method_name = 'Please enter the name of a static method.';
					}
				} else if (item.autocomplete_options == 'centralised_list') {
					if (!item.values_source) {
						errors.values_source = 'Please select a value source for the autocomplete lists.';
					}
				}
			}
			
			if (item.invalid_responses && item.invalid_responses.length) {
				if (!item.invalid_field_value_error_message) {
					errors.invalid_field_value_error_message = 'Please enter an error message when an invalid response is chosen.';
				}
			}
			
			if (item.type == 'textarea') {
				if (item.word_limit) {
					if (+item.word_limit != item.word_limit) {
						errors.word_limit = 'Please a valid number for word count.';
					} else if (item.word_limit < 1) {
						errors.word_limit = 'Word count must be greater than 0.';
					}
				}
			}
			
		} else if (tab == 'crm') {
			if (item.send_to_crm && !item.field_crm_name) {
				errors.field_crm_name = 'Please enter a CRM field name.';
			}
		}
	}
};

methods.highlightField = function(fieldId) {
	$('#organizer_form_fields .form_field .form_field_classes').hide();
	$('#organizer_form_fields .form_field .form_field_inline_buttons').hide();
	$('#organizer_form_fields .form_field').removeClass('selected');
	if (fieldId) {
		$('#organizer_form_field_' + fieldId).addClass('selected');
		$('#organizer_form_field_' + fieldId + ' .form_field_classes').show();
		$('#organizer_form_field_' + fieldId + ' .form_field_inline_buttons').show();
	}
	
	if (this.selectedFieldId && this.tuix.items[this.currentTabId].fields[this.selectedFieldId]) {
		delete(this.tuix.items[this.currentTabId].fields[this.selectedFieldId].just_added);
	}
};


methods.getFieldReadableType = function(type) {
	switch (type) {
		case 'checkbox':
			return 'Checkbox';
		case 'checkboxes':
			return 'Checkboxes';
		case 'date':
			return 'Date';
		case 'editor':
			return 'Editor';
		case 'radios':
			return 'Radios';
		case 'centralised_radios':
			return 'Centralised radios';
		case 'select':
			return 'Select';
		case 'centralised_select':
			return 'Centralised select';
		case 'text':
			return 'Text';
		case 'textarea':
			return 'Textarea';
		case 'url':
			return 'URL';
		case 'attachment':
			return 'Attachment';
		case 'page_break':
			return 'Page';
		case 'section_description':
			return 'Subheading';
		case 'calculated':
			return 'Calculated';
		case 'restatement':
			return 'Mirror';
		//Types unique to dataset fields
		case 'group':
			return 'Group';
		case 'file_picker':
			return 'File picker';
		case 'repeat_start':
			return 'Start of repeating section';
		case 'repeat_end':
			return 'End of repeating section';
		default:
			return 'Unknown';
	}
};

methods.getOrderedFields = function(tabId, orderValues) {
	var sortedFields = [],
		fieldId, field;
	if (this.tuix.items[tabId] && this.tuix.items[tabId].fields) {
		foreach (this.tuix.items[tabId].fields as fieldId => field) {
			var fieldClone = _.clone(field);
			if (orderValues) {
				fieldClone.lov = this.getOrderedFieldValues(tabId, fieldId, true);
			}
			sortedFields.push(fieldClone);
		}
	}
	sortedFields.sort(this.sortByOrd);
	return sortedFields;
};

methods.getOrderedFieldValues = function(tabId, fieldId, includeEmptyValue) {
	var sortedValues = [],
		valueId, value;
	if (this.tuix.items[tabId]
		&& this.tuix.items[tabId].fields
		&& this.tuix.items[tabId].fields[fieldId]
		&& this.tuix.items[tabId].fields[fieldId].lov
	) {
		foreach (this.tuix.items[tabId].fields[fieldId].lov as valueId => value) {
			sortedValues.push(value);
		}
		var type = this.tuix.items[tabId].fields[fieldId].type;
		if (includeEmptyValue && (type == 'select' || type == 'centralised_select')) {
			sortedValues.push({
				id: 'empty_value',
				label: '-- Select --',
				ord: -1
			});
		}
	}
	sortedValues.sort(this.sortByOrd);
	return sortedValues;
};

methods.sortErrors = function(errors) {
	var sortedErrors = [];
	foreach (errors as var i => var error) {
		sortedErrors.push(error);
	}
	return sortedErrors;
};

methods.sortLanguages = function(languages) {
	var sortedLanguages = [];
	foreach (languages as var i => var language) {
		sortedLanguages.push(language);
	}
	return sortedLanguages;
};

methods.getOrderedDatasetTabsAndFields = function() {
	var sortedTabs = [];
	var usedDatasetFields = {};
	foreach (this.tuix.items as var tabId => var tab) {
		foreach (tab.fields as var fieldId => var field) {
			if (field.dataset_field_id) {
				usedDatasetFields[field.dataset_field_id] = true;
			}
		}
	}
	
	foreach (this.tuix.dataset_fields as var datasetTabName => var datasetTab) {
		var datasetTabClone = _.clone(datasetTab);
		datasetTabClone.fields = [];
		
		if (datasetTab.fields) {
			foreach (datasetTab.fields as var datasetFieldId => var datasetField) {
				datasetField.readableType = this.getFieldReadableType(datasetField.type);
				if (!usedDatasetFields[datasetFieldId]) {
					datasetTabClone.fields.push(datasetField);
				}
			}
			datasetTabClone.fields.sort(this.sortByOrd);
		}
		
		sortedTabs.push(datasetTabClone);
	}
	sortedTabs.sort(this.sortByOrd);
	return sortedTabs;
};

methods.changeMadeToPanel = function() {
	var that = this;
	if (!this.changeDetected) {
		this.changeDetected = true;
		window.onbeforeunload = function() {
			return 'You are currently editing this form. If you leave now you will lose any unsaved changes.';
		}
		var warningMessage = 'Please either save your changes, or click Reset to discard them, before exiting the form editor.';
		zenarioO.disableInteraction(warningMessage);
		zenarioO.setButtons();
	}
};

methods.saveChanges = function() {
	var that = this;
	var actionRequests = {
		mode: 'save',
		data: JSON.stringify(this.tuix.items),
		deletedFields: JSON.stringify(this.deletedFields),
		currentTabId: this.currentTabId
	};
	if (this.selectedTabId) {
		actionRequests.selectedTabId = this.selectedTabId;
	}
	if (this.selectedFieldId) {
		actionRequests.selectedFieldId = this.selectedFieldId;
	}
	
	this.sendAJAXRequest(actionRequests).after(function(info) {
		if (info) {
			info = JSON.parse(info);
			if (info.errors) {
				var message = '';
				for (var i = 0; i < info.errors.length; i++) {
					message += info.errors[i] + '<br>';
				}
				if (message) {
					zenarioA.floatingBox(message);
				}
			}
			that.currentTabId = info.currentTabId;
			that.selectedTabId = info.selectedTabId
			that.selectedFieldId = info.selectedFieldId;
		}
		
		zenarioA.nowDoingSomething();
		window.onbeforeunload = false;
		zenarioO.enableInteraction();
		
		that.changeDetected = false;
		that.changesSaved = true;
		
		zenarioO.reload();
	});
};


}, zenarioO.panelTypes);
