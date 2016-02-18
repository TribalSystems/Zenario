/*
 * Copyright (c) 2016, Tribal Limited
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
	zenario, zenarioA, zenarioAB, zenarioAT, zenarioO,
	get, engToBoolean, htmlspecialchars, ifNull, jsEscape, phrase,
	extensionOf, methodsOf,
	zenarioAF
) {
	"use strict";

zenarioAB.init('zenarioAB');


zfabName = 'AdminFloatingBox';
zfabWidthNoPreview = 800;
zfabMinWidthWithPreview = 1100;
zfabPreviewBorderWidth = 4;
zfabLeft = 50;
zfabTop = 2;
zfabPaddingHeight = 188;
zfabTabBarHeight = 53;







//Open an admin floating box
zenarioAB.open = function(path, key, tab, values, callBack, createAnotherObject, reopening) {
	
	//Don't allow a box to be opened if Organizer is opened and covering the screen
	if (zenarioA.checkIfBoxIsOpen('AdminOrganizer')) {
		return false;
	}
	
	//Record the current scroll location of the page behind the box
	if (zenarioAB.documentScrollTop === false) {
		zenarioAB.documentScrollTop = $(zenario.browserIsSafari()? 'body' : 'html').scrollTop();
	}
	
	//Allow admin boxes to be opened in a simmilar format to Organizer panels; e.g. tag/path//id
	if (!key) {
		key = {};
	}
	path = ('' + path).split('//', 2);
	if (path[1] && !key.id) {
		key.id = path[1];
	}
	path = path[0];
	
	
	//Open the box...
	zenarioAB.isOpen = true;
	zenarioAB.callBack = callBack;
	zenarioAB.createAnotherObject = createAnotherObject;
	zenarioAB.getRequestKey = key;
	zenarioAB.changed = {};
	zenarioAB.isSlidUp = false;
	zenarioAB.previewHidden = true;
	zenarioAB.hasPreviewWindow = false;
	zenarioAB.lastPreviewValues = false;
	zenarioAB.previewSlotWidth = false;
	zenarioAB.previewSlotWidthInfo = '';
	zenarioAB.heightBeforeSlideUp = false;
	
	zenarioAB.baseCSSClass = 'zenario_fbAdmin zenario_admin_box zenario_fab_' + path;
	
	if (!reopening) {
		var html = zenarioA.microTemplate(zenarioAB.mtPrefix, {});
		
		//zenarioA.adjustBox = function(n, e, width, left, top, html, padding, maxHeight, rightCornerOfElement, bottomCornerOfElement) {
		//zenarioA.openBox = function(html, className, n, e, width, left, top, disablePageBelow, overlay, draggable, resizable, padding, maxHeight, rightCornerOfElement, bottomCornerOfElement) {
		zenarioA.openBox(html, zenarioAB.baseCSSClass, zfabName, false, zfabWidthNoPreview, zfabLeft, zfabTop, true, true, '.zenario_fabHead', false);
		
		//...but hide the box itself, so only the overlay shows
		get('zenario_fbAdminFloatingBox').style.display = 'none';
	}
	
	//If any Admin Boxes are open, set a warning message for if an admin tries to leave the page 
	window.onbeforeunload = zenarioA.onbeforeunload;
	
	zenarioAB.start(path, key, tab, values);
};


zenarioAB.initFields = function() {
	zenarioAB.hasPreviewWindow = !!zenarioAB.pluginPreviewURL();
	methodsOf(zenarioAF).initFields.call(zenarioAB);
};


zenarioAB.refreshParentAndClose = function(disallowNavigation, saveAndContinue, createAnother) {
	zenarioA.nowDoingSomething(false);
	
	var requests = {};
	
	if (!saveAndContinue) {
		zenarioAB.isOpen = false;
	}
	
	//Attempt to work out what to do next.
	if (zenarioAB.callBack && !saveAndContinue) {
		var values;
		if (values = zenarioAB.getValueArrayofArrays()) {
			zenarioAB.callBack(zenarioAB.tuix.key, values);
		}
		
	} else if (zenarioO.init && (zenarioA.isFullOrganizerWindow || zenarioA.checkIfBoxIsOpen('og'))) {
		//Reload Organizer if zenarioAB window is an Organizer window
		var id = false;
		
		if (zenarioAB.tuix.key.id !== undefined) {
			id = zenarioAB.tuix.key.id;
		} else {
			foreach (zenarioAB.tuix.key as var i) {
				id = zenarioAB.tuix.key[i];
				break;
			}
		}
		
		zenarioO.refreshToShowItem(id);
		
	} else if (zenario.cID && zenarioAB.tuix.key.slotName) {
		//Refresh the slot if zenarioAB was a plugin settings FAB
		zenario.refreshPluginSlot(zenarioAB.tuix.key.slotName, '', zenarioA.importantGetRequests);
		
		if (zenarioAT.init) {
			zenarioAT.init();
		}
		
	} else if (zenario.cID && (zenarioAB.path == 'zenario_menu' || zenarioAB.path == 'zenario_menu_text')) {
		//If this is the front-end, and this was a menu FAB, just reload the menu plugins
		zenarioA.reloadMenuPlugins();
		
		if (zenarioAT.init) {
			zenarioAT.init();
		}
	
	} else if (disallowNavigation || saveAndContinue) {
		//Don't allow any of the actions below, as they involve navigation
	
	//Otherwise build up a URL from the primary key, if it looks valid
	} else
	if (zenarioAB.tuix.key.cID) {
		
		//If zenarioAB is the current content item, add any important get requests from plugins
		if (zenarioAB.tuix.key.cID == zenario.cID
		 && zenarioAB.tuix.key.cType == zenario.cType) {
			requests = zenarioA.importantGetRequests;
		}
		
		zenario.goToURL(zenario.linkToItem(zenarioAB.tuix.key.cID, zenarioAB.tuix.key.cType, requests));
	
	//For any other Admin Toolbar changes, reload the page
	} else if (zenarioAT.init) {
		
		//Try to keep to the same version if possible.
		requests = _.clone(zenarioA.importantGetRequests);
		requests.cVersion = zenario.cVersion;
		
		zenario.goToURL(zenario.linkToItem(zenario.cID, zenario.cType, requests));
	}
	
	var popout_message = zenarioAB.tuix.popout_message;
	createAnother = createAnother && zenarioAB.createAnotherObject;
	
	if (!saveAndContinue && !createAnother) {
		zenarioAB.close();
	}
	
	if (popout_message) {
		zenarioA.showMessage(popout_message, true, false);
	}
	
	if (saveAndContinue) {
		zenarioAB.changed = {};
		
		if (zenarioAB.tuix.tabs) {
			foreach (zenarioAB.tuix.tabs as var i => var zenarioABTab) {
				if (zenarioABTab) {
					if (zenarioAB.editModeOn(i)) {
						zenarioAB.tuix.tabs[i]._saved_and_continued = true;
					}
				}
			}
		}
		
		zenarioAB.sortTabs();
		zenarioAB.draw();
	
	} else if (createAnother) {
		$('#zenario_abtab').clearQueue();
		delete zenarioAB.tuix;
		zenarioAB.open(
			zenarioAB.createAnotherObject.path,
			zenarioAB.getRequestKey,
			zenarioAB.createAnotherObject.tab,
			zenarioAB.createAnotherObject.values,
			undefined,
			zenarioAB.createAnotherObject,
			true);
	}
};


zenarioAB.close = function(keepMessageWindowOpen) {
	//Close TinyMCE if it is open
	zenarioAB.callFunctionOnEditors('remove');
	zenarioA.nowDoingSomething(false);
	
	if (zenarioAB.sizing) {
		clearTimeout(zenarioAB.sizing);
	}
	zenarioAB.stopPoking();
	zenario.clearAllDelays();
	
	if (!keepMessageWindowOpen) {
		zenarioA.closeFloatingBox();
	}
	
	zenarioA.closeBox(zfabName);
	zenarioAB.isOpen = false;
	
	//Return the page to it's original scroll position, before the box was opened
	if (zenarioAB.documentScrollTop !== false) {
		$(zenario.browserIsSafari()? 'body' : 'html').scrollTop(zenarioAB.documentScrollTop);
		zenarioAB.documentScrollTop = false;
	}
	
	delete zenarioAB.tuix;
	delete zenarioAB.lastPreviewValues;
	delete zenarioAB.previewSlotWidth;
	delete zenarioAB.previewSlotWidthInfo;
	
	return false;
};

zenarioAB.closeButton = function(onlyCloseIfNoChanges) {
	//Check if there is an editor open
	var message = zenarioA.onbeforeunload();
	
	//If there was, give the Admin a chance to stop leaving the page
	if (message === undefined || (!onlyCloseIfNoChanges && confirm(message))) {
		if (zenarioAB.isOpen) {
			zenarioAB.close();
		}
	}
	
	return false;
};











zenarioAB.setData = function(data) {
	zenarioAB.syncAdminBoxFromServerToClient(data, zenarioAB.tuix);
};

//Sync updates from the server to the array stored on the client
zenarioAB.syncAdminBoxFromServerToClient = function($serverTags, $clientTags) {
	for (var $key0 in $serverTags) {
		if ($serverTags[$key0]['[[__unset__]]']) {
			delete $clientTags[$key0];
		
		} else
		if ($clientTags[$key0] === undefined || $clientTags[$key0] === null) {
			$clientTags[$key0] = $serverTags[$key0];
		
		} else
		if ($serverTags[$key0]['[[__replace__]]']) {
			delete $serverTags[$key0]['[[__replace__]]'];
			$clientTags[$key0] = $serverTags[$key0];
		
		} else
		if ('object' != typeof $clientTags[$key0]) {
			$clientTags[$key0] = $serverTags[$key0];
		
		} else
		if (('object' == typeof $clientTags[$key0]) && ('object' != typeof $serverTags[$key0])) {
			$clientTags[$key0] = $serverTags[$key0];
		
		} else {
			zenarioAB.syncAdminBoxFromServerToClient($serverTags[$key0], $clientTags[$key0]);
		}
	}
};


zenarioAB.sendStateToServer = function() {
	var $serverTags = {};
	zenarioAB.syncAdminBoxFromClientToServerR($serverTags, zenarioAB.tuix);
	
	return JSON.stringify($serverTags);
};

//Sync updates from the client to the array stored on the server
zenarioAB.syncAdminBoxFromClientToServerR = function($serverTags, $clientTags, $key1, $key2, $key3, $key4, $key5, $key6) {
	
	if ('object' != typeof $clientTags) {
		return;
	}
	
	var $type, $key0;
	
	for ($key0 in $clientTags) {
		//Only allow certain tags in certain places to be merged in
		if ((($type = 'array') && $key1 === undefined && $key0 == '_sync')
		 || (($type = 'value') && $key2 === undefined && $key1 == '_sync' && $key0 == 'session')
		 || (($type = 'value') && $key2 === undefined && $key1 == '_sync' && $key0 == 'cache_dir')
		 || (($type = 'value') && $key2 === undefined && $key1 == '_sync' && $key0 == 'password')
		 || (($type = 'value') && $key2 === undefined && $key1 == '_sync' && $key0 == 'iv')
		 || (($type = 'array') && $key1 === undefined && $key0 == 'key')
		 || (($type = 'value') && $key2 === undefined && $key1 == 'key')
		 || (($type = 'value') && $key1 === undefined && $key0 == 'shake')
		 || (($type = 'value') && $key1 === undefined && $key0 == 'download')
		 || (($type = 'array') && $key1 === undefined && $key0 == 'tabs')
		 || (($type = 'array') && $key2 === undefined && $key1 == 'tabs')
		 || (($type = 'array') && $key3 === undefined && $key2 == 'tabs' && $key0 == 'edit_mode')
		 || (($type = 'value') && $key4 === undefined && $key3 == 'tabs' && $key1 == 'edit_mode' && $key0 == 'on')
		 || (($type = 'array') && $key3 === undefined && $key2 == 'tabs' && $key0 == 'fields')
		 || (($type = 'array') && $key4 === undefined && $key3 == 'tabs' && $key1 == 'fields')
		 || (($type = 'value') && $key5 === undefined && $key4 == 'tabs' && $key2 == 'fields' && $key0 == 'current_value')
		 || (($type = 'value') && $key5 === undefined && $key4 == 'tabs' && $key2 == 'fields' && $key0 == '_display_value')
		 || (($type = 'value') && $key5 === undefined && $key4 == 'tabs' && $key2 == 'fields' && $key0 == '_was_hidden_before')
		 || (($type = 'value') && $key5 === undefined && $key4 == 'tabs' && $key2 == 'fields' && $key0 == 'pressed')
		 || (($type = 'array') && $key5 === undefined && $key4 == 'tabs' && $key2 == 'fields' && $key0 == 'multiple_edit')
		 || (($type = 'value') && $key6 === undefined && $key5 == 'tabs' && $key3 == 'fields' && $key1 == 'multiple_edit' && $key0 == '_changed')) {
			
			//Update any values from the client on the server's copy
			if ($type == 'value') {
				if (('function' != typeof $clientTags[$key0]) && ('object' != typeof $clientTags[$key0])) {
					$serverTags[$key0] = $clientTags[$key0];
				}
			
			//For arrays, check them recursively
			} else if ($type == 'array') {
				if ('object' == typeof $clientTags[$key0]) {
					$serverTags[$key0] = {};
					zenarioAB.syncAdminBoxFromClientToServerR($serverTags[$key0], $clientTags[$key0], $key0, $key1, $key2, $key3, $key4, $key5);
				}
			}
		}
	}
};


zenarioAB.draw = function() {
	if (zenarioAB.isOpen && zenarioAB.loaded && zenarioAB.tabHidden) {
		zenarioAB.draw2();
	}
};


zenarioAB.draw2 = function() {
	
	if (!zenarioAB.tuix.tabs) {
		return;
	}
	
	//Add wrapper CSS classes
	get('zenario_fbAdminFloatingBox').className =
		zenarioAB.baseCSSClass +
		' ' +
		(zenarioAB.tuix.css_class || 'zenario_fab_default_style') + 
		' ' +
		(engToBoolean(zenarioAB.tuix.hide_tab_bar)?
			'zenario_admin_box_with_tabs_hidden'
		  : 'zenario_admin_box_with_tabs_shown');
	
	//Don't show the requested tab if it has been hidden
	if (zenarioAB.tuix.tab && (!zenarioAB.tuix.tabs[zenarioAB.tuix.tab] || zenarioA.hidden(zenarioAB.tuix.tabs[zenarioAB.tuix.tab]))) {
		zenarioAB.tuix.tab = false;
	}
	
	//Set the HTML for the floating boxes tabs and title
	get('zenario_fabTabs').innerHTML = zenarioAB.drawTabs();
	
	
	var isReadOnly = !zenarioAB.editModeOnBox(),
		html = '';
	
	zenarioAB.setTitle(zenarioAB.tuix.title, isReadOnly);
	zenarioAB.showCloseButton();
	
	//Set the html for the save and continue button
	if (zenarioAB.tuix.save_and_continue_button_message) {
		html += '<input id="zenarioAFB_save_and_continue"  type="button" value="' + htmlspecialchars(zenarioAB.tuix.save_and_continue_button_message) + '"';
		
		if (isReadOnly) {
			html += ' class="submit_disabled"/>';
		} else {
			html += ' class="submit_selected" onclick="' + zenarioAB.globalName + '.save(undefined, true);"/>';
		}
	}
	
	//Set the html for the save and continue button
	if (zenarioAB.createAnotherObject) {
		html += '<input id="zenarioAFB_save_and_continue"  type="button" value="' + phrase.createAnother + '"';
		
		if (isReadOnly) {
			html += ' class="submit_disabled"/>';
		} else {
			html += ' class="submit_selected" onclick="' + zenarioAB.globalName + '.save(undefined, false, true);"/>';
		}
	}

	
	//Set the html for the save button
	html += '<input id="zenarioAFB_save"  type="button" value="' + ifNull(htmlspecialchars(zenarioAB.tuix.save_button_message), phrase.save) + '"';
	
	if (isReadOnly) {
		html += ' class="submit_disabled"/>';
	} else {
		html += ' class="submit_selected" onclick="' + zenarioAB.globalName + '.save();"/>';
	}

	
	//Set the html for the cancel button, if enabled
	if (zenarioAB.tuix.cancel_button_message) {
		html += '<input type="button" value="' + htmlspecialchars(zenarioAB.tuix.cancel_button_message) + '" onclick="' + zenarioAB.globalName + '.close();">';
	}
	
	if (zenarioAB.tuix.extra_button_html) {
		html += zenarioAB.tuix.extra_button_html;
	}
	
	get('zenario_fbButtons').innerHTML = html;
	
	//Show the box
	get('zenario_fbAdminFloatingBox').style.display = 'block';
	
	//Set the floating box to the max height for the user's screen
	zenarioAB.size(true);
	
	zenarioA.nowDoingSomething(false);
	
	
	var cb = new zenario.callback,
		html = zenarioAB.drawFields(cb);
	
	zenarioAB.animateInTab(html, cb, $('#zenario_abtab'));

	zenarioAB.shownTab = zenarioAB.tuix.tab;
	delete zenarioAB.lastScrollTop;
	
	zenarioAB.startPoking();
};




zenarioAB.setTitle = function(title, isReadOnly) {
	
	if (!title) {
		$('#zenario_fabTitleWrap').css('display', 'none');
	} else {
		$('#zenario_fabTitleWrap').css('display', 'block');
		$('#zenario_fabTitleWrap').addClass(' zenario_no_drag');
		
		get('zenario_fabTitle').innerHTML = htmlspecialchars(title);
	}
	
	if (isReadOnly) {
		$('#zenario_fabBox_readonlyMarker').css('display', 'block');
	} else {
		$('#zenario_fabBox_readonlyMarker').css('display', 'none');
	}
};

zenarioAB.showCloseButton = function() {
	if (this.tuix.cancel_button_message) {
		$('#zenario_fbAdminFloatingBox .zenario_fabClose').css('display', 'none');
	} else {
		$('#zenario_fbAdminFloatingBox .zenario_fabClose').css('display', 'block');
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
		newWidth,
		windowSizedChanged,
		boxHeight,
		formHeight,
		maxFormHeight,
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
					get('zenario_fbButtons').style.paddingBottom = '7px';
					get('zenario_fabTabs').style.display = 'none';
				} else {
					get('zenario_fbMain').style.top = '24px';
					get('zenario_fbButtons').style.paddingBottom = '31px';
					get('zenario_fabTabs').style.display = zenario.browserIsIE()? '' : 'inherit';
				}
			}
			
			paddingHeight = zfabPaddingHeight;
			if (hideTabBar) {
				paddingHeight -= zfabTabBarHeight;
			}
			
			paddingHeight += $('#zenario_fabTitleWrap').height();
			
			boxHeight = Math.floor(height * 0.96);
			maxBoxWidth = Math.floor(width * 0.96);
			formHeight = boxHeight - paddingHeight;
			
			maxFormHeight = 1 * (zenarioAB.tuix && zenarioAB.tuix.max_height);
			
			if (maxFormHeight
			 && formHeight > maxFormHeight) {
				formHeight = maxFormHeight;
				boxHeight = maxFormHeight + paddingHeight;
			}
	
			if (formHeight && formHeight > 0) {
				$('#zenario_fbAdminInner').height(formHeight);
			}
	
			if (boxHeight && boxHeight > 0) {
				$('#zenario_fabBox').height(boxHeight);
				$('#zenario_fabPreview').height(boxHeight);
			}
			
			previewHidden = !zenarioAB.hasPreviewWindow || maxBoxWidth < zfabMinWidthWithPreview;
			
			if (previewHidden) {
				newWidth = zfabWidthNoPreview;
				
				zenarioAB.previewWidth = false;
				zenarioAB.lastPreviewValues = false;
			
			
			} else {
				//If we found the width of the slot earlier, don't allow the preview window to be larger than that.
				//Also don't let the combined width of the preview window and the admin box be larger than the window!
				if (zenarioAB.previewSlotWidth) {
					newWidth = Math.min(maxBoxWidth, zfabWidthNoPreview + zfabPreviewBorderWidth + zenarioAB.previewSlotWidth);
				} else {
					newWidth = maxBoxWidth;
				}
				
				//Note down the size that the preview window will be after all of thise
				zenarioAB.previewWidth = newWidth - zfabWidthNoPreview - zfabPreviewBorderWidth;
				
				$('#zenario_fabPreview').width(zenarioAB.previewWidth);
				
				//Show or hide the description of the width
				if (zenarioAB.previewSlotWidthInfo) {
					$('#zenario_fabPreviewInfo').show().text(zenarioAB.previewSlotWidthInfo);
				} else {
					$('#zenario_fabPreviewInfo').hide();
				}
			}
			
			if (!zenarioAB.hasPreviewWindow) {
				$('#zenario_fb' + zfabName)
					.addClass('zenario_fab_with_no_preview')
					.removeClass('zenario_fab_with_preview')
					.removeClass('zenario_fab_with_preview_hidden')
					.removeClass('zenario_fab_with_preview_shown');
			
			} else if (previewHidden) {
				$('#zenario_fb' + zfabName)
					.removeClass('zenario_fab_with_no_preview')
					.addClass('zenario_fab_with_preview')
					.addClass('zenario_fab_with_preview_hidden')
					.removeClass('zenario_fab_with_preview_shown');
			
			} else {
				$('#zenario_fb' + zfabName)
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
			
			
			zenarioA.adjustBox(zfabName, false, newWidth, zfabLeft, zfabTop);
		}
	}
	
	//Stop the admin from scrolling the page bellow, to prevent a bug with TinyMCE
	if (zenarioAB.isOpen && zenarioAB.documentScrollTop !== false) {
		$(zenario.browserIsSafari()? 'body' : 'html').scrollTop(zenarioAB.documentScrollTop);
	}
	
	zenarioAB.sizing = setTimeout(zenarioAB.size, 250);
};


//Get a URL needed for an AJAX request
zenarioAB.returnAJAXURL = function() {
	return URLBasePath + 'zenario/admin/ajax.php' +
									'?_json=1&_ab=1' +
									'&path=' + encodeURIComponent(zenarioAB.path) +
									zenario.urlRequest(zenarioAB.getRequestKey);
};



//Attempt to get the URL of a preview
zenarioAB.pluginPreviewURL = function(slotName, instanceId) {
	
	if (zenarioAB.path != "plugin_settings"
	 || !zenario.slots
	 || !(slotName = slotName || (zenarioAB.tuix && zenarioAB.tuix.key && zenarioAB.tuix.key.slotName))
	 || !(instanceId = instanceId || (zenarioAB.tuix && zenarioAB.tuix.key && zenarioAB.tuix.key.instanceId) || zenario.slots[slotName].instanceId)) {
		return false;
	}
	
	var grid = zenarioA.getGridSlotDetails(slotName),
		requests = _.clone(zenarioA.importantGetRequests),
		c, clas,
		cssClasses = (grid && grid.cssClass && grid.cssClass.split(' ')) || [];
	
	requests.cVersion = zenario.cVersion;
	requests.slotName = slotName;
	requests.instanceId = instanceId;
	requests.method_call = 'showSingleSlot';
	requests.fakeLayout = 1;
	requests.grid_columns = grid.columns;
	requests.grid_container = grid.container;
	
	//Remember the width of the slot. Don't resize the preview window to be any bigger than this.
	zenarioAB.previewSlotWidth = grid.pxWidth;
	//Also remember the full description of the width
	zenarioAB.previewSlotWidthInfo = grid.widthInfo;
	
	//If the preview window is open and we've previously set its size, request in the URL that the
	//preview be the size of the window that we opened
	if (zenarioAB.previewWidth) {
		requests.grid_pxWidth = zenarioAB.previewWidth;
	
	//Otherwise just use the width of the slot for now
	} else {
		requests.grid_pxWidth = zenarioAB.previewSlotWidth;
	}
	
	//Include all of the slot's custom CSS classes.
	requests.grid_cssClass = '';
	foreach (cssClasses as c => clas) {
		//For the most part we just want the custom classes, so filter "alpha", "omega" and the "spans".
		if (clas != 'alpha'
		 && clas != 'omega'
		 && !clas.match(/^span[\d_]*$/)) {
			requests.grid_cssClass += clas + ' ';
		}
	}
	
	return zenario.linkToItem(zenario.cID, zenario.cType, requests);
};


//If this is a plugin settings FAB with a preview window, changing the value of any field
//should update the preview if needed
zenarioAB.addExtraAttsForTextFields = function(field, extraAtt) {
	if (zenarioAB.hasPreviewWindow) {
		extraAtt.onkeyup =
			ifNull(extraAtt.onkeyup, '', '') +
			" zenarioAB.updatePreview();";
	}
};

zenarioAB.fieldChange = function(id, lov) {
	zenarioAB.updatePreview(750);
	methodsOf(zenarioAF).fieldChange.call(zenarioAB, id, lov);
};

//This function updates the preview, after a short delay to stop lots of spam updates happening all at once
zenarioAB.updatePreview = function(delay) {
	if (zenarioAB.hasPreviewWindow && !zenarioAB.previewHidden) {
		zenario.actAfterDelayIfNotSuperseded('fabUpdatePreview', zenarioAB.updatePreview2, delay || 1000);
	}
};

zenarioAB.updatePreview2 = function() {
	
	//Get the values of the plugin settings on this FAB
	var previewValues = JSON.stringify(zenarioAB.getValues1D(true));
	
	//If they've changed since last time, refresh the preview window
	if (zenarioAB.lastPreviewValues != previewValues) {
		zenarioAB.lastPreviewValues = previewValues;
		
		$('<form action="' + htmlspecialchars(zenarioAB.pluginPreviewURL()) + '" method="post" target="zenario_fabPreviewFrame">' +
			'<input name="overrideSettings" value="' + htmlspecialchars(previewValues) + '"/>' +
		'</form>').appendTo('body').hide().submit().remove();
	}
};

zenarioAB.showPreviewInPopoutBox = function() {
	
	var url = zenarioAB.pluginPreviewURL(),
		previewValues = JSON.stringify(zenarioAB.getValues1D(true));
	
	if (!url) {
		return;
	}
	
	$.colorbox({
		width: Math.floor($(window).width() * 0.7),
		height: Math.floor($(window).height() * 0.9),
		iframe: true,
		preloading: false,
		open: true,
		title: zenarioAB.previewSlotWidthInfo || phrase.preview,
		href: url + '&overrideSettings=' + encodeURIComponent(previewValues),
		className: 'zenario_plugin_preview_popout_box'
	});
	$('#colorbox,#cboxOverlay,#cboxWrapper').css('z-index', '333000');
};


zenarioAB.slideToggle = function() {
	if (zenarioAB.isSlidUp) {
		zenarioAB.slideDown();
	} else {
		zenarioAB.slideUp();
	}
};
	
zenarioAB.slideUp = function() {
	
	if (zenarioAB.isSlidUp) {
		return;
	}
	
	var height = $('#zenario_fabBox_Header').height(),
		//height = zfabPaddingHeight + zfabTabBarHeight,
		$zenario_fabBox = $('#zenario_fabBox');
	
	zenarioAB.heightBeforeSlideUp = $zenario_fabBox.height();
	
	$('#zenario_fabBox_Body').stop(true).slideUp();
	
	$zenario_fabBox.stop(true).animate({height: height});
	
	$('#zenario_fabSlideToggle')
		.addClass('zenario_fabSlideToggleUp')
		.removeClass('zenario_fabSlideToggleDown');
	
	zenarioAB.isSlidUp = true;
};

zenarioAB.slideDown = function() {
	
	if (!zenarioAB.isSlidUp) {
		return;
	}
	
	$('#zenario_fabBox_Body').stop(true).slideDown();
	$('#zenario_fabBox').stop(true).animate({height: zenarioAB.heightBeforeSlideUp}, function() {
		zenarioAB.size(true);
	});
	
	$('#zenario_fabSlideToggle')
		.addClass('zenario_fabSlideToggleDown')
		.removeClass('zenario_fabSlideToggleUp');
	
	zenarioAB.isSlidUp = false;
};

//If someone clicks on a tab, make sure that the form isn't hidden first!
zenarioAB.clickTab = function(tab) {
	zenarioAB.slideDown();
	methodsOf(zenarioAF).clickTab.call(zenarioAB, tab);
};




zenarioAB.stopPoking = function() {
	if (zenarioAB.poking) {
		clearInterval(zenarioAB.poking);
	}
	zenarioAB.poking = false;
};

zenarioAB.startPoking = function() {
	if (!zenarioAB.poking) {
		zenarioAB.poking = setInterval(zenarioAB.poke, 2 * 60 * 1000);
	}
};

zenarioAB.poke = function() {
	zenario.ajax(URLBasePath + 'zenario/admin/quick_ajax.php?keep_session_alive=1')
};





},
	zenarioAF
);