/*
 * Copyright (c) 2021, Tribal Limited
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
	
	For more information, see js_minify.shell.php for steps (1) and (2), and admin.wrapper.js.php for step (3).
*/

zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioT, zenarioAB, zenarioAT, zenarioO,
	encodeURIComponent, defined, engToBoolean, get, htmlspecialchars, jsEscape, phrase,
	extensionOf, methodsOf, has,
	zenarioABToolkit
) {
	"use strict";



var FAB_NAME = 'AdminFloatingBox',
	FAB_LEFT = 50,
	FAB_TOP = 2,
	FAB_PADDING_HEIGHT = 193,
	FAB_TAB_BAR_HEIGHT = 53,
	FAB_WIDTH = 960,
	PLUGIN_SETTINGS_WIDTH = 800,
	PLUGIN_SETTINGS_MIN_WIDTH_FOR_PREVIEW = 1100,
	PLUGIN_SETTINGS_BORDER_WIDTH = 4;



zenarioAB.openingKey = {};

zenarioAB.start = function(path, key, tab, values) {
	var that = this;
	
	zenarioAB.openingKey = key;
	
	//Ensure the Organizer map is loaded so various pickers can work
	zenarioO.loadMap(function() {
		//When Organizer is loaded, continue running the start() function from the parent class
		methodsOf(zenarioABToolkit).start.call(zenarioAB, path, key, tab, values);
	});
};


zenarioAB.openBox = function(html) {
	//zenarioA.adjustBox = function(n, e, width, left, top, html, padding, maxHeight, rightCornerOfElement, bottomCornerOfElement) {
	//zenarioA.openBox = function(html, className, n, e, width, left, top, disablePageBelow, overlay, draggable, resizable, padding, maxHeight, rightCornerOfElement, bottomCornerOfElement) {
	zenarioA.openBox(html, zenarioAB.baseCSSClass, FAB_NAME, false, FAB_WIDTH, FAB_LEFT, FAB_TOP, true, true, '.zenario_fabHead', false);
	
	//...but hide the box itself, so only the overlay shows
	get('zenario_fbAdminFloatingBox').style.display = 'none';
};

zenarioAB.closeBox = function() {
	zenarioA.closeBox(FAB_NAME);
	zenarioAB.updateHash();
};

zenarioAB.updateHash = function() {
	if (zenarioO.path && zenarioO.tuix) {
		zenarioO.setHash();
	}
};



zenarioAB.setTitle = function(isReadOnly) {
	
	var title, lastUpdated, values, c, v, string2, identifier, id,
		$zenario_fabBox = $('#zenario_fabBox'),
		$zenario_fabId = $('#zenario_fabId'),
		$zenario_fabTitleWrap = $('#zenario_fabTitleWrap'),
		$zenario_fabLastUpdated = $('#zenario_fabLastUpdated');
	
	if (!(title = zenarioAB.getTitle())) {
		$zenario_fabTitleWrap.css('display', 'none');
	} else {
		$zenario_fabTitleWrap.css('display', 'block');
		$zenario_fabTitleWrap.addClass(' zenario_no_drag');
		
		get('zenario_fabTitle').innerHTML = htmlspecialchars(title);
	}
	
	if (lastUpdated = zenarioAB.tuix.last_updated) {
		$zenario_fabLastUpdated.show().html(htmlspecialchars(lastUpdated));
	} else {
		$zenario_fabLastUpdated.hide();
	}
	
	if (isReadOnly) {
		$('#zenario_fabBox_readonlyMarker').css('display', 'block');
	} else {
		$('#zenario_fabBox_readonlyMarker').css('display', 'none');
	}
	
	if (zenarioAB.tuix.key
	 && (identifier = zenarioAB.tuix.identifier)
	 && (identifier.value = identifier.value || (zenarioAB.tuix.key.id && zenario.decodeItemIdForOrganizer(zenarioAB.tuix.key.id)))) {
		
		$zenario_fabId.show().html(zenarioAB.microTemplate(this.mtPrefix + '_identifier', identifier));
		$zenario_fabBox.addClass('zfab_with_identifier');
	} else {
		$zenario_fabId.hide();
		$zenario_fabBox.removeClass('zfab_with_identifier');
	}
	
	if (zenarioO.path && zenarioO.tuix) {
		zenarioO.setHash();
	}
};




//Automatically set the box to the correct height for the users screen, or the maximum height requested, whichever is smaller
zenarioAB.lastSize = false;
zenarioAB.previewHidden = true;
zenarioAB.size = function(refresh) {
	
	if (zenarioAB.sizing) {
		clearTimeout(zenarioAB.sizing);
	}
	
	var width = Math.floor($(window).width()),
		height = Math.floor($(window).height()),
		previewHidden = false,
		newWidth,
		windowSizedChanged,
		boxHeight,
		maxBoxWidth,
		tabContainerHeight,
		maxTabContainerHeight,
		paddingHeight,
		hideTabBar;
	
	if (width && height && !zenarioAB.isSlidUp) {
		
		windowSizedChanged = zenarioAB.lastSize != width + 'x' + height;
		
		if (windowSizedChanged || refresh) {
			zenarioAB.lastSize = width + 'x' + height;
			
			hideTabBar = zenarioAB.tuix && engToBoolean(zenarioAB.tuix.hide_tab_bar);
			
			if (get('zenario_fbMain')) {
				if (hideTabBar) {
					get('zenario_fbMain').style.top = '0px';
					get('zenario_fabTabs').style.display = 'none';
				} else {
					get('zenario_fbMain').style.top = '24px';
					get('zenario_fabTabs').style.display = zenario.browserIsIE()? '' : 'inherit';
				}
			}
			
			paddingHeight = FAB_PADDING_HEIGHT;
			if (hideTabBar) {
				paddingHeight -= FAB_TAB_BAR_HEIGHT;
			}
			
			paddingHeight += $('#zenario_fabTitleWrap').height();
			
			boxHeight = Math.floor(height * 0.96);
			maxBoxWidth = Math.floor(width * 0.96);
			tabContainerHeight = boxHeight - paddingHeight;
			
			maxTabContainerHeight = 1 * (zenarioAB.tuix && zenarioAB.tuix.max_height);
			
			if (maxTabContainerHeight
			 && tabContainerHeight > maxTabContainerHeight) {
				tabContainerHeight = maxTabContainerHeight;
				boxHeight = maxTabContainerHeight + paddingHeight;
			}
	
			if (tabContainerHeight && tabContainerHeight > 0) {
				$('#zenario_fbAdminInner').height(tabContainerHeight);
			}
			zenarioAB.tabContainerHeight = tabContainerHeight;
	
			if (boxHeight && boxHeight > 0) {
				$('#zenario_fabBox').height(boxHeight);
				$('#zenario_fabPreview').height(boxHeight);
			}
			
			if (!zenarioAB.tuix
			 || !zenarioAB.tuix.css_class
			 || !zenarioAB.tuix.css_class.match(/zenario_fab_plugin\b/)) {
				
				$('#zenario_fabBox').width(FAB_WIDTH);
				newWidth = FAB_WIDTH;
				
				zenarioAB.previewMD5 =
				zenarioAB.previewWidth =
				zenarioAB.previewPost = false;
				
				previewHidden = true;
			
			} else {
				$('#zenario_fabBox').width(PLUGIN_SETTINGS_WIDTH);
				
				previewHidden = !zenarioAB.hasPreviewWindow || maxBoxWidth < PLUGIN_SETTINGS_MIN_WIDTH_FOR_PREVIEW;
			
				if (previewHidden) {
					newWidth = PLUGIN_SETTINGS_WIDTH;
				
					zenarioAB.previewMD5 =
					zenarioAB.previewWidth =
					zenarioAB.previewPost = false;
			
			
				} else {
					//If we found the width of the slot earlier, don't allow the preview window to be larger than that.
					//Also don't let the combined width of the preview window and the admin box be larger than the window!
					if (zenarioAB.previewSlotWidth) {
						newWidth = Math.min(maxBoxWidth, PLUGIN_SETTINGS_WIDTH + PLUGIN_SETTINGS_BORDER_WIDTH + zenarioAB.previewSlotWidth);
					} else {
						newWidth = maxBoxWidth;
					}
				
					//Note down the size that the preview window will be after all of thise
					zenarioAB.previewWidth = newWidth - PLUGIN_SETTINGS_WIDTH - PLUGIN_SETTINGS_BORDER_WIDTH;
				
					$('#zenario_fabPreview').width(zenarioAB.previewWidth);
				
					//Show or hide the description of the width
					if (zenarioAB.previewSlotWidthInfo) {
						$('#zenario_fabPreviewInfo').show().text(zenarioAB.previewSlotWidthInfo);
					} else {
						$('#zenario_fabPreviewInfo').hide();
					}
				}
			}
			
			if (!zenarioAB.hasPreviewWindow) {
				$('#zenario_fb' + FAB_NAME)
					.addClass('zenario_fab_with_no_preview')
					.removeClass('zenario_fab_with_preview')
					.removeClass('zenario_fab_with_preview_hidden')
					.removeClass('zenario_fab_with_preview_shown');
			
			} else if (previewHidden) {
				$('#zenario_fb' + FAB_NAME)
					.removeClass('zenario_fab_with_no_preview')
					.addClass('zenario_fab_with_preview')
					.addClass('zenario_fab_with_preview_hidden')
					.removeClass('zenario_fab_with_preview_shown');
			
			} else {
				$('#zenario_fb' + FAB_NAME)
					.removeClass('zenario_fab_with_no_preview')
					.addClass('zenario_fab_with_preview')
					.removeClass('zenario_fab_with_preview_hidden')
					.addClass('zenario_fab_with_preview_shown');
			}
			
			if (zenarioAB.previewHidden != previewHidden) {
				zenarioAB.previewHidden = previewHidden;
				
				//Refresh the preview frame if it was previously hidden and is now shown
				if (!previewHidden) {
					zenarioAB.updatePreview();
				}
			}
			
			
			zenarioA.adjustBox(FAB_NAME, false, newWidth, FAB_LEFT, FAB_TOP);
			zenarioAB.makeFieldAsTallAsPossible();
		}
	}
	
	zenarioAB.sizing = setTimeout(zenarioAB.size, 250);
};


//If there's a field flagged with "tall_as_possible", try to make it as tall as possible
zenarioAB.makeFieldAsTallAsPossible = function() {
	
	var id = zenarioAB.tallAsPossibleField,
		type = zenarioAB.tallAsPossibleFieldType,
		tabContainerHeight,
		tabHeight,
		domField,
		$field,
		height,
		editor,
		MARGIN = 20;
	
	if (defined(id)) {
		
		//Don't allow this to run for WYSIWYG editors, these auto-size based on the content you type in
		if (type != 'editor') {
		
			//Check the height of the current tab vs its container
			if (tabContainerHeight = zenarioAB.tabContainerHeight) {
				tabContainerHeight -= MARGIN;
			
				if (domField = zenarioAB.get(id)) {
					$field = $(domField);
					$field.height('');
				
					tabHeight = $('#zenario_abtab').outerHeight();
			
					if (tabContainerHeight > tabHeight) {
						$field.height(height = $field.height() + tabContainerHeight - tabHeight);
						
						if (type == 'code_editor') {
							if (editor = ace.edit(id)) {
								editor.resize();
							}
						}
					}
				}
			}
		}
	}
};



//Old slide up/slide down tech for the FABs, that let you peep at the page they were covering up.
//Currently not used anywhere.

//zenarioAB.slideToggle = function() {
//	if (zenarioAB.isSlidUp) {
//		zenarioAB.slideDown();
//	} else {
//		zenarioAB.slideUp();
//	}
//};
//	
//zenarioAB.slideUp = function() {
//	
//	if (zenarioAB.isSlidUp) {
//		return;
//	}
//	
//	var height = $('#zenario_fabBox_Header').height(),
//		//height = FAB_PADDING_HEIGHT + FAB_TAB_BAR_HEIGHT,
//		$zenario_fabBox = $('#zenario_fabBox');
//	
//	zenarioAB.heightBeforeSlideUp = $zenario_fabBox.height();
//	
//	$('#zenario_fabBox_Body').stop(true).slideUp();
//	
//	$zenario_fabBox.stop(true).animate({height: height});
//	
//	$('#zenario_fabSlideToggle')
//		.addClass('zenario_fabSlideToggleUp')
//		.removeClass('zenario_fabSlideToggleDown');
//	
//	zenarioAB.isSlidUp = true;
//};
//
//zenarioAB.slideDown = function() {
//	
//	if (!zenarioAB.isSlidUp) {
//		return;
//	}
//	
//	$('#zenario_fabBox_Body').stop(true).slideDown();
//	$('#zenario_fabBox').stop(true).animate({height: zenarioAB.heightBeforeSlideUp}, function() {
//		zenarioAB.size(true);
//	});
//	
//	$('#zenario_fabSlideToggle')
//		.addClass('zenario_fabSlideToggleDown')
//		.removeClass('zenario_fabSlideToggleUp');
//	
//	zenarioAB.isSlidUp = false;
//};



//If someone clicks on a tab, make sure that the form isn't hidden first!
zenarioAB.clickTab = function(tab) {
	methodsOf(zenarioABToolkit).clickTab.call(zenarioAB, tab);
};




//Specific bespoke functions for a few cases. These could have been on onkeyup/onchange events, but zenarioAB way is more efficient.
//Add the alias validation functions from the meta-data tab
zenarioAB.validateAlias = function() {
	zenario.actAfterDelayIfNotSuperseded('validateAlias', function() {
		zenarioAB.validateAliasGo(); 
	});
};

zenarioAB.validateAliasGo = function() {
	
	var domAlias = get('alias');
	
	if (!domAlias) {
		return;
	}
	
	var req = {
		_validate_alias: 1,
		alias: domAlias.value
	}
	
	if (zenarioAB.tuix.key.cID) {
		req.cID = zenarioAB.tuix.key.cID;
	}
	if (zenarioAB.tuix.key.cType) {
		req.cType = zenarioAB.tuix.key.cType;
	}
	if (zenarioAB.tuix.key.equivId) {
		req.equivId = zenarioAB.tuix.key.equivId;
	}
	if (get('language_id')) {
		req.langId = get('language_id').value;
	}
	
	if (get('update_translations')) {
		req.lang_code_in_url = 'show';
		if (get('update_translations').value == 'update_this' && get('lang_code_in_url')) {
			req.lang_code_in_url = get('lang_code_in_url').value;
		}
	}
	
	zenario.ajax(
		URLBasePath + 'zenario/admin/quick_ajax.php',
		req,
		true
	).after(function(data) {
		var html = '',
			alias_warning_display = get('alias_warning_display');
	
		if (data) {
			foreach (data as var error) {
				html += (html? '<br />' : '') + data[error];
			}
		}
		
		if (alias_warning_display) {
			alias_warning_display.innerHTML =  html;
		}
	});
};

//bespoke functions for the Content Tab
zenarioAB.generateAlias = function(text) {
	var trimmed_text =  text
						.toLowerCase()
						.replace(
							/[áÁàÀâÂåÅäÄãÃÆæçÇðÐéÉèÈêÊëËíÍìÌîÎïÏñÑóÓòÒôÔöÖõÕøØšŠúÚùÙûÛüÜýÝžŽ]/g,
							function(chr) {
								return {
										'á':'a', 'Á':'A', 'à':'a', 'À':'A', 'â':'a', 'Â':'A', 'å':'a', 'Å':'A', 'ä':'a', 'Ä':'A', 'ã':'a', 'Ã':'A',
										'Æ':'AE', 'æ':'ae',
										'ç':'c', 'Ç':'C', 'ð':'d', 'Ð':'F', 'é':'e', 'É':'E', 'è':'e', 'È':'E', 'ê':'e', 'Ê':'E', 'ë':'e', 'Ë':'E',
										'í':'i', 'Í':'I', 'ì':'i', 'Ì':'I', 'î':'i', 'Î':'I', 'ï':'i', 'Ï':'I', 'ñ':'n', 'Ñ':'N',
										'ó':'o', 'Ó':'O', 'ò':'o', 'Ò':'O', 'ô':'o', 'Ô':'O', 'ö':'o', 'Ö':'O', 'õ':'o', 'Õ':'O', 'ø':'o', 'Ø':'O',
										'š':'s', 'Š':'S', 'ú':'u', 'Ú':'U', 'ù':'u', 'Ù':'U', 'û':'u', 'Û':'U', 'ü':'u', 'Ü':'U', 'ý':'y', 'Ý':'Y', 'ž':'z', 'Ž':'Z'
									}[chr];
							})
						.replace(/&/g, 'and')
						.replace(/[^a-zA-Z0-9\s_-]/g, '')
						.replace(/\s+/g, '-')
						.replace(/^-+/, '')
						.replace(/-+$/, '')
						.replace(/-+/g, '-');
			
	if (trimmed_text.length > 50) {
		if (trimmed_text.indexOf('-') > -1) {
			trimmed_text = trimmed_text.substr(0, trimmed_text.lastIndexOf('-', 50));
		}
	}
	
	trimmed_text = trimmed_text.substr(0, 50);
	return trimmed_text;
};

zenarioAB.contentTitleChange = function() {
	
	var menuTextDOM = get('menu_text'),
		aliasDOM = get('alias');
	
	if (menuTextDOM && !zenarioAB.tuix.___menu_text_changed) {
		menuTextDOM.value = get('title').value.replace(/\s+/g, ' ');
		//menuTextDOM.onkeyup();
	}
	
	if (aliasDOM && !aliasDOM.disabled && !aliasDOM.readOnly && !zenarioAB.tuix.___alias_changed) {
		aliasDOM.value = zenarioAB.generateAlias(get('title').value);
		zenarioAB.validateAlias();
	}
};



zenarioAB.viewFrameworkSource = function() {
	var url =
		URLBasePath +
		'zenario/admin/organizer.php' +
		'#zenario__modules/show_frameworks//' + zenarioAB.tuix.key.moduleId + '//' + zenario.encodeItemIdForOrganizer(zenarioAB.readField('framework'));
	window.open(url);
	
	return false;
};

//Check to see if a svn image is selected in a picker field
zenarioAB.svgSelected = function(fieldName) {
	var field = zenarioAB.field(fieldName),
		value = zenarioAB.value(fieldName),
		pickedItems = field && value && zenarioAB.pickedItemsArray(field, value),
		i, label;
	
	if (pickedItems) {
		foreach (pickedItems as i => label) {
			if (!_.isString(label)) {
				label = label.label;
			}
			if (label && label.match(/\.svg( \[.*?\]|)$/i)) {
				return true;
			}
		}
	}
	return false;
};



//bespoke functions for Admin Perms

//Change a child-checkbox
zenarioAB.adminPermChange = function(parentName, childrenName, toggleName, n, c) {
	var parentChecked = true,
		parentClass,
		fields = zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields;
	
	//Count how many checkboxes are on the page, and how many of these are checked
	if (!defined(n)) {
		c = 0;
		n = $('input[name=' + childrenName + ']').each(function(i, e) {if (e.checked) ++c;}).length;
	}
	
	//Check or uncheck the parent, depending on if at least one child is checked.
	//Also set a CSS class on the row around the parent depending on how many were checked.
	if (c == 0) {
		parentChecked = false;
		parentClass = 'zenario_permgroup_empty';
	} else if (c < n) {
		parentClass = 'zenario_permgroup_half_full';
	} else {
		parentClass = 'zenario_permgroup_full';
	}
	
	get(parentName).checked =
	fields[parentName].current_value = parentChecked;
	
	$(get('row__' + parentName))
		.removeClass('zenario_permgroup_empty')
		.removeClass('zenario_permgroup_half_full')
		.removeClass('zenario_permgroup_full')
		.addClass(parentClass);
	
	fields[parentName].row_class =
		fields[parentName].row_class
			.replace(' zenario_permgroup_empty', '')
			.replace(' zenario_permgroup_half_full', '')
			.replace(' zenario_permgroup_full', '')
			+ ' ' + parentClass;
	
	//Set the "X / Y" display on the toggle
	get(toggleName).value =
	fields[toggleName].value =
	fields[toggleName].current_value = c + '/' + n;
};

//Change the parent checkbox
zenarioAB.adminParentPermChange = function(parentName, childrenName, toggleName) {
	var n = 0,
		c = 0,
		current_value = '',
		checked = get(parentName).checked,
		$children = $('input[name=' + childrenName + ']'),
		visibleChildren = !!$children.length;
	
	//Loop through each value for the child checkboxes.
	//Count them, and either turn them all on or all off, depending on whether the parent was checked
	foreach (zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[childrenName].values as var v) {
		++n;
		if (checked) {
			current_value += (current_value? ',' : '') + v;
			++c;
		}
	}
	
	//If the $children are currently drawn on the page, update them on the page
	if (visibleChildren) {
		$children.each(function(i, el) {
			el.checked = checked;
			//$children.prop('checked', checked? 'checked' : false);
		});
	}
	
	//Update them in the data
	zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[childrenName].current_value = current_value;
	
	//Call the function above to update the count and the CSS
	zenarioAB.adminPermChange(parentName, childrenName, toggleName, n, c);
	
	//If the parent was just turned on, but was also closed, open up to show what was just enabled
	if (checked && !visibleChildren) {
		zenarioAB.turnToggleOn(toggleName);
	}
};

//Date Previews in the Site Settings
zenarioAB.previewDateFormat = function(formatField, previewField) {
	zenario.actAfterDelayIfNotSuperseded(
		formatField,
		function() {
			zenarioAB.previewDateFormatGo(formatField, previewField);
		});
};

zenarioAB.previewDateFormatGo = function(formatField, previewField) {
	if ((formatField = get(formatField))
	 && (previewField = get(previewField))) {
		previewField.value = zenario.moduleNonAsyncAJAX('zenario_common_features', {previewDateFormat: formatField.value});
	}
};

zenarioAB.openSiteSettings = function(settingGroup, tab) {
	zenarioAB.open(
		'site_settings',
		{
			id: settingGroup
		},
		tab,
		undefined,
		function() {
			zenarioO.reloadPage();
		}
	);
};
zenarioAB.enableOrDisableSite = function() {
	zenarioAB.open(
		'zenario_enable_site',
		undefined,
		undefined,
		undefined,
		function() {
			zenarioO.reloadPage();
		}
	);
};
zenarioAB.updateSEP = function() {
	zenario.actAfterDelayIfNotSuperseded('updateSEP', function() {
		$('#microtemplate__search_engine_preview').html(zenarioAB.microTemplate('zenario_admin_box_search_engine_preview', {}));
	}, 400);
};

zenarioAB.cutText = function(text, length) {
	if (text.length > length) {
		text = text.substring(0, length).split(' ').slice(0, -1).join(' ') + ' ...';
	}
	
	return text;
};




zenarioAB.init('zenarioAB', 'zenario_admin_box', 'zenario_fbAdminFloatingBox');
zenario.shrtNms(zenarioAB);





},
	zenarioABToolkit
);