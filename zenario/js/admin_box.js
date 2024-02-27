/*
 * Copyright (c) 2024, Tribal Limited
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
		3. It may be bundled together with other files (this is to reduce the number of http requests on a page).
	
	For more information, see js_minify.shell.php for steps (1) and (2), and admin.bundle.js.php for step (3).
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
	FAB_WIDTH = 960,
	PLUGIN_SETTINGS_WIDTH = 800,
	PLUGIN_SETTINGS_MIN_WIDTH_FOR_PREVIEW = 1100,
	PLUGIN_SETTINGS_MIN_HEIGHT_FOR_PREVIEW = 700,
	PLUGIN_SETTINGS_MOBILE_PREVIEW_TARGET_WIDTH = 390,
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




//Automatically set the box to the correct height for the users screen, or the maximum height requested, whichever is smaller.
//We also need to handle the size of the preview box, and which side that's docked to
zenarioAB.lastSize = false;
zenarioAB.showPreview = true;
zenarioAB.size = function(refresh) {
	
	if (zenarioAB.sizing) {
		clearTimeout(zenarioAB.sizing);
	}
	
	var width = Math.floor($(window).width()),
		height = Math.floor($(window).height()),
		boxAndPreviewCombinedWidth,
		windowSizedChanged,
		global_area,
		tuix = zenarioAB.tuix || {};
	
	if (width && height && !zenarioAB.isSlidUp) {
		
		windowSizedChanged = zenarioAB.lastSize != width + 'x' + height;
		
		if (windowSizedChanged || refresh) {
			zenarioAB.lastSize = width + 'x' + height;
			
			var $zenario_fabBox = $('#zenario_fabBox'),
				$zenario_fabPreview = $('#zenario_fabPreview'),
				$zenario_fabForm = $('#zenario_fbAdminInner'),
				hideTabBar = engToBoolean(tuix.hide_tab_bar), 
				maxTabContainerHeight = 1 * tuix.max_height,
				showingMobilePreview = false;
			
			if (get('zenario_fbMain')) {
				if (hideTabBar) {
					get('zenario_fbMain').style.top = '0px';
					get('zenario_fabTabs').style.display = 'none';
				} else {
					get('zenario_fbMain').style.top = '24px';
					get('zenario_fabTabs').style.display = zenario.browserIsIE()? '' : 'inherit';
				}
			}
			
			
			
			//Reset the size of elements before calculating any sizes
			$zenario_fabBox.height('auto');
			$zenario_fabForm.height(100);
			$zenario_fabPreview.height(0);
			
			$zenario_fabBox.css({left: 'initial'});
			$zenario_fabPreview.css({left: 'initial'});
			
			//Get the height of the box before we set the tab container's height properly,
			//and compare it against the size of the window that we have to work in.
			//Use these numbers to work out how large the tab container can be.
			var outOfGrid = !zenarioAB.previewSlotWidthInfo,
				initialHeight = $zenario_fabBox.height(),
				heightAvailable = Math.floor(height * 0.96),
				widthAvailable = Math.floor(width * 0.96),
				tabContainerHeight,
				wideEnoughForDockAtSide,
				tallEnoughForDockAtTopOrBottom,
				hasPreview = false,
				couldShowPreviewInDock = false,
				showPreview = false,
				narrowPreviewWindow = false,
				previewWidth,
				previewHeight,
				dockPosition,
				isPluginSettings = tuix.css_class && tuix.css_class.match(/zenario_fab_plugin\b/);
			
			
			//Check if we can show a plugin preview.
			//This must be a plugin settings FAB, and the function to generate a preview must be callable.
			//Also if we want to show the preview at the same time as the settings, then we must have enough
			//room either above/below/to the side to show the preview.
			if (hasPreview = isPluginSettings && zenarioAB.hasPreviewWindow) {
				wideEnoughForDockAtSide = widthAvailable >= PLUGIN_SETTINGS_MIN_WIDTH_FOR_PREVIEW;
				tallEnoughForDockAtTopOrBottom = heightAvailable >= PLUGIN_SETTINGS_MIN_HEIGHT_FOR_PREVIEW;
				
				if (wideEnoughForDockAtSide || tallEnoughForDockAtTopOrBottom) {
					showPreview = couldShowPreviewInDock = true;
					
					//Check the sessions to see what the last position of the dock was.
					dockPosition = zenarioAB.getDockPosition();
					
					//A bit of validation to check the dock position is actually valid
					switch (dockPosition) {
						case 'closed':
							showPreview = false;
							break;
					
						case 'top':
							if (outOfGrid) {
								//Don't allow the "top" option for slots outside of the grid
								dockPosition = 'full';
							}
						case 'full':
							if (!tallEnoughForDockAtTopOrBottom) {
								dockPosition = 'right';
							}
							break;
					
						case 'mobile':
							if (!wideEnoughForDockAtSide) {
								dockPosition = 'top';
							}
							break;
					
						default:
							if (wideEnoughForDockAtSide) {
								dockPosition = 'right';
							} else {
								dockPosition = 'top';
							}
					}
					
					//Only show the buttons for each position if there is enough room to dock the preview in that position
					var $zenario_fabDockIconTop = $('#zenario_fabDockIconTop'),
						$zenario_fabDockIconFull = $('#zenario_fabDockIconFull'),
						$zenario_fabDockIconRight = $('#zenario_fabDockIconRight'),
						$zenario_fabDockIconMobile = $('#zenario_fabDockIconMobile');
					
					if (wideEnoughForDockAtSide) {
						$zenario_fabDockIconRight.show();
						$zenario_fabDockIconMobile.show();
					} else {
						$zenario_fabDockIconRight.hide();
						$zenario_fabDockIconMobile.hide();
					}
					
					if (tallEnoughForDockAtTopOrBottom) {
						$zenario_fabDockIconFull.show();
					} else {
						$zenario_fabDockIconFull.hide();
					}
					
					//Only show the "full width" option for an out-of grid slot.
					if (tallEnoughForDockAtTopOrBottom && !outOfGrid) {
						$zenario_fabDockIconTop.show();
					} else {
						$zenario_fabDockIconTop.hide();
					}
				}
			}
			
			
			//Plugin setting FABs have a slightly different width than regular FABs.
			if (!isPluginSettings) {
				boxAndPreviewCombinedWidth = FAB_WIDTH;
				$zenario_fabBox.width(boxAndPreviewCombinedWidth);
				
				zenarioAB.previewChecksum =
				previewWidth =
				zenarioAB.previewPost = false;
			
			} else if (!showPreview) {
				boxAndPreviewCombinedWidth = PLUGIN_SETTINGS_WIDTH;
				$zenario_fabBox.width(boxAndPreviewCombinedWidth);
				
				zenarioAB.previewChecksum =
				previewWidth =
				zenarioAB.previewPost = false;
			
			//Logic for setting the width and height of the FAB and the preview box, when display a
			//preview box docked to the FAB.
			} else {
				
				//Logic for setting the widths
				boxAndPreviewCombinedWidth = PLUGIN_SETTINGS_WIDTH;
				$zenario_fabBox.width(boxAndPreviewCombinedWidth);
				
				//Show the dock on the top and make the preview as wide as possible
				if (dockPosition == 'full') {
					previewWidth =
					boxAndPreviewCombinedWidth = widthAvailable;
					
				//Show the dock on the top, but try to set the same width as the grid-slot it's showing.
				} else if (dockPosition == 'top') {
					previewWidth = zenarioAB.previewSlotWidth || PLUGIN_SETTINGS_WIDTH;
					
					narrowPreviewWindow = previewWidth < PLUGIN_SETTINGS_MIN_WIDTH_FOR_PREVIEW - PLUGIN_SETTINGS_WIDTH;
					
					boxAndPreviewCombinedWidth = Math.max(previewWidth, PLUGIN_SETTINGS_WIDTH);
				
				//Show the dock on the right. Try to set the same width as the grid-slot it's showing but make it a bit thinner
				//if there is not enough room.
				} else {
					//If we found the width of the slot earlier, don't allow the preview window to be larger than that.
					//Also don't let the combined width of the preview window and the admin box be larger than the window!
					if (dockPosition == 'mobile') {
						boxAndPreviewCombinedWidth = Math.min(widthAvailable, PLUGIN_SETTINGS_WIDTH + PLUGIN_SETTINGS_BORDER_WIDTH + PLUGIN_SETTINGS_MOBILE_PREVIEW_TARGET_WIDTH);
					} else if (zenarioAB.previewSlotWidth) {
						boxAndPreviewCombinedWidth = Math.min(widthAvailable, PLUGIN_SETTINGS_WIDTH + PLUGIN_SETTINGS_BORDER_WIDTH + zenarioAB.previewSlotWidth);
						
						narrowPreviewWindow = boxAndPreviewCombinedWidth < widthAvailable;
						
					} else {
						boxAndPreviewCombinedWidth = widthAvailable;
					}
			
					//Note down the size that the preview window will be after all of thise
					previewWidth = boxAndPreviewCombinedWidth - PLUGIN_SETTINGS_WIDTH - PLUGIN_SETTINGS_BORDER_WIDTH;
				}
				$zenario_fabPreview.width(previewWidth);
				
				//Tony doesn't want the width of the preview to match the width of the FAB when the preview is
				//docked at the top.
				//We'll still want to try and nicely center them though. This is a bit tricky to do and needs some patching.
				//Work out what the difference in width is, then set that to forcibly move one of the boxes to the correct
				//position.
				var widthDiff = Math.floor((PLUGIN_SETTINGS_WIDTH - previewWidth) / 2);
				
				if (widthDiff > 0) {
					$zenario_fabPreview.css({left: widthDiff + 'px'});
				} else if (widthDiff < 0) {
					$zenario_fabBox.css({left: -widthDiff + 'px'});
				}
				
				
				
				//Logic for setting the heights
				if (dockPosition == 'right' || dockPosition == 'mobile') {
					//If the preview is docked to the right of the FAB, the FAB should be it's usual height,
					//and the preview should also be that height.
					$zenario_fabPreview.height(heightAvailable);
			
				} else {
					//If the preview is docked to the top of the FAB, they will need to divide up the height between them.
					var dockHeight = Math.floor(heightAvailable * 0.375);
					heightAvailable = heightAvailable - dockHeight;
				
					$zenario_fabPreview.height(dockHeight);
				}
			}
			
			if (showPreview) {
				var $zenario_fabPreviewInfo = $('#zenario_fabPreviewInfo');
				
				//Set the width info to the number of columns, or else mention that this
				//is a slot outside of the grid.
				if (outOfGrid) {
					$zenario_fabPreviewInfo.text(phrase.outOfGrid);
				} else {
					$zenario_fabPreviewInfo.text(zenarioAB.previewSlotWidthInfo);
				}
			}
			
			
			tabContainerHeight = heightAvailable - initialHeight + 100;
			
			if (maxTabContainerHeight
			 && tabContainerHeight > maxTabContainerHeight) {
				tabContainerHeight = maxTabContainerHeight;
				heightAvailable = tabContainerHeight + initialHeight - 100;
			}
	
			if (tabContainerHeight && tabContainerHeight > 0) {
				$zenario_fabForm.height(tabContainerHeight);
			}
			zenarioAB.tabContainerHeight = tabContainerHeight;
			
			$zenario_fabBox.height(heightAvailable);
			
			
			
			var $fab = $('#zenario_fb' + FAB_NAME);
			
			$fab.removeClass('zenario_fab_with_no_preview')
				.removeClass('zenario_fab_with_preview')
				.removeClass('zenario_fab_with_preview_cant_dock')
				.removeClass('zenario_fab_with_preview_hidden')
				.removeClass('zenario_fab_with_preview_shown')
				.removeClass('zenario_fab_with_preview_docked_top')
				.removeClass('zenario_fab_with_preview_docked_full')
				.removeClass('zenario_fab_with_preview_docked_right')
				.removeClass('zenario_fab_with_mobile_preview')
				.removeClass('zenario_fab_with_narrow_preview_window')
				.removeClass('zenario_fab_cant_show_in_place_preview');
			
			//N.b. the in-place preview doesn't work with FEAs...
			if (zenarioAB.tuix.key.isFEA) {
				$fab.addClass('zenario_fab_cant_show_in_place_preview');
			}
			
			if (!hasPreview) {
				$fab.addClass('zenario_fab_with_no_preview');
			
			} else if (!couldShowPreviewInDock) {
				$fab.addClass('zenario_fab_with_preview')
					.addClass('zenario_fab_with_preview_cant_dock');
			
			} else if (!showPreview) {
				$fab.addClass('zenario_fab_with_preview')
					.addClass('zenario_fab_with_preview_hidden');
			
			} else {
				$fab.addClass('zenario_fab_with_preview')
					.addClass('zenario_fab_with_preview_shown');
				
				if (narrowPreviewWindow) {
					$fab.addClass('zenario_fab_with_narrow_preview_window');
				}
				
				switch (dockPosition) {
					case 'top':
						$fab.addClass('zenario_fab_with_preview_docked_top');
						break;
					
					case 'full':
						$fab.addClass('zenario_fab_with_preview_docked_full');
						break;
					
					case 'mobile':
						$fab.addClass('zenario_fab_with_mobile_preview');
						showingMobilePreview = true;
						break;
					
					default:
						$fab.addClass('zenario_fab_with_preview_docked_right');
				}
			}
			
			if ((global_area = tuix.tabs.global_area)
			 && (!_.isEmpty(global_area.fields))) {
				$fab
					.removeClass('zenario_fab_with_no_global_area')
					.addClass('zenario_fab_with_global_area');
			} else {
				$fab
					.removeClass('zenario_fab_with_global_area')
					.addClass('zenario_fab_with_no_global_area');
			}
					
			
			if (zenarioAB.showPreview != showPreview) {
				zenarioAB.showPreview = showPreview;
				
				//Refresh the preview frame if it was previously hidden and is now shown
				if (showPreview) {
					zenarioAB.updatePreview();
				}
			}
			
			
			zenarioAB.showingMobilePreview = showingMobilePreview;
			
			zenarioA.adjustBox(FAB_NAME, false, boxAndPreviewCombinedWidth, FAB_LEFT, FAB_TOP);
			zenarioAB.makeFieldAsTallAsPossible();
		}
	}
	
	zenarioAB.sizing = setTimeout(zenarioAB.size, 250);
};

zenarioAB.getDockPosition = function() {
	return zenario.sGetItem(true, 'zfab_dock_position') || 'right';
};

zenarioAB.setDockPosition = function(side) {
	zenario.sSetItem(true, 'zfab_dock_position', side);
	zenarioAB.size(true);
};


//If there's a field flagged with "tall_as_possible", try to make it as tall as possible
zenarioAB.makeFieldAsTallAsPossible = function() {
	
	var id = zenarioAB.tallAsPossibleField,
		type = zenarioAB.tallAsPossibleFieldType,
		tabContainerHeight,
		tabHeight,
		height,
		isCodeEditor = type == 'code_editor',
		isWYSIWYG = type == 'editor',
		editor,
		$resizeMe,
		MARGIN = 20;
	
	if (defined(id)) {
		
		if (isWYSIWYG
		 && ($resizeMe = tinyMCE.get(id))
		 && ($resizeMe = $resizeMe.getContainer())
		) {
		} else {
			$resizeMe = zenarioAB.get(id);
		}
		$resizeMe = $($resizeMe);
	
		//Check the height of the current tab vs its container
		if (tabContainerHeight = zenarioAB.tabContainerHeight) {
			tabContainerHeight -= MARGIN;
		
			if (domField = zenarioAB.get(id)) {
				
				if (isWYSIWYG) {
					$resizeMe.height(200);
				} else {
					$resizeMe.height('');
				}
			
				tabHeight = $('#zenario_abtab').outerHeight();
		
				if (tabContainerHeight > tabHeight) {
					height = $resizeMe.height() + tabContainerHeight - tabHeight;
					
					$resizeMe.height(Math.floor(height));
					
					if (type == 'code_editor') {
						if (editor = ace.edit(id)) {
							editor.resize();
						}
					}
				}
			}
		}
	}
};



//If someone clicks on a tab, make sure that the form isn't hidden first!
zenarioAB.clickTab = function(tab) {
	methodsOf(zenarioABToolkit).clickTab.call(zenarioAB, tab);
};




//Specific bespoke functions for a few cases. These could have been on onkeyup/onchange events, but zenarioAB way is more efficient.
//Remove http and https from alias...
zenarioAB.removeHttpAndHttpsFromAlias = function() {
	var domAlias = get('alias');
	var alias = domAlias.value;

	alias = alias.replace(/(http|https)\:\/\//,'');

	domAlias.value = alias;
};

//... and .html or .htm if the mod_rewrite_suffix is set in the site settings.
zenarioAB.removeHtmAndHtmlFromAlias = function(suffix) {
	var domAlias = get('alias');
	var alias = domAlias.value;

	alias = alias.replace(suffix, '');

	domAlias.value = alias;
};

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
	
	var menuTextDOM = zenarioAB.get('menu_text'),
		aliasDOM = zenarioAB.get('alias');
	
	if (menuTextDOM && !zenarioAB.tuix.___menu_text_changed) {
		menuTextDOM.value = get('title').value.replace(/\s+/g, ' ');
		$(menuTextDOM).trigger('input');
	}
	
	if (aliasDOM && !aliasDOM.disabled && !aliasDOM.readOnly && !zenarioAB.tuix.___alias_changed) {
		aliasDOM.value = zenarioAB.generateAlias(get('title').value);
		zenarioAB.validateAlias();

		$('#alias').trigger('input');
	}
};



zenarioAB.viewFrameworkSource = function() {
	var url =
		URLBasePath +
		'organizer.php' +
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