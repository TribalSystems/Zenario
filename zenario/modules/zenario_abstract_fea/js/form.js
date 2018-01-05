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




zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioT, zenarioAB, zenarioAT, zenarioO,
	encodeURIComponent, defined, engToBoolean, get, htmlspecialchars, jsEscape, phrase,
	extensionOf, methodsOf, has,
	getContainerIdFromEl
) {
	"use strict";



	var zenarioFEA = createZenarioLibrary('FEA', zenarioF),
		methods = methodsOf(zenarioFEA);


methods.idVarName = function() {
	return 'id';
};




//Extend the parent function validateFormatOrRedrawForField() and add the
//option to have a save button
methods.validateFormatOrRedrawForField = function(field) {
	
	field = this.field(field);
	
	if (engToBoolean(field.save_onchange)) {
		if (this.ffoving < 4) {
			this.ffoving = 4;
			this.save();
		}
		return true;
	} else {
		return methodsOf(zenarioF).validateFormatOrRedrawForField.apply(this, arguments);
	}
};



methods.fill = function() {
	return this.ffov('fill');
};
methods.format = function() {
	return this.ffov('format');
};
methods.validate = function() {
	return this.ffov('validate');
};
methods.save = function() {
	return this.ffov('save');
};

methods.setData = function(data) {
	this.setDataDiff(data);
};

methods.sendStateToServer = function() {
	return this.sendStateToServerDiff();
};

methods.visitorTUIXLink = function(requests, mode, useSync) {
	if (this.noPlugin) {
		return zenario.visitorTUIXLink(this.moduleClassName, this.path, requests, mode, useSync);
	} else {
		return zenario.pluginVisitorTUIXLink(this.moduleClassName, this.containerId, this.path, requests, mode, useSync);
	}
};


methods.ffov = function(action) {
	
	var that = this,
		cb = new zenario.callback,
		url = this.url = this.visitorTUIXLink(this.request, action, true),
		post = false,
		goneToURL = false;
	
	if (action != 'fill') {
		this.prevPath = this.path;
		this.checkValues();
		post = {_format: true, _tuix: this.sendStateToServer()};
	}
	
	if (!this.loading) {
		this.showLoader();
		
		var after = function(tuix) {
			that.hideLoader();
		
			if (action == 'fill') {
				that.tuix = tuix;
			} else {
				that.setData(tuix);
			}
			
			if (that.tuix.reload_parent) {
				that.reloadParent();
			}
		
			if (that.tuix.go) {
				that.go(that.tuix.go);
			
			} else if (that.tuix.go_to_url && !goneToURL) {
				zenario.goToURL(that.tuix.go_to_url);
				goneToURL = true;
				
				//Set a timeout to re-liven the form, just in case the URL was a redirect to a download which wouldn't
				//unload the current window.
				setTimeout(function() {
					after(tuix);
				}, 350);
			
			} else if (that.tuix.close_popout && that.inPopout) {
				that.closePopout();
			
			} else {
				that.sortOutTUIX();
				that.draw();
				cb.call();
			}
		};
		
		this.ajax(url, post, true).after(after);
	}
	
	return cb;
};

methods.draw = function() {
	this.draw2();
	
	//If the user presses the "close" button on the conductor,
	//and the confirm_on_close tag is set, warn the user.
	if (this.tuix.confirm_on_close) {
		var that = this;
		
		zenario_conductor.confirmOnClose(
			that.containerId,
			function() {
				return !that.hidden(undefined, that.tuix.confirm_on_close);
			},
			function(after) {
				that.confirm(that.tuix.confirm_on_close, after);
			},
			that.tuix.confirm_on_close.message
		);
	}
};
methods.redrawTab = function() {
	this.draw2();
	this.hideLoader(true);
};
methods.draw2 = function() {
	this.sortTabs();
	
	this.tuix.form_title = this.getTitle();
	
	this.cb = new zenario.callback;
	this.putHTMLOnPage(this.drawFields(this.cb, this.mtPrefix + '_form'));
	
	var DOMlastFieldInFocus;
	
	if (this.path == this.prevPath
	 && this.lastFocus
	 && (DOMlastFieldInFocus = this.get(this.lastFocus.id))) {
		DOMlastFieldInFocus.focus();
	}
};

methods.ajaxURL = function() {
	return zenario.pluginAJAXLink(undefined, this.containerId, {path: this.path});
};





methods.putHTMLOnPage = function(html) {
	
	this.clearOnScrollForItemButtons();
	
	var containerId = this.containerId,
		sel = 'fea_' + containerId,
		$fea;
	
	$fea = $('#' + sel);
	if (!$fea.length) {
		sel = containerId;
		$fea = $('#' + sel);
	}
	
	$fea.html(html);
	
	this.cb.call();
	this.addJQueryElements('#' + sel);
	
	if (zenario.adminId) {
		zenarioA.scanHyperlinksAndDisplayStatus(sel);
	}
};

methods.after = function(fun) {
	this.cb.after(fun);
};

methods.closePopout = function() {
	if (this.inPopout) {
		var outerWrap = 'outer_' + this.containerId;
		
		if (get(outerWrap)) {
			$(get(outerWrap)).remove();
		}
	}
};

methods.reloadParent = function() {
	if (this.parent) {
		if (typeof this.parent == 'string') {
			zenario.refreshSlot(thus.parent);
		} else {
			this.parent.reload();
		}
	}
};

methods.hasSearch = function() {
	return defined(this.request.search) && this.request.search != '';
};

methods.showClearSearchButton = function() {
	return this.hasSearch() && (zenario.browserIsFirefox() || zenario.browserIsIE(9));
};

methods.fun = function(functionName) {
	return this.globalName + '.' + functionName;
};

methods.pMicroTemplate = function(template, data, filter) {
	return this.microTemplate(this.mtPrefix + '_' + template, data, filter);
};

methods.microTemplate = function(template, data, filter) {
	
	var d,
		html,
		addAndRemoveThis = true,
		cusTemplate,
		cusTemplateApplied;
	
	//Use a customised microtemplate for listing classes and methods
	if (cusTemplateApplied = (
			!this.__cusTemplateApplied
		 && (template == 'fea_list' || template == 'fea_form')
		 && (cusTemplate = this.tuix && this.tuix.microtemplate)
		 && (zenario.microTemplates[cusTemplate])
	)) {
		template = this.tuix.microtemplate;
		
		//Added a catch top stop infinite loops
		this.__cusTemplateApplied = true;
	}
	
	filter = zenarioT.filter(filter);
	
	if (_.isArray(data)) {
		for (d in data) {
			data[d].lib = this;
			data[d].that = this;
			if (!data[d].tuix) data[d].tuix = this.tuix;
		}
	} else {
		if (!defined(data.lib)) {
			data.lib = this;
			data.that = this;
			if (!data.tuix) data.tuix = this.tuix;
		} else {
			addAndRemoveThis = false;
		}
	}
	
	html = zenario.microTemplate(template, data, filter);
	
	if (addAndRemoveThis) {
		setTimeout(function() {
			if (_.isArray(data)) {
				for (d in data) {
					delete data[d].lib;
					delete data[d].that;
					delete data[d].tuix;
				}
			} else {
				delete data.lib;
				delete data.that;
				delete data.tuix;
			}
		}, 1);
	}
	
	//Remove the catch for infinite loops
	if (cusTemplateApplied) {
		delete this.__cusTemplateApplied;
	}
	
	return html;
};

methods.on = function(eventName, handler) {
	zenario.on(false, this.containerId, eventName, handler);
};

methods.off = function(eventName) {
	zenario.off(false, this.containerId, eventName);
};

methods.showLoader = function(hide, wasRedraw) {
	var loadingId = 'loader_for_' + this.containerId,
		container = this.get(this.containerId),
		loader = this.get(loadingId),
		$container = $(container),
		$loader;
	
	if (hide) {
		this.loading = false;
		$container.removeClass('fea_loading').addClass('fea_loaded').addClass('fea_initial_load_done');
	} else {
		this.loading = true;
		$container.removeClass('fea_loaded').addClass('fea_loading');
	}
	
	if (wasRedraw) {
		$container.removeClass('fea_just_loaded').addClass('fea_just_redrawn');
	} else {
		$container.removeClass('fea_just_redrawn').addClass('fea_just_loaded');
	}
	
	if (loader) {
		$loader = $(loader);
	
	} else if (hide) {
		return;
		
	} else {
		$loader = $(this.microTemplate(this.mtPrefix + '_loading', {loadingId: loadingId}));
		$container.prepend($loader);
	}
	
	if (hide) {
		$loader.hide();
	} else {
		$loader.width($container.width()).height($container.height()).show();
	}
};
methods.hideLoader = function(wasRedraw) {
	this.showLoader(true, wasRedraw);
};




methods.reload = function(callWhenLoaded) {
	if (this.containerId) {
		this.runLogic(this.request, callWhenLoaded || (function() {}));
	}
};

methods.commandEnabled = function(commandName) {
	return zenario_conductor.commandEnabled(this.containerId, commandName);
};

methods.navigationEnabled = function(commandName, mode) {
	return this.tuix.enable && this.tuix.enable[mode || commandName] && zenario_conductor.commandEnabled(this.containerId, commandName);
};

methods.runLogic = function(request, callWhenLoaded) {
	
	this.request = request;
	
	this.logic(request, callWhenLoaded);
};

methods.typeOfLogic = function() {
	return this.guessTypeOfLogic();
};

methods.guessTypeOfLogic = function() {
	if (this.tuix
	 && this.tuix.fea_paths
	 && !this.tuix.fea_paths[this.request.path]) {
		return 'normal_plugin';
	
	} else if (this.mode.match(/list/)) {
		return 'list';
	
	} else {
		return 'form';
	}
};

methods.logic = function(request, callWhenLoaded) {
	switch (this.typeOfLogic()) {
		case 'list':
			this.showList(callWhenLoaded);
			break;
		
		case 'form':
			this.showForm(callWhenLoaded);
			break;
		
		case 'normal_plugin':
			zenario.refreshPluginSlot(this.containerId, 'lookup', request);
			break;
		
		case 'normal_plugin_using_post':
			zenario.refreshPluginSlot(this.containerId, 'lookup', false,false,false,false,false, request);
			break;
	}
};


methods.loadFromScriptCallback = function(request, tuix) {
	
	this.request = request;
	this.tuix = tuix;
	
	this.last = {request: request};
	this.prevPath = this.path;
	
	//setTimeout() is used as a hack to ensure the conductor is fully loaded first
	var that = this;
	
	$(document).ready(function() {
		switch (that.typeOfLogic()) {
			case 'list':
				that.url = that.visitorTUIXLink(that.request);
				that.drawList();
				that.hideLoader();
				break;
		
			case 'form':
				that.url = that.visitorTUIXLink(that.request, 'fill', true)
				that.sortOutTUIX();
				that.draw();
		}
	});
};


methods.showForm = function(callWhenLoaded) {
	this.changed = {};
	this.fill().after(callWhenLoaded);
};


methods.showList = function(callWhenLoaded) {
	if (this.loading) {
		return;
	}
	
	var that = this,
		redrawn = false,
		url = this.url = this.visitorTUIXLink(this.request);
	
	this.showLoader();
	this.ajax(url, false, true).after(function(tuix) {
	
		that.tuix = tuix;
		
		that.drawList();
		that.hideLoader(redrawn);
		callWhenLoaded();
		
	});
};




methods.drawList = function() {
	this.hadSparkline = false;
	
	this.sortOutTUIX();
	
	this.cb = new zenario.callback;
	this.putHTMLOnPage(this.microTemplate(this.mtPrefix + '_list', {}));
	
	var that = this,
		page = 1 * this.tuix.__page__,
		pageSize = 1 * this.tuix.__page_size__,
		itemCount = 1 * this.tuix.__item_count__,
		paginationId;
	
	if (page
	 && pageSize
	 && itemCount
	 && itemCount > pageSize) {
		
		paginationId = '#pagination_' + this.containerId;
		
		$(paginationId).show().jPaginator({ 
			nbPages: Math.ceil(itemCount / pageSize), 
			selectedPage: page,
			overBtnLeft: paginationId + '_o_left', 
			overBtnRight: paginationId + '_o_right', 
			maxBtnLeft: paginationId + '_m_left', 
			maxBtnRight: paginationId + '_m_right',
			
			//withSlider: true,
			//minSlidesForSlider: 2,
			//
			//withAcceleration: true,
			//speed: 2,
			//coeffAcceleration: 2,
			
			onPageClicked: function(a,num) { 
				that.doSearch(undefined, undefined, num);
			}
		});
	}
	

	
	//call sparkline
	if (this.hadSparkline) {
		this.initSparklineChart();
	}

};




methods.getPathFromMode = function(mode) {
	//N.b. "create" is just an alias for "edit" to give a nicer URL
	return 'zenario_' + mode;
};
methods.getModeFromPath = function(path) {
	return path.replace(/^zenario_/, '');
};


methods.recordRequestsInURL = function(request) {
	zenario.recordRequestsInURL(this.containerId, this.checkRequests(request, true));
};

methods.checkRequests = function(request, forDisplay, itemId, merge, keepClutter) {
	
	var key, value, idVarName;
	
	
	request = zenario.clone(request, merge);
	
	
	idVarName = this.idVarName(this.mode) || 'id';
	
	//Automatically add everything that's defined in the key
	if (this.tuix
	 && this.tuix.key) {
		foreach (this.tuix.key as key => value) {
			
			//Catch the case where the idVarName is not "id",
			//but "id" was used in the code!
			if (key == 'id'
			 && idVarName != 'id'
			 && !defined(this.tuix.key[idVarName])) {
				key = idVarName;
			}
			
			if (!defined(request[key])) {
				request[key] = value;
			}
		}
	}
	
	if (itemId) {
		request[idVarName] = itemId;
	} else if (keepClutter && !defined(request[idVarName])) {
		request[idVarName] = '';
	}
	
	foreach (request as key => value) {
		//For item buttons, have the ability to insert values from that item
		if (_.isObject(value)) {
			value =
			request[key] = (
				value.replace_with_field_from_item
				 && itemId
				 && this.tuix.items
				 && this.tuix.items[itemId]
			)?
				this.tuix.items[itemId][value.replace_with_field_from_item] : '';
		}
		
		//Remove any empty values to avoid clutter
		if (!keepClutter) {
			if (_.isEmpty(value) || value === '0') {
				delete request[key];
			}
		}
	}
	
	if (forDisplay) {
		//Don't show the path in the URL
		delete request.path;
		
		//Don't show the name of the default mode in the URL
		if (request.mode == this.defaultMode) {
			delete request.mode;
		}
	}
	
	return request;
};

methods.init = function(globalName, microtemplatePrefix, moduleClassName, containerId, path, request, mode, pages, noPlugin, parent, inPopout, popoutClass) {
	
	methodsOf(zenarioF).init.call(this, globalName, microtemplatePrefix, containerId);
	
	this.last = {};
	this.pages = pages || {};
	this.mode = mode;
	this.path = path;
	this.prevPath = '';
	this.moduleClassName = moduleClassName;
	this.containerId = containerId;
	this.noPlugin = noPlugin;
	this.parent = parent;
	this.inPopout = inPopout;
	this.popoutClass = popoutClass = popoutClass || '';
	
	
	if (inPopout) {
		this.closePopout();
		
		$('body').append(
			zenarioT.div(
				'id', 'outer_' + containerId,
				'class', 'zfea_popout ' + popoutClass,
					zenarioT.div(
						'id', containerId,
						'class', 'zfea_popout_inner'
					)
			)
		);
	}

	
	
	//I'm experiementing with making the first AJAX request of a FEA plugin load via a script tag to speed up the initial page load.
	//If the request is set to -1, that means we'll do the first load using a script tag then later call the loadFromScriptCallback() method.
	if (request !== -1) {
		this.go(request, undefined, true);
	}
	
	
	//Error phrases
	//Currently I've just copied them from admin mode then hardcoded them, and have not given any thought to translating them
	this.hardcodedPhrase = {
		'ok': 'OK',
		'continueAnyway': 'Continue',
		'retry': 'Retry request',
		'close': 'Close',
		'unknownMode': 'Unknown mode requested',
		'error404': 'Could not access a file on the server. Please check that you have uploaded all of the CMS files to the server, and that you have no misconfigured rewrite rules in your Apache config or .htaccess file that might cause a 404 error.',
		'error500': "Something on the server is incorrectly set up or misconfigured.",
		'errorTimedOut': "There was no reply or a blank reply from the server.\n\nThis could be a temporary network problem, or could be a bug in the application."
	};
};

methods.editModeOn = function(tab) {
	return true;
};

methods.editModeAlwaysOn = function(tab) {
	return true;
};


methods.lookupFileDetails = function(fileId) {
	return false;
};


methods.debug = function() {
	if (this.path
	 && this.tuix
	 && this.url) {
		zenarioA.debug(this.globalName);
	}
};

methods.doSearch = function(e, value, page) {
	
	zenario.stop(e);
	var search = this.get('search_' + this.containerId);
	var requests = this.request,
		page = page ? page : '',
		search = {
			page: page,
			search: defined(value)? value : (search ? search.value: false)
		};

	this.go(this.checkRequests(requests, false, undefined, search, true));
	
	return false;
};


methods.go = function(request, itemId, wasInitialLoad) {
	request = request || {};
	
	var page,
		that = this,
		containerId = this.containerId,
		command = zenario_conductor.commandEnabled(containerId, request.command);
	
	//Remove any existing signal handlers that we might have added
	this.off();
	
	delete request.command;
	
	request = this.checkRequests(request, false, itemId, undefined, true);
	this.last = {request: request};
	this.prevPath = this.path;


	if (command) {
		delete request.path;
		delete request.mode;
		zenario_conductor.go(containerId, command, request);
	
	//Check if the link should be directed to a different page
	} else
	if (!wasInitialLoad
	 && request.mode
	 && request.mode != this.mode
	 && (page = this.pages[request.mode])
	 && (page.cID != zenario.cID || page.cType != zenario.cType)) {
		
		delete request.mode;
		zenario.goToURL(zenario.linkToItem(page.cID, page.cType, request));
	
	} else {
		zenario_conductor.mergeRequests(containerId, request);
		
		this.runLogic(request, function() {
			if (request.page) {
				zenario.scrollToSlotTop(that.containerId, true);
			}
			if (!wasInitialLoad) {
				that.recordRequestsInURL(request);
			}
		});
	}
};


methods.itemButtonIsntHidden = function(button, itemIds, isCheckboxSelect) {
	
	var i, item,
		met = false,
		maxItems = 1, 
		minItems = 0,
		numItems = isCheckboxSelect? itemIds.length : 0;
	
	//Check all of the itemIds in the request actually exist
	for (i in itemIds) {
		if (!(item = this.tuix.items[itemIds[i]])) {
			return false;
		}
	}
	
	//Do the standard checks if something is hidden
	if (this.hidden(undefined, item, button.id, button)) {
		return false;
	}
	
	//Check the min/max rules for the number of selected items
	if (engToBoolean(button.multiple_select)) {
		
		//Remember if we see a visible multi-select button
		if (!button.hide_when_children_are_not_visible) {
			this.multiSelectButtonsExist = true;
		}
		
		maxItems = button.multiple_select_max_items;
		
		if (engToBoolean(button.multiple_select_only)) {
			minItems = 2;
		}
		
		if (button.multiple_select_min_items) {
			minItems = Math.max(minItems, button.multiple_select_min_items);
		}
	}
	
	//If there are too many/too few selected items, don't show this button
	if (numItems < minItems
	 || (maxItems && numItems > maxItems)) {
		return false;
	}
	
	if (defined(button.visible_if_for_all_selected_items)) {
		for (i in itemIds) {
			item = this.tuix.items[itemIds[i]];
			
			if (!zenarioT.eval(button.visible_if_for_all_selected_items, this, undefined, item, button.id, button)) {
				return false;
			}
		}
	}
	
	if (defined(button.visible_if_for_any_selected_items)) {
		for (i in itemIds) {
			item = this.tuix.items[itemIds[i]];
			
			if (zenarioT.eval(button.visible_if_for_any_selected_items, this, undefined, item, button.id, button)) {
				met = true;
				break;
			}
		}
		
		return met;
	}
	
	return true;
};


//Check to see whether a button should be disabled
methods.buttonIsntDisabled = function(button, itemIds) {
	
	//Run all of the checks to see if a button is disabled
	doLoop:
	do {
		if (engToBoolean(button.disabled)) {
			break;
		}
	
		if (defined(button.disabled_if)) {
			if (zenarioT.eval(button.disabled_if, this, undefined, item, button.id, button)) {
				break;
			}
		}
	
		var i, item;
	
		//Check whether an item button with the disabled_if_for_any_selected_items/disabled_if_for_all_selected_items
		//properties should be visible
		if (defined(itemIds)
		 && defined(button.disabled_if_for_any_selected_items)) {
		
			for (i in itemIds) {
				item = this.tuix.items[itemIds[i]];
			
				if (zenarioT.eval(button.disabled_if_for_any_selected_items, this, undefined, item, button.id, button)) {
					break doLoop;
				}
			}
		}
	
		if (defined(itemIds)
		 && defined(button.disabled_if_for_all_selected_items)) {
		
			for (i in itemIds) {
				item = this.tuix.items[itemIds[i]];
			
				if (!zenarioT.eval(button.disabled_if_for_all_selected_items, this, undefined, item, button.id, button)) {
					return true;
				}
			}
		
			break;
		}
	
		return true;
	} while (false);
	
	//If it is disabled, flag it as such and change to the disabled-tooltip
	button._isDisabled = true;
	button.tooltip = button.disabled_tooltip || button.tooltip;
	
	return false;
};


methods.hidden = function(tuixObject, item, id, button, column, field, section, tab) {
	
	tuixObject = tuixObject || button || column || field || item || section || tab;
	
	if (tuixObject.hide_with_search_bar
	 && this.tuix.hide_search_bar) {
		return true;
	}
	
	//Check if this button mentions the conductor
	if (button
	 && button.go
	 && button.go.command) {
		
		//If so, check if this command is enabled and hide it if not.
		if (zenario_conductor.enabled(this.containerId)) {
			if (!zenario_conductor.commandEnabled(this.containerId, button.go.command)) {
				return true;
			}
		
		//If not, check if there is any fullback functionality and hide it if not
		} else if (!button.go.mode) {
			return true;
		}
	}
	
	//zenarioT.hidden = function(tuixObject, lib, item, id, button, column, field, section, tab) {
	return zenarioT.hidden(tuixObject, this, item, id, button, column, field, section, tab);
};


methods.sortOutTUIX = function() {
	
	var tuix = this.tuix;
	
	this.newlyNavigated = this.path != this.prevPath;
	this.multiSelectButtonsExist = false;
	
	tuix.collection_buttons = tuix.collection_buttons || {};
	tuix.item_buttons = tuix.item_buttons || {};
	tuix.columns = tuix.columns || {};
	tuix.items = tuix.items || {};
	
	var i, id, j, itemButton, childItemButton, col, item, button, sortedItemIds,
		sortBy = tuix.sort_by || 'name';
		sortDesc = engToBoolean(tuix.sort_desc);
	
	tuix.sortedCollectionButtonIds = zenarioT.getSortedIdsOfTUIXElements(tuix, tuix.collection_buttons);
	tuix.sortedCollectionButtons = [];
	tuix.sortedItemButtonIds = zenarioT.getSortedIdsOfTUIXElements(tuix, tuix.item_buttons);
	tuix.sortedItemButtons = [];
	tuix.sortedColumnIds = zenarioT.getSortedIdsOfTUIXElements(tuix, tuix.columns);
	tuix.sortedColumns = [];
	
	foreach (tuix.sortedCollectionButtonIds as i => id) {
		button = _.clone(tuix.collection_buttons[id]);
		button.id = id;
		
		if (!this.hidden(undefined, undefined, id, button)) {
			if (this.buttonIsntDisabled(button)) {
				this.setupButtonLinks(button);
			}
			
			tuix.sortedCollectionButtons.push(button);
		}
	}
	
	foreach (tuix.sortedItemButtonIds as i => id) {
		button = tuix.item_buttons[id];
		button.id = id;
		
		tuix.sortedItemButtons.push(button);
	}
	
	foreach (tuix.sortedColumnIds as i => id) {
		col = tuix.columns[id];
		col.id = id;
		
		if (!this.hidden(undefined, undefined, id, undefined, col)) {
			tuix.sortedColumns.push(col);
		}
	}
	
	zenarioT.setKin(tuix.sortedColumns);
	zenarioT.setKin(tuix.sortedCollectionButtons, 'zfea_button_with_children');
	zenarioT.setKin(tuix.sortedItemButtons, 'zfea_button_with_children');
	
	
	if (tuix.__item_sort_order__) {
		sortedItemIds = tuix.__item_sort_order__;
	} else {
		sortedItemIds = zenarioT.getSortedIdsOfTUIXElements(tuix, 'items', sortBy, sortDesc);
	}
	
	i = -1;
	tuix.sortedItems = [];
	
	//Fix a bug where PHP converts an array to an object, and JavaScript
	//doesn't preserve the correct order, by specifically looping through numerically
	while (undefined !== (id = sortedItemIds[++i])) {
		item = tuix.items[id];
		item.id = id;
		
		tuix.sortedItems.push(item);
		
		item.sortedItemButtons = this.getSortedItemButtons([id], false);
	}
	
	this.last.tuix = tuix;
};

//Get a list of item buttons, depending on the item(s) that they were for
methods.getSortedItemButtons = function(itemIds, isCheckboxSelect) {
		
	var j, itemButton,
		k, childItemButton,
		button, children, childButton,
		sortedButtons = [],
		itemId, itemIdsCSV;
	
	if (isCheckboxSelect) {
		itemIdsCSV = itemIds.join(',');
	} else {
		itemIdsCSV = itemId = itemIds[0];
	}
	
	foreach (this.tuix.sortedItemButtons as j => itemButton) {
		button = _.clone(itemButton);
		button.itemId = itemId;
		button.itemIds = itemIdsCSV;
		
		if (this.itemButtonIsntHidden(button, itemIds, isCheckboxSelect)) {
			
			if (this.buttonIsntDisabled(button, itemIds)) {
				this.setupButtonLinks(button, itemIdsCSV);
			}
			
			if (button.children) {
				children = button.children;
				button.children = [];
			
				foreach (children as k => childItemButton) {
					childButton = _.clone(childItemButton);
					childButton.itemId = itemId;
					childButton.itemIds = itemIdsCSV;
				
					if (this.itemButtonIsntHidden(childButton, itemIds, isCheckboxSelect)) {
						
						if (this.buttonIsntDisabled(childButton, itemIds)) {
							this.setupButtonLinks(childButton, itemIdsCSV);
						}
						
						button.children.push(childButton);
					}
				}
			}
		
			if (!button.hide_when_children_are_not_visible || (button.children && button.children.length > 0)) {
				this.tuix.itemHasItemButton = true;
				sortedButtons.push(button);
			}
		}
	}
	
	return sortedButtons;
};

methods.setupButtonLinks = function(button, itemId) {
	
	var page,
		request,
		onclick,
		onPrefix,
		command;
	
	if (button.go
	 || button.ajax
	 || button.onclick
	 || button.confirm) {
		
		onPrefix = this.defineLibVarBeforeCode();
		
		if (!button.onclick
		 || !button.onclick.startsWith(onPrefix)) {
			
			onclick = onPrefix;
			
			if (defined(itemId)) {
				onclick += "var button = (lib.tuix.item_buttons||{})['" + jsEscape(button.id) + "'],"
						+ "itemId = '" + jsEscape(itemId) + "',"
						+ "item = (lib.tuix.items||{})[itemId];";
			} else {
				onclick += "var button = (lib.tuix.collection_buttons||{})['" + jsEscape(button.id) + "'],"
						+ "itemId,"
						+ "item;";
			}
			
			onclick += "lib.button(this, button, item, itemId";
		
			if (button.go) {
				request = this.checkRequests(button.go, true, itemId);
			}
		
			if (button.onclick) {
				onclick += ", function() {" + button.onclick + "}";
			}
		
			onclick += "); return false;";
		
			button.onclick = onclick;
		}
	}
	
	//Check if this button has a "go" link
	if (!defined(button.href)) {
		if (request) {
			command = zenario_conductor.commandEnabled(this.containerId, request.command);
			delete request.command;
			
			if (command) {
				button.href = zenario_conductor.link(this.containerId, command, request);
			
			//Check if the link should be directed to a different page. If so, just include a href and don't set an onclick
			} else
			if (request.mode
			 && request.mode != this.mode
			 && (page = this.pages[request.mode])
			 && (page.cID != zenario.cID || page.cType != zenario.cType)) {
			
				delete request.mode;
				button.href = zenario.linkToItem(page.cID, page.cType, request);
				return;
		
			} else {
				button.href = zenario.linkToItem(zenario.cID, zenario.cType, request);
			}
		}
	} else if (button.href.replace_with_field_from_item) {
		button.href = (
			 itemId
			 && this.tuix.items
			 && this.tuix.items[itemId]
		)?
			this.tuix.items[itemId][button.href.replace_with_field_from_item] : '';
	}
};

//Submit/toggle button presses on forms
methods.clickButton = function(el, id) {
	
	var that = this,
		button = this.field(id),
		clickButton = methodsOf(zenarioF).clickButton;
	
	if (button.confirm
	 && !this.hidden(button.confirm, undefined, id, button)) {
		this.confirm(
			button.confirm,
			function () {
				clickButton.call(that, el, id);
			}
		);
		
	} else {
		clickButton.call(this, el, id);
	}
};

//Collection/item button presses on lists
methods.button = function(el, button, item, itemId, onclickFun, confirmed) {
	if (this.loading) {
		return;
	}
	
	var that = this,
		getMergeField,
		request,
		isDelete,
		confirm,
		funReturn,
		itemIds,
		numItems = 0;
	
	if (defined(itemId)) {
		itemIds = itemId.split(',');
		numItems = itemIds.length;
	}
	
	if (!confirmed
	 && (confirm =
	 		button.confirm
	 	|| (button.go && button.go.confirm)
	 	|| (button.ajax && button.ajax.confirm))
	 && (!this.hidden(confirm, item, button.id, button))) {
		
		//For item buttons, modify the confirm message to include details on the item(s) selected
		if (defined(itemId)) {
			confirm = _.extend({}, confirm);
			
			if (numItems === 1) {
				confirm.title = zenario.applyMergeFields(confirm.title, item);
				confirm.message = zenario.applyMergeFields(confirm.message, item);
			} else {
				
				getMergeField = function(mrg, options) {
					
					if (options == 'item_count') {
						return numItems;
					
					} else {
						options = options.split('|');
						
						var i,
							item,
							itemId,
							key = options[0],
							join = options[1] || ', ',
							and = options[2],
							out = [];
						
						foreach (itemIds as i => itemId) {
							if (item = that.tuix.items[itemId]) {
								out.push(item[key]);
							}
						}
					}
					
					if (and && out.length > 1) {
						and += out.pop();
					} else {
						and = '';
					}
					
					return out.join(join) + and;
				};
				
				confirm.title = zenario.applyMergeFields(confirm.multiple_select_title || confirm.title, undefined, getMergeField);
				confirm.message = zenario.applyMergeFields(confirm.multiple_select_message || confirm.message, undefined, getMergeField);
			}
		}
		
		this.confirm(confirm, function() {
			that.button(el, button, item, itemId, onclickFun, true);
		});
	
	} else {
		
		//If the button had a regular onclick, run that
		if (onclickFun) {
			funReturn = onclickFun.call(el);
			
			//If the onclick returned false, don't continue running
			if (defined(funReturn) && !funReturn) {
				return;
			}
		}
		
		if (button.ajax) {
			request = this.checkRequests(button.ajax.request, false, itemId, undefined, true);
			
			this.runAJAXRequest(request, button.go || this.checkRequests(this.last.request, true), button.ajax, itemId);

		} else {
			this.go(button.go, itemId);
		}
	}
	
};


methods.checkAllCheckboxes = function(cbEl) {
	$('#' + this.containerId + ' input.zfea_check_item').each(function(i, el) {
		el.checked = cbEl.checked;
	});
	
	this.updateItemButtons();
};

methods.clearOnScrollForItemButtons = function() {
	if (this.updateItemButtonPositionOnScroll) {
		$(window).off('scroll', this.updateItemButtonPositionOnScroll);
	}
};

methods.updateItemButtons = function() {
	
	this.clearOnScrollForItemButtons();
	
	var prefix = '#multi_select_buttons_',
		containerId = this.containerId,
		checkAllCheckbox = get('zfea_check_all_' + containerId),
		$trs = $('#' + containerId + ' tr.zfea_row'),
		$oldTds = $('#' + containerId + ' td.single_select_buttons'),
		$td = $(prefix + 'td_' + containerId),
		$div = $(prefix + containerId),
		$allCheckboxes = $('#' + containerId + ' td.zfea_check_item input'),
		$tickedCheckboxes = $allCheckboxes.filter('input:checked'),
		$highestTickedCheckbox = $tickedCheckboxes.first(),
		$lowestTickedCheckbox = $tickedCheckboxes.last(),
		numberChecked = $tickedCheckboxes.length,
		highestCheckboxHeight,
		lowestCheckboxHeight,
		offsetTop, offsetBottom,
		fullWidth, largestPossibleGap, distanceFromTop,
		itemIds = [];
	
	//If no checkboxes are checked, clear the multi-select buttons and show the regular buttons
	if (!numberChecked) {
		$td.hide();
		$oldTds.show();
		checkAllCheckbox.checked = false;
		
		//Loop through each row, removing the height hack
		$trs.each(function(i, el) {
			var $el = $(el);
			$el.height('');
		});
		
	} else {
		
		//Loop through each row, fixing the height to stop the table moving around
		$trs.each(function(i, el) {
			var $el = $(el);
			$el.height($el.height());
		});
		
		$tickedCheckboxes.each(function(i, el) {
			var $el = $(el);
			itemIds.push($el.data('item_id'));
		});
		
		
		
		$div.html(this.microTemplate(this.mtPrefix + '_button', this.getSortedItemButtons(itemIds, true)));
		zenario.addJQueryElements(prefix + containerId + ' ');
		
		
	
	
		$td.show();
		$oldTds.hide();
		checkAllCheckbox.checked = numberChecked == $allCheckboxes.length;
	
		highestCheckboxHeight = $highestTickedCheckbox.offset().top;
		lowestCheckboxHeight = $lowestTickedCheckbox.offset().top;
	
		fullWidth = $(window).width() / 2;
		largestPossibleGap = $td.height() - $div.outerHeight(true);
		distanceFromTop = $td.offset().top;
	
	
	
		offsetTop = highestCheckboxHeight - distanceFromTop;
		offsetBottom = lowestCheckboxHeight - distanceFromTop;
	
		if (offsetTop < 0) {
			offsetTop = 0;
		}
		if (offsetBottom > largestPossibleGap) {
			offsetBottom = largestPossibleGap;
		}
	
	
		if (offsetBottom > 0) {
			//Look for full-width position: fixed divs and subtract them from the distance to the top
				//N.b. the ":not(.ui-helper-hidden-accessible)" part of the selector is to skip the many junk tags added by jQuery tooltips
			$('body > div:visible:not(.ui-helper-hidden-accessible)').each(function(i, el) {
				var $el = $(el);
		
				if ($el.css('position') == 'fixed'
				 && $el.css('top').match(/^0/)
				 && $el.width() > fullWidth) {
					distanceFromTop -= $el.height();
				}
			});
			
			this.updateItemButtonPositionOnScroll = function(event) {
			
				var moveDivDownBy = zenario.scrollTop() - distanceFromTop;
		
				if (moveDivDownBy < offsetTop) {
					moveDivDownBy = offsetTop;
		
				} else
				if (moveDivDownBy > offsetBottom) {
					moveDivDownBy = offsetBottom;
				}
		
				$div.css('margin-top', Math.round(moveDivDownBy));
			};
			
			this.updateItemButtonPositionOnScroll();
			
			if (numberChecked > 1) {
				$(window).on('scroll', this.updateItemButtonPositionOnScroll);
			}
		}
	}
};










//Sparkline
methods.sparkline = function() {
	/**
	 * Create a constructor for sparklines that takes some sensible defaults and merges in the individual
	 * chart options. This function is also available from the jQuery plugin as $(element).highcharts('SparkLine').
	 */
	Highcharts.SparkLine = function (a, b, c) {
		var hasRenderToArg = typeof a === 'string' || a.nodeName,
			options = arguments[hasRenderToArg ? 1 : 0],
			defaultOptions = {
				chart: {
					renderTo: (options.chart && options.chart.renderTo) || this,
					backgroundColor: null,
					borderWidth: 0,
					type: 'area',
					margin: [2, 0, 2, 0],
					width: 120,
					height: 40,
					style: {
						overflow: 'visible'
					},
					skipClone: true
				},
				title: {
					text: ''
				},
				credits: {
					enabled: false
				},
				xAxis: {
					labels: {
						enabled: false
					},
					title: {
						text: null
					},
					startOnTick: false,
					endOnTick: false,
					tickPositions: []
				},
				yAxis: {
					endOnTick: false,
					startOnTick: false,
					labels: {
						enabled: false
					},
					title: {
						text: null
					},
					tickPositions: [0]
				},
				legend: {
					enabled: false
				},
				/*tooltip: { 
					enabled: true 
				},*/
				tooltip: {
					backgroundColor: null,
					borderWidth: 0,
					shadow: false,
					useHTML: true,
					hideDelay: 0,
					shared: true,
					padding: 0,
					positioner: function (w, h, point) {
						return { x: point.plotX - w / 2, y: point.plotY - h };
					}
				},
				plotOptions: {
					series: {
						animation: false,
						lineWidth: 1,
						shadow: false,
						states: {
							hover: {
								//lineWidth: 1
								enabled: false
							}
						},
						marker: {
							radius: 1,
							states: {
								hover: {
									radius: 2
								}
							}
						},
						fillOpacity: 0.25
					},
					column: {
						negativeColor: '#910000',
						borderColor: 'silver'
					}
				},
				exporting: {
					enabled: false
				}
			};

		options = Highcharts.merge(defaultOptions, options);

		return hasRenderToArg ?
			new Highcharts.Chart(a, options, c) :
			new Highcharts.Chart(options, b);
	};

	var start = +new Date(),
		$tds = $('td[data-sparkline]'),
		fullLen = $tds.length,
		n = 0;
	
	var ii, item, ci, col;
	foreach (this.tuix.sortedColumns as ci => col) {
		if (col.sparkline) {
			foreach (this.tuix.sortedItems as ii => item) {
				
				var i,
					$td = $(this.get('zfea_' + this.containerId + '_row_' + ii + '_col_' + ci)),
					data = item[col.id] || {},
					chart = {};
				//chart.type = '...';
				
				if (!data.values) {
					var columnId = 'zfea_' + this.containerId + '_row_' + ii + '_col_' + ci;
					$('#'+columnId).html(data.no_data_message);
					continue;
				}
				
				var colour;
				if(data.colour){
					colour = data.colour;
				}else{
					colour = "#82CAFF";
				}
				
				$td.highcharts('SparkLine', {
					colors: [colour],
					series: [{
						data: _.toArray(data.values),
						pointStart: 1
					}],
					tooltip: {
						headerFormat: '<span style="font-size: 10px">' + data.label+':</span><br/>',
						pointFormat: '<b>{point.y}</b> ' + data.units
					},
					chart: chart
				});
			}
		}
	}
};


methods.initSparklineChart = function() {
	var that = this;
	
	that.sparkline();
};







methods.confirm = function(confirm, after) {
	if (this.loading) {
		return;
	}
	
	if (!_.isFunction(after) && after._zenario_confirmed) {
		delete after._zenario_confirmed;
		return true;
	}
	
	var cssClassName = 'zfea_colorbox_content' + (confirm.css_class? ' ' + confirm.css_class : ''),
		
		//Attempt to work around bugs where colorbox often forgets to add the class name on to the box
		//by manually doing it ourselves
		addClassName = function() {
			var $colorbox = $('#colorbox');
			if ($colorbox.length) {
				$colorbox[0].className = cssClassName;
			}
		};
	
	$.colorbox.remove();
	$.colorbox({
		transition: 'none',
		closeButton: false,
		html: this.microTemplate(this.mtPrefix + '_confirm', confirm),
		className: cssClassName,
		onOpen: addClassName
	});
	
	addClassName();

	if (!_.isFunction(after)) {
		$('#zfea_do_it').click(function() {
			$.colorbox.remove();
			after._zenario_confirmed = true;
			$(after).click();
		});
	} else {
		$('#zfea_do_it').click(function() {
			$.colorbox.remove();
			after();
		});
	}
};


methods.runAJAXRequest = function(request, goAfter, ajax, itemId) {
	if (this.loading) {
		return;
	}
	
	
	$.colorbox.remove();
	
	var that = this,
		isDelete = ajax.is_delete,
		isDownload = ajax.download,
		reloadSlide = ajax.reload_slide;
	
	if (isDownload) {
		url = zenario.pluginAJAXLink(this.moduleClassName, this.containerId, request);
		window.location = url;
	} else {
		url = zenario.pluginAJAXLink(this.moduleClassName, this.containerId);
		this.showLoader();
		this.ajax(url, request).after(function(resp) {
			that.hideLoader();
			
			if (resp) {
				that.AJAXErrorHandler(resp);
			
			} else if (reloadSlide && zenario_conductor.refresh(zenario.getSlotnameFromEl(that.containerId))) {
				
			} else {
				that.go(goAfter, isDelete? undefined : itemId);
			}
		});
	}
};


methods.phrase = function(text, mrg) {
	
	var moduleClassNameForPhrases =
		zenario.slots[this.containerId]
	 && zenario.slots[this.containerId].moduleClassNameForPhrases;
	
	return zenario.phrase(moduleClassNameForPhrases, text, mrg);
};


methods.ajax = function(url, post, json) {
	
	var that = this,
		previewValues =
			zenario.adminId
		 && windowParent
		 && windowParent.zenarioAB
		 && windowParent.zenarioAB.previewValues;
	
	if (previewValues) {
		if (post === false
		 || !defined(post)) {
			post = {};
		}
		
		if (_.isObject(post)) {
			post.overrideSettings = previewValues;
		} else {
			post += '&overrideSettings=' + encodeURIComponent(previewValues);
		}
	}
	
	//zenario.ajax(url, post, json, useCache, retry, continueAnyway, settings, timeout, AJAXErrorHandler, onRetry, onCancel)
	return zenario.ajax(url, post, json, false, true, true, undefined, undefined,
		function(resp, statusType, statusText) {
			that.AJAXErrorHandler(resp, statusType, statusText);
		},
		function() {
			$.colorbox.remove();
		}
	);
};


//Not currently used
//If we need pickers, I plan to show a list of things in a colorbox, using this function as the URL
methods.pickerLink = function(pageName, request) {
	var page;
	
	if (page = this.pages[pageName]) {
		return this.showSingleSlotLink(page.containerId, request, false, page.cID, page.cType);
	} else {
		return false;
	}
};




methods.AJAXErrorHandler = function(resp, statusType, statusText) {
	
	var that = this,
		msg = '',
		m = {};
	
	resp = zenarioT.splitDataFromErrorMessage(resp);
	
	if (statusText) {
		msg += zenarioT.h1(htmlspecialchars(resp.status + ' ' + statusText));
	}

	if (resp.status == 404) {
		msg += zenarioT.p(this.hardcodedPhrase.error404);

	} else if (resp.status == 500) {
		msg += zenarioT.p(this.hardcodedPhrase.error500);

	} else if (resp.status == 0 || statusType == 'timeout') {
		msg += zenarioT.p(this.hardcodedPhrase.errorTimedOut);
	}

	if (resp.responseText) {
		msg += zenarioT.div(htmlspecialchars(resp.responseText));
	}
	
	
	showErrorMessage = function() {
		
		m.body = msg;
		m.retry = !!resp.zenario_retry;
		m.continueAnyway = resp.zenario_continueAnyway && resp.data;
		
		$.colorbox({
			transition: 'none',
			closeButton: false,
			html: that.microTemplate(that.mtPrefix + '_error', m)
		});
		
		if (m.retry) {
			$('#zfea_retry').click(function() {
				$.colorbox.close();
				zenario.enableScrolling('colorbox');
				
				setTimeout(resp.zenario_retry, 1);
			});
		}
		if (m.continueAnyway) {
			$('#zfea_continueAnyway').click(function() {
				$.colorbox.close();
				zenario.enableScrolling('colorbox');
				
				setTimeout(function() {
					resp.zenario_continueAnyway(resp.data);
				}, 1);
			});
		}
	}
	
	if (resp.status == 0 || statusType == 'timeout') {
		setTimeout(showErrorMessage, 750);
	} else {
		showErrorMessage();
	}
};



zenario_abstract_fea.setupAndInit = function(moduleClassName, library, containerId, path, request, mode, pages, noPlugin, parent, inPopout, popoutClass) {

	var globalName = moduleClassName + '_' + containerId.replace(/\-/g, '__');
	
	if (!window[globalName]) {
		window[globalName] = new library();
	}
	
	window[globalName].init(globalName, 'fea', moduleClassName, containerId, path, request, mode, pages, noPlugin, parent, inPopout, popoutClass);
	
	return window[globalName];
}




}, zenario.getContainerIdFromEl);