/*
 * Copyright (c) 2018, Tribal Limited
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
	zenarioAF, zenarioABToolkit
) {
	"use strict";

	var methods = methodsOf(zenarioABToolkit);


//Open an admin floating box
methods.open = function(path, key, tab, values, callBack, createAnotherObject, reopening, passMatchedIds) {
	
	//Don't allow a box to be opened if Organizer is opened and covering the screen
	if (zenarioA.checkIfBoxIsOpen('AdminOrganizer')) {
		return false;
	}
	
	//Stop the page behind from scrolling
	zenario.disableScrolling(this.globalName);
	
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
	this.isOpen = true;
	this.callBack = callBack;
	this.passMatchedIds = passMatchedIds;
	this.createAnotherObject = createAnotherObject;
	this.getRequestKey = key;
	this.changed = {};
	
	this.isSlidUp =
	this.heightBeforeSlideUp =
	this.hasPreviewWindow =
	this.lastPreviewValues =
	this.previewValues =
	this.previewSlotWidth = false;
	this.previewSlotWidthInfo = '';
	this.previewHidden = true;
	
	this.baseCSSClass = 'zenario_fbAdmin zenario_admin_box zenario_fab_' + path;
	
	if (!reopening) {
		var html = this.microTemplate(this.mtPrefix, {});
		this.openBox(html);
	}
	
	//If any Admin Boxes are open, set a warning message for if an admin tries to leave the page 
	window.onbeforeunload = zenarioT.onbeforeunload;
	
	this.start(path, key, tab, values);
	
	return this.cb = new zenario.callback;
};

methods.openBox = function(html) {
	//...placeholder, needs overwriting
};

methods.closeBox = function() {
	//...placeholder, needs overwriting
};

methods.updateHash = function() {
	//...placeholder, needs overwriting
};


methods.initFields = function() {
	this.hasPreviewWindow = !!this.pluginPreviewDetails();
	methodsOf(zenarioAF).initFields.call(this);
};


methods.refreshParentAndClose = function(disallowNavigation, saveAndContinue, createAnother) {
	zenarioA.nowDoingSomething(false);
	
	var slotName,
		requests = {};
	
	if (!saveAndContinue) {
		this.isOpen = false;
		this.updateHash();
	}
	
	//Attempt to work out what to do next.
	if (this.callBack && !saveAndContinue) {
		var values;
		if (values = this.getValueArrayofArrays()) {
			this.callBack(this.tuix.key, values);
		}
		
	} else if (zenarioO.init && (zenarioA.isFullOrganizerWindow || zenarioA.checkIfBoxIsOpen('og'))) {
		//Reload Organizer if this window is an Organizer window
		var id = false;
		
		if (defined(this.tuix.key.id)) {
			id = this.tuix.key.id;
		} else {
			foreach (this.tuix.key as var i) {
				id = this.tuix.key[i];
				break;
			}
		}
		
		zenarioO.refreshToShowItem(id,
			createAnother && phrase.createdAnother,
			!saveAndContinue && phrase.savedButNotShown);
	
	//Check if this FAB was opened from a specific slot (e.g. this was a plugin settings FAB)
	} else if (zenario.cID && (slotName = this.tuix.key.slotName)) {
		
		//If this slot is managed by the conductor, use the conductor's refresh function
		//to ensure we correctly keep the current slide, state and all of the variables.
		//Otherwise do a normal slot refresh.
		zenario.refreshSlot(slotName, zenarioA.importantGetRequests);
		
		if (zenarioAT.init) {
			zenarioAT.init();
		}
		
	} else if (zenario.cID && (this.path == 'zenario_menu' || this.path == 'zenario_menu_text')) {
		//If this is the front-end, and this was a menu FAB, just reload the menu plugins
		zenarioA.reloadMenuPlugins();
		
		if (zenarioAT.init) {
			zenarioAT.init();
		}
	
	} else if (disallowNavigation || saveAndContinue) {
		//Don't allow any of the actions below, as they involve navigation
	
	//Otherwise build up a URL from the primary key, if it looks valid
	} else
	if (this.tuix.key.cID) {
		
		//If this is the current content item, add any important get requests from plugins
		if (this.tuix.key.cID == zenario.cID
		 && this.tuix.key.cType == zenario.cType) {
			requests = zenarioA.importantGetRequests;
		}
		
		zenario.goToURL(zenario.linkToItem(this.tuix.key.cID, this.tuix.key.cType, requests));
	
	//For any other Admin Toolbar changes, reload the page
	} else if (zenarioAT.init) {
		
		//Try to keep to the same version if possible.
		requests = _.clone(zenarioA.importantGetRequests);
		requests.cVersion = zenario.cVersion;
		
		zenario.goToURL(zenario.linkToItem(zenario.cID, zenario.cType, requests));
	}
	
	var popout_message = this.tuix.popout_message;
	createAnother = createAnother && this.createAnotherObject;
	
	if (!saveAndContinue && !createAnother) {
		if (this.cb) this.cb.call();
		this.close();
	}
	
	if (popout_message) {
		zenarioA.showMessage(popout_message, true, false);
	}
	
	if (saveAndContinue) {
		this.changed = {};
		
		if (this.tuix.tabs) {
			foreach (this.tuix.tabs as var i => var zenarioABTab) {
				if (zenarioABTab) {
					if (this.editModeOn(i)) {
						this.tuix.tabs[i]._saved_and_continued = true;
					}
				}
			}
		}
		
		this.sortTabs();
		this.draw();
	
	} else if (createAnother) {
		$('#zenario_abtab').clearQueue();
		delete this.tuix;
		this.open(
			this.createAnotherObject.path,
			this.getRequestKey,
			this.createAnotherObject.tab,
			this.createAnotherObject.values,
			undefined,
			this.createAnotherObject,
			true,
			this.passMatchedIds);
	}
};


methods.close = function(keepMessageWindowOpen) {
	//Close TinyMCE if it is open
	this.callFunctionOnEditors('remove');
	zenarioA.nowDoingSomething(false);
	
	if (this.sizing) {
		clearTimeout(this.sizing);
	}
	this.stopPoking();
	zenario.clearAllDelays();
	
	if (!keepMessageWindowOpen) {
		zenarioA.closeFloatingBox();
	}
	
	this.isOpen = false;
	this.closeBox();
	
	//Allow the page behind to scroll again
	zenario.enableScrolling(this.globalName);
	
	delete this.cb;
	delete this.tuix;
	delete this.previewValues;
	delete this.lastPreviewValues;
	delete this.previewSlotWidth;
	delete this.previewSlotWidthInfo;
	
	return false;
};

methods.closeButton = function(onlyCloseIfNoChanges) {
	//Check if there is an editor open
	var message = zenarioT.onbeforeunload();
	
	//If there was, give the Admin a chance to stop leaving the page
	if (!defined(message) || (!onlyCloseIfNoChanges && confirm(message))) {
		if (this.isOpen) {
			this.close();
		}
	}
	
	return false;
};












methods.draw = function() {
	if (this.isOpen && this.loaded && this.tabHidden) {
		this.draw2();
	}
};


methods.draw2 = function() {
	
	if (!this.tuix.tabs) {
		return;
	}
	
	//Add wrapper CSS classes
	this.get('zenario_fbAdminFloatingBox').className =
		this.baseCSSClass +
		' ' +
		(this.tuix.css_class || 'zenario_fab_default_style') + 
		' ' +
		(engToBoolean(this.tuix.hide_tab_bar)?
			'zenario_admin_box_with_tabs_hidden'
		  : 'zenario_admin_box_with_tabs_shown');
	
	var tuix = this.tuix;
	
	//Don't show the requested tab if it has been hidden
	if (tuix.tab
	 && (!tuix.tabs[tuix.tab]
		//zenarioT.hidden(tuixObject, lib, item, id, button, column, field, section, tab, tuix)
	  || zenarioT.hidden(undefined, this, undefined, tuix.tab, undefined, undefined, undefined, undefined, tuix.tabs[tuix.tab]))) {
		tuix.tab = false;
	}
	
	//Set the HTML for the floating boxes tabs and title
	this.get('zenario_fabTabs').innerHTML = this.drawTabs();
	
	
	var isReadOnly = !this.editModeOnBox(),
		html = '',
		m = {
			isReadOnly: isReadOnly
		};
	
	this.setTitle(isReadOnly);
	this.showCloseButton();
	
	this.get('zenario_fbButtons').innerHTML = this.microTemplate(this.mtPrefix + '_buttons', m);
	zenario.addJQueryElements('#zenario_fbButtons ', true);
	
	//Show the box
	this.get('zenario_fbAdminFloatingBox').style.display = 'block';
	
	//Set the floating box to the max height for the user's screen
	this.tallAsPossibleField = undefined;
	this.size(true);
	
	zenarioA.nowDoingSomething(false);
	
	
	var cb = new zenario.callback,
		html = this.drawFields(cb);
	
	cb.after(this.makeFieldAsTallAsPossible);
	
	this.animateInTab(html, cb, $('#zenario_abtab'));

	this.shownTab = tuix.tab;
	delete this.lastScrollTop;
	
	this.startPoking();
};




methods.setTitle = function(isReadOnly) {
	//Do nothing..?
};

methods.showCloseButton = function() {
	if (this.tuix.cancel_button_message) {
		$('#zenario_fbAdminFloatingBox .zenario_fabClose').css('display', 'none');
	} else {
		$('#zenario_fbAdminFloatingBox .zenario_fabClose').css('display', 'block');
	}
};



//Get a URL needed for an AJAX request
methods.returnAJAXURL = function(action) {
	
	//If an admin_box button requests all of the ids that are currently matched in Organizer,
	//we'll need to get the details of the last Organizer panel accessed (the requests needed
	//should be stored in zenarioO.lastRequests) and fire up the Organizer Panel to get the list of
	//ids.
	//When this script is done, it should then call admin_boxes.ajax.php.
	if (action == 'start'
	 && this.passMatchedIds
	 && zenarioO.lastRequests) {
		return URLBasePath +
			'zenario/admin/organizer.ajax.php' +
			'?_get_matched_ids=1' +
			'&_fab_path=' + encodeURIComponent(this.path) +
			'&path=' + encodeURIComponent(zenarioO.path) +
			zenario.urlRequest(zenarioO.lastRequests) +
			zenario.urlRequest(this.getRequestKey);
	
	//Otherwise we can call admin_boxes.ajax.php directly.
	} else {
		return URLBasePath +
			'zenario/admin/admin_boxes.ajax.php' +
			'?path=' + encodeURIComponent(this.path) +
			zenario.urlRequest(this.getRequestKey);
	}
};



//Attempt to get the URL of a preview
methods.pluginPreviewDetails = function(loadValues, fullPage, fullWidth, slotName, instanceId) {
	
	//Disallow for plugins in nests
	if (this.tuix && this.tuix.key && (this.tuix.key.nest || this.tuix.key.eggId)) {
		return false;
	}
	
	
	var requests = _.clone(zenarioA.importantGetRequests),
		postName,
		postValues,
		includeSlotInfo = !fullPage;
	
	switch (this.path) {
		case 'zenario_skin_editor':
			includeSlotInfo = false;
		
		case 'plugin_css_and_framework':
			postName = 'overrideFrameworkAndCSS';
			
			if (loadValues) {
				postValues = JSON.stringify(this.getValues1D(false, true, false, true, true));
			}
			break;
		
		case 'plugin_settings':
			postName = 'overrideSettings';
			
			if (loadValues) {
				postValues = JSON.stringify(this.getValues1D(true, false));
			}
			break;
			
		default:
			return false;
	}
	
	requests.cVersion = zenario.cVersion;
	slotName = slotName || (this.tuix && this.tuix.key && this.tuix.key.slotName);
	instanceId = instanceId || (this.tuix && this.tuix.key && this.tuix.key.instanceId)
							|| (zenario.slots && zenario.slots[slotName] && zenario.slots[slotName].instanceId);
	
	if (includeSlotInfo) {
		if (!slotName || !instanceId) {
			return false;
		}
	
		var grid = zenarioA.getGridSlotDetails(slotName),
			c, clas,
			cssClasses = (grid && grid.cssClass && grid.cssClass.split(' ')) || [];
	
		requests.method_call = 'showSingleSlot';
		requests.fakeLayout = 1;
		requests.grid_columns = grid.columns;
		requests.grid_container = grid.container;
	
		//Remember the width of the slot. Don't resize the preview window to be any bigger than this.
		this.previewSlotWidth = grid.pxWidth;
		//Also remember the full description of the width
		this.previewSlotWidthInfo = grid.widthInfo;
	
		//If the preview window is open and we've previously set its size, request in the URL that the
		//preview be the size of the window that we opened
		if (this.previewWidth && !fullWidth) {
			requests.grid_pxWidth = this.previewWidth;
	
		//Otherwise just use the width of the slot for now
		} else {
			requests.grid_pxWidth = this.previewSlotWidth;
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
	} else {
		requests._show_page_preview = 1;
	}
	
	if (slotName) {
		zenario_conductor.mergeRequests(slotName, requests);
		requests.slotName = slotName;
	}
	if (instanceId) requests.instanceId = instanceId;
	
	return {
		url: zenario.linkToItem(zenario.cID, zenario.cType, requests),
		postName: postName,
		postValues: postValues
	};
};


//If this is a plugin settings FAB with a preview window, changing the value of any field
//should update the preview if needed
methods.addExtraAttsForTextFields = function(field, extraAtt) {
	if (this.hasPreviewWindow) {
		extraAtt.onkeyup =
			(extraAtt.onkeyup || '') +
			" " + this.globalName + ".updatePreview();";
	}
};

methods.fieldChange = function(id, lov) {
	this.updatePreview(750);
	methodsOf(zenarioAF).fieldChange.call(this, id, lov);
};

//This function updates the preview, after a short delay to stop lots of spam updates happening all at once
methods.updatePreview = function(delay) {
	var that = this;
	if (this.hasPreviewWindow && !this.previewHidden) {
		zenario.actAfterDelayIfNotSuperseded('fabUpdatePreview', function() {
	
			//Get the values of the plugin settings on this FAB
			var preview = that.pluginPreviewDetails(true);
	
			//If they've changed since last time, refresh the preview window
			if (preview
			 && that.lastPreviewValues != preview.postValues) {
				that.lastPreviewValues = that.previewValues = preview.postValues;
		
				that.submitPreview(preview);
			}
		}, delay || 1000);
	}
};

var iframeCount = 0;

methods.submitPreview = function(preview, $parent, cssClassName) {
	
	$parent = $parent || $('#zenario_fabPreview');
	cssClassName = cssClassName || 'zenario_fabPreviewFrame';
	
	var id = 'zenario_previewFrame' + ++iframeCount,
		$old = $parent.find('.' + cssClassName).not('.beingRemoved'),
		$iframe = $(zenarioT.html('iframe', 'id', id, 'name', id, 'class', cssClassName)),
		
		//Check if the preview has been scrolled down
		doc = $old[0] && $old[0].contentDocument,
		scrollTop = 1 * (doc && $(doc).scrollTop());
	
	if (scrollTop) {
		preview.url += '&_scroll_to=' + scrollTop;
	}
	
	if ($old[0]) {
		$old
			.width($old.width())
			.height($old.height())
			.attr('id', '')
			.attr('name', '')
			.addClass('beingRemoved');
	
		setTimeout(function() {
			$old.fadeOut(600, function() {
				$old.remove();
			});
		}, 400);
	}
	
	$parent.append($iframe);
	
	this.showPreviewViaPost(preview, id);
};

methods.showPreviewViaPost = function(preview, iframeName) {
	
	$('<form action="' + htmlspecialchars(preview.url) + '" method="post" target="' + htmlspecialchars(iframeName) + '">' +
		'<input name="' + htmlspecialchars(preview.postName) + '" value="' + htmlspecialchars(preview.postValues) + '"/>' +
	'</form>').appendTo('body').hide().submit().remove();
};

methods.showPreviewInPopoutBox = function(fullPage, fullWidth) {
	
	var that = this,
		href,
		onComplete,
		preview = this.pluginPreviewDetails(true, fullPage, fullWidth);
	
	if (!preview) {
		return;
	}
	
	this.previewValues = preview.postValues;
	
	//Attempt to load the page via GET
	href = preview.url + '&' + encodeURIComponent(preview.postName) + '=' + encodeURIComponent(preview.postValues);
	
	//Bugfix: Loading by GET may fail if the data is too large, so use POST instead
	if (href.length >= (zenario.browserIsIE()? 2000 : 4000)) {
		href = '';
		onComplete = function() {
			that.showPreviewViaPost(preview, $('#cboxLoadedContent iframe')[0].name);
			//that.submitPreview(preview, $('#cboxLoadedContent iframe')[0]);
		};
	}
	
	$.colorbox({
		width: '95%',
		height: '90%',
		iframe: true,
		preloading: false,
		open: true,
		title: this.previewSlotWidthInfo || phrase.preview,
		className: 'zenario_plugin_preview_popout_box',
		href: href,
		onComplete: onComplete
	});
	$('#colorbox,#cboxOverlay,#cboxWrapper').css('z-index', '333000');
};



















methods.editModeOn = function(tab) {
	
	if (!this.tuix || !this.tuix.tabs) {
		return false;
	}
	
	if (!tab) {
		tab = this.tuix.tab;
	}
	
	if (this.tuix.tabs[tab].edit_mode) {
		return this.tuix.tabs[tab].edit_mode.on =
			engToBoolean(this.tuix.tabs[tab].edit_mode.enabled)
		 && (engToBoolean(this.tuix.tabs[tab].edit_mode.on)
		  || this.editModeAlwaysOn(tab));
	
	} else {
		return false;
	}
};

methods.editModeAlwaysOn = function(tab) {
	return !defined(this.tuix.tabs[tab].edit_mode.always_on)
		|| engToBoolean(this.tuix.tabs[tab].edit_mode.always_on)
		|| this.savedAndContinued(tab);
};

methods.editCancelEnabled = function(tab) {
	return this.tuix.tabs[tab].edit_mode
		&& engToBoolean(this.tuix.tabs[tab].edit_mode.enabled)
		&& !this.editModeAlwaysOn(tab);
};

methods.revertEnabled = function(tab) {
	return this.tuix.tabs[tab].edit_mode
		&& engToBoolean(this.tuix.tabs[tab].edit_mode.enabled)
		&& this.editModeAlwaysOn(tab)
		&& !this.savedAndContinued(tab)
		&& ((!defined(this.tuix.tabs[tab].edit_mode.enable_revert) && this.tuix.key && this.tuix.key.id)
		 || engToBoolean(this.tuix.tabs[tab].edit_mode.enable_revert));
};

methods.savedAndContinued = function(tab) {
	return false;
};

methods.editModeOnBox = function() {
	if (this.tuix && this.tuix.tabs && this.sortedTabs) {
		foreach (this.sortedTabs as var i) {
			var tab = this.sortedTabs[i];
			
			if (this.editModeOn(tab)) {
				return true;
			}
		}
	}
	
	return false;
};





methods.setData = function(data) {
	this.setDataDiff(data);
};

methods.sendStateToServer = function() {
	return this.sendStateToServerDiff();
};






methods.save = function(confirm, saveAndContinue, createAnother) {
	var that = this,
		url;
	
	if (!this.loaded || !(url = this.getURL('save'))) {
		return;
	}
	
	if (this.saving) {
		return;
	} else {
		
		//Use setTimeout to try and catch the case where someone was typing in a field using the keyboard,
		//then immediately presses the save button with their mouse, which would otherwise skip any onchange events
		setTimeout(function() {
		
			that.saving = true;
	
			that.differentTab = true;
			that.loaded = false;
			that.hideTab(true);
	
			that.checkValues();
	
			var post = {
				_save: true,
				_confirm: confirm? 1 : '',
				_save_and_continue: saveAndContinue,
				_box: that.sendStateToServer()};
	
			if (engToBoolean(that.tuix.download) || (that.tuix.confirm && engToBoolean(that.tuix.confirm.download))) {
				that.save2(zenario.nonAsyncAJAX(that.getURL('save'), zenario.urlRequest(post), true), saveAndContinue, createAnother);
			} else {
				that.retryAJAX(
					url,
					post,
					true,
					function(data) {
						that.save2(data, saveAndContinue, createAnother);
					},
					'saving'
				);
			}
		}, 100);
	}
};

methods.save2 = function(data, saveAndContinue, createAnother) {
	delete this.saving;
	
	var flags = data
			 && data._sync
			 && data._sync.flags || {},
		isOrganizer = zenarioO.init && !window.zenarioOQuickMode && !window.zenarioOSelectMode;
	
	
	if (flags.close_with_message) {
		this.close();
		
		if (isOrganizer) {
			zenarioO.reload();
		}
		
		zenarioA.showMessage(flags.close_with_message);
	
	} else if (flags.reload_organizer && isOrganizer) {
		this.close();
		zenarioA.rememberToast();
	
		zenarioT.uploading = false;
		zenarioO.setWrapperClass('uploading', zenarioT.uploading);
	
		zenarioO.reloadPage();

	//Open an Admin Box
	} else if (flags.open_admin_box && zenarioAB.init) {
		this.close();
		zenarioAB.open(flags.open_admin_box);

	//Go somewhere
	} else if (flags.go_to_url) {
		this.close();
		zenarioA.rememberToast();
		zenario.goToURL(zenario.addBasePath(flags.go_to_url), true);
	
	} else if (flags.valid) {
		
		if (flags.confirm) {
			this.load(data);
			this.sortTabs();
			this.draw();
			this.showConfirm(saveAndContinue, createAnother);
		
		} else if (flags.download) {
			this.get('zenario_iframe_form').action = this.getURL('download');
			this.get('zenario_iframe_form').innerHTML =
				'<input type="hidden" name="_download" value="1"/>' +
				'<input type="hidden" name="_box" value="' + htmlspecialchars(this.sendStateToServer()) + '"/>';
			this.get('zenario_iframe_form').submit();
			
			if (saveAndContinue) {
				data = data.substr(15);
				this.load(data);
			}
			
			this.refreshParentAndClose(true, saveAndContinue, createAnother);
		
		} else if (flags.saved) {
			this.load(data);
			this.refreshParentAndClose(false, saveAndContinue, createAnother);
		
		} else {
			this.close();
			zenarioA.showMessage(data, true, 'error');
		}
	
	} else {
		this.load(data);
		this.sortTabs();
		this.switchToATabWithErrors();
		this.draw();
	}
};


methods.showConfirm = function(saveAndContinue, createAnother) {
	if (this.tuix && this.tuix.confirm && engToBoolean(this.tuix.confirm.show)) {
		
		var message = this.tuix.confirm.message;
		
		if (!engToBoolean(this.tuix.confirm.html)) {
			message = htmlspecialchars(message, true);
		}
		
		var buttons =
			'<input type="button" class="submit_selected" value="' + this.tuix.confirm.button_message + '" onclick="' + this.globalName + '.save(true, ' + engToBoolean(saveAndContinue) + ', ' + engToBoolean(createAnother) + ');"/>' +
			'<input type="button" class="submit" value="' + this.tuix.confirm.cancel_button_message + '"/>';
		
		zenarioA.floatingBox(message, buttons, this.tuix.confirm.message_type || 'none');
	}
};





methods.dragDropTarget = function() {
	return this.get('zenario_fbAdminInner');
};

methods.enableDragDropUpload = function() {
	
	var el = this.dragDropTarget();
	
	zenarioT.setHTML5UploadFromDragDrop(
		this.ajaxURL(),
		{
			fileUpload: 1
		},
		false,
		this.uploadCallback,
		el
	);
		
	$(el).addClass('upload_enabled').removeClass('dragover');
};

methods.disableDragDropUpload = function() {
	$(this.dragDropTarget()).removeClass('upload_enabled').removeClass('dragover').off('drop');
};

methods.dragListeners = function() {
	return zenarioT.canDoHTML5Upload()? 'ondragover="$(this).addClass(\'dragover\');" ondragleave="$(this).removeClass(\'dragover\');"' : '';
};







methods.stopPoking = function() {
	if (this.poking) {
		clearInterval(this.poking);
	}
	this.poking = false;
};

methods.startPoking = function() {
	if (!this.poking) {
		this.poking = setInterval(this.poke, 2 * 60 * 1000);
	}
};

methods.poke = function() {
	zenario.ajax(URLBasePath + 'zenario/admin/quick_ajax.php?keep_session_alive=1')
};




}, zenarioAF, zenarioABToolkit);