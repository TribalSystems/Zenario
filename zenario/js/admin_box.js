/*
 * Copyright (c) 2015, Tribal Limited
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










//Open an admin floating box
zenarioAB.open = function(path, key, tab, values, callBack) {
	
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
	zenarioAB.getRequestKey = key;
	zenarioAB.changed = {};

	var html = zenarioA.microTemplate(zenarioAB.mtPrefix, {});
	
	zenarioAB.baseCSSClass = 'zenario_fbAdmin zenario_admin_box zab_' + path;
	zenarioA.openBox(html, zenarioAB.baseCSSClass, 'AdminFloatingBox', false, 800, 50, 2, true, true, '.zenario_jqmHead', false);
	
	//...but hide the box itself, so only the overlay shows
	get('zenario_fbAdminFloatingBox').style.display = 'none';
	
	//If any Admin Boxes are open, set a warning message for if an admin tries to leave the page 
	window.onbeforeunload = zenarioA.onbeforeunload;
	
	
	zenarioAB.start(path, key, tab, values);
};



zenarioAB.refreshParentAndClose = function(disallowNavigation, saveAndContinue) {
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
		
	} else if (zenarioAB.tuix.key.slotName) {
		//Refresh the slot if zenarioAB was a plugin settings FAB
		zenario.refreshPluginSlot(zenarioAB.tuix.key.slotName, '', zenarioA.importantGetRequests);
		
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
	
	if (!saveAndContinue) {
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
	
	if (!keepMessageWindowOpen) {
		zenarioA.closeFloatingBox();
	}
	
	zenarioA.closeBox('AdminFloatingBox');
	zenarioAB.isOpen = false;
	
	//Return the page to it's original scroll position, before the box was opened
	if (zenarioAB.documentScrollTop !== false) {
		$(zenario.browserIsSafari()? 'body' : 'html').scrollTop(zenarioAB.documentScrollTop);
		zenarioAB.documentScrollTop = false;
	}
	
	delete zenarioAB.tuix;
	
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
	
	//Add wrapper CSS classes
	get('zenario_fbAdminFloatingBox').className =
		zenarioAB.baseCSSClass +
		' ' +
		(zenarioAB.tuix.css_class || '') + 
		' ' +
		(engToBoolean(zenarioAB.tuix.hide_tab_bar)? 'zenario_admin_box_with_tabs_hidden' : 'zenario_admin_box_with_tabs_shown');
	
	//Don't show the requested tab if it has been hidden
	if (zenarioAB.tuix.tab && (!zenarioAB.tuix.tabs[zenarioAB.tuix.tab] || zenarioA.hidden(zenarioAB.tuix.tabs[zenarioAB.tuix.tab]))) {
		zenarioAB.tuix.tab = false;
	}
	
	//Set the HTML for the floating boxes tabs and title
	get('zenario_jqmTabs').innerHTML = zenarioAB.drawTabs();
	
	zenarioAB.setTitle(zenarioAB.tuix.title);
	zenarioAB.showCloseButton();
	
	
	html = '';
	
	//Set the html for the save and continue button
	if (zenarioAB.tuix.save_and_continue_button_message) {
		html += '<input id="zenarioAFB_save_and_continue"  type="button" value="' + htmlspecialchars(zenarioAB.tuix.save_and_continue_button_message) + '"';
		
		if (!zenarioAB.editModeOnBox()) {
			html += ' class="submit_disabled"/>';
		} else {
			html += ' class="submit_selected" onclick="' + zenarioAB.globalName + '.save(undefined, true);"/>';
		}
	}

	
	//Set the html for the save button
	html += '<input id="zenarioAFB_save"  type="button" value="' + ifNull(htmlspecialchars(zenarioAB.tuix.save_button_message), phrase.save) + '"';
	
	if (!zenarioAB.editModeOnBox()) {
		html += ' class="submit_disabled"/>';
	} else {
		html += ' class="submit_selected" onclick="' + zenarioAB.globalName + '.save();"/>';
	}

	
	//Set the html for the cancel button, if enabled
	if (zenarioAB.tuix.cancel_button_message) {
		html += '<input type="button" value="' + htmlspecialchars(zenarioAB.tuix.cancel_button_message) + '" onclick="' + zenarioAB.globalName + '.close();">';
	}
	
	get('zenario_fbButtons').innerHTML = html;
	
	//Set the floating box to the max height for the user's screen
	zenarioAB.size();
	
	//Show the box
	get('zenario_fbAdminFloatingBox').style.display = 'block';
	
	zenarioA.nowDoingSomething(false);
	
	
	var cb = new zenario.callback,
		html = zenarioAB.drawFields(cb);
	
	zenarioAB.animateInTab(html, cb, $('#zenario_abtab'));

	zenarioAB.shownTab = zenarioAB.tuix.tab;
	delete zenarioAB.lastScrollTop;
	
	zenarioAB.startPoking();
};



//Automatically set the box to the correct height for the users screen, or the maximum height requested, whichever is smaller
zenarioAB.size = function() {
	
	if (zenarioAB.sizing) {
		clearTimeout(zenarioAB.sizing);
	}
	
	if (get('zenario_fbMain')) {
		if (zenarioAB.tuix && engToBoolean(zenarioAB.tuix.hide_tab_bar)) {
			get('zenario_fbMain').style.top = '0px';
			get('zenario_fbButtons').style.paddingBottom = '7px';
			get('zenario_jqmTabs').style.display = 'none';
		} else {
			get('zenario_fbMain').style.top = '24px';
			get('zenario_fbButtons').style.paddingBottom = '31px';
			get('zenario_jqmTabs').style.display = zenario.browserIsIE()? '' : 'inherit';
		}
	}
	
	if (get('zenario_fbAdminInner')) {
		var height = Math.floor($(window).height() * 0.96 - (zenario.browserIsIE()? 210 : zenario.browserIsSafari()? 202 : 205));
		
		if (zenarioAB.tuix
		 && zenarioAB.tuix.max_height
		 && height > zenarioAB.tuix.max_height) {
			height = zenarioAB.tuix.max_height;
		}
		
		if (zenarioAB.tuix && engToBoolean(zenarioAB.tuix.hide_tab_bar)) {
			height = 1*height + 24;
		}
		
		if ((height = 1*height) > 0) {
			get('zenario_fbAdminInner').style.height = height + 'px';
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