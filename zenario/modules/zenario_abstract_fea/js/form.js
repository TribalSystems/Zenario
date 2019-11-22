/*
 * Copyright (c) 2019, Tribal Limited
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
	return thus.specifiedIdVarName || 'id';
};




//Extend the parent function validateFormatOrRedrawForField() and add the
//option to have a save button
methods.validateFormatOrRedrawForField = function(field) {
	
	field = thus.field(field);
	
	if (engToBoolean(field.save_onchange)) {
		if (thus.ffoving < 4) {
			thus.ffoving = 4;
			thus.save();
		}
		return true;
	} else {
		return methodsOf(zenarioF).validateFormatOrRedrawForField.apply(thus, arguments);
	}
};



methods.fill = function() {
	return thus.ffov('fill');
};
methods.format = function() {
	return thus.ffov('format');
};
methods.validate = function() {
	return thus.ffov('validate');
};
methods.save = function() {
	return thus.ffov('save');
};

methods.setData = function(data) {
	thus.setDataDiff(data);
};

methods.sendStateToServer = function() {
	return thus.sendStateToServerDiff();
};

methods.visitorTUIXLink = function(requests, mode, useSync) {
	if (thus.noPlugin) {
		return zenario.visitorTUIXLink(thus.moduleClassName, thus.path, requests, mode, useSync);
	} else {
		return zenario.pluginVisitorTUIXLink(thus.moduleClassName, thus.containerId, thus.path, requests, mode, useSync);
	}
};


methods.ffov = function(action) {
	
	var cb = new zenario.callback,
		url = thus.url = thus.visitorTUIXLink(thus.request, action, true),
		post = false,
		goneToURL = false;
	
	if (action != 'fill') {
		thus.prevPath = thus.path;
		thus.checkValues();
		post = {_format: true, _tuix: thus.sendStateToServer()};
	}
	
	if (!thus.loading) {
		thus.showLoader();
		
		var after = function(tuix) {
			thus.hideLoader();
		
			if (action == 'fill') {
				thus.tuix = tuix;
			} else {
				thus.setData(tuix);
			}
			
			if (thus.tuix.reload_parent) {
				thus.reloadParent();
			}
			
			if (thus.tuix.js) {
				zenarioT.eval(thus.tuix.js, thus);
			}
			
		
			if (thus.tuix.go) {
				thus.go(thus.tuix.go);
			
			} else if (thus.tuix.go_to_url && !goneToURL) {
				zenario.goToURL(thus.tuix.go_to_url);
				goneToURL = true;
				
				//Set a timeout to re-liven the form, just in case the URL was a redirect to a download which wouldn't
				//unload the current window.
				setTimeout(function() {
					after(tuix);
				}, 350);
			
			} else if (thus.tuix.close_popout && thus.inPopout) {
				thus.closePopout();
			
			} else {
				thus.sortOutTUIX();
				thus.draw();
				cb.done();
				
				if (action == 'save' && thus.tuix.scroll_after_save) {
					zenario.scrollToSlotTop(thus.containerId, true);
				}
			}
		};
		
		thus.ajax(url, post, true).after(after);
	}
	
	return cb;
};

methods.draw = function() {
	thus.draw2();
	
	//If the user presses the "close" button on the conductor,
	//and the confirm_on_close tag is set, warn the user.
	if (thus.tuix.confirm_on_close) {
		
		zenario_conductor.confirmOnClose(
			thus.containerId,
			function() {
				return !thus.hidden(undefined, thus.tuix.confirm_on_close);
			},
			function(after) {
				thus.confirm(thus.tuix.confirm_on_close, after);
			},
			thus.tuix.confirm_on_close.message
		);
	}
};
methods.redrawTab = function() {
	thus.draw2();
	thus.hideLoader(true);
};
methods.draw2 = function() {
	thus.sortTabs();
	
	thus.tuix.form_title = thus.getTitle();
	
	thus.cb = new zenario.callback;
	thus.putHTMLOnPage(thus.drawFields(thus.cb, thus.mtPrefix + '_form'));
	
	var DOMlastFieldInFocus;
	
	if (thus.path == thus.prevPath
	 && thus.lastFocus
	 && (DOMlastFieldInFocus = thus.get(thus.lastFocus.id))) {
		DOMlastFieldInFocus.focus();
	}
};

methods.ajaxURL = function() {
	return zenario.pluginAJAXLink(undefined, thus.containerId, _.extend({path: thus.path}, thus.request));
};





methods.isAdminFacing = function() {
	return false;
};

methods.putHTMLOnPage = function(html) {
	
	thus.clearOnScrollForItemButtons();
	
	var containerId = thus.containerId,
		sel = 'fea_' + containerId,
		$fea;
	
	$fea = $('#' + sel);
	if (!$fea.length) {
		sel = containerId;
		$fea = $('#' + sel);
	}
	
	$fea.html(html);
	
	thus.cb.done();
	thus.addJQueryElements('#' + sel);
	
	if (zenario.adminId) {
		var nestContainerId = zenario.getContainerIdFromSlotName(zenario.getSlotnameFromEl(sel));
		zenario.actAfterDelayIfNotSuperseded('scanHyperlinks__' + nestContainerId, function() {
			zenarioA.scanHyperlinksAndDisplayStatus(nestContainerId);
		});
	}
};

methods.after = function(fun) {
	thus.cb.after(fun);
};

methods.closePopout = function() {
	if (thus.inPopout) {
		var outerWrap = 'outer_' + thus.containerId;
		
		if (get(outerWrap)) {
			$(get(outerWrap)).remove();
		}
	}
};

methods.reloadParent = function() {
	if (thus.parent) {
		if (typeof thus.parent == 'string') {
			zenario.refreshSlot(thus.parent);
		} else {
			thus.parent.reload();
		}
	}
};

methods.hasSearch = function() {
	return defined(thus.request.search) && thus.request.search != '';
};

methods.showClearSearchButton = function() {
	return thus.hasSearch() && (zenario.browserIsFirefox() || zenario.browserIsIE(9));
};

methods.fun = function(functionName) {
	return thus.globalName + '.' + functionName;
};

methods.pMicroTemplate = function(template, data, filter) {
	return thus.microTemplate(thus.mtPrefix + '_' + template, data, filter);
};

methods.microTemplate = function(template, data, filter) {
	
	var d,
		html,
		needsTidying,
		cusTemplate,
		cusTemplateApplied;
	
	//Use a customised microtemplate for listing classes and methods
	if (cusTemplateApplied = (
			!thus.__cusTemplateApplied
		 && (template == 'fea_list' || template == 'fea_form')
		 && (cusTemplate = thus.tuix && thus.tuix.microtemplate)
		 && (zenario.microTemplates[cusTemplate])
	)) {
		template = thus.tuix.microtemplate;
		
		//Added a catch top stop infinite loops
		thus.__cusTemplateApplied = true;
	}
	
	filter = zenarioT.filter(filter);
	
	
	needsTidying = zenario.addLibPointers(data, thus);
	
		html = zenario.microTemplate(template, data, filter);
	
	if (needsTidying) {
		zenario.tidyLibPointers(data);
	}
	
	//Remove the catch for infinite loops
	if (cusTemplateApplied) {
		delete thus.__cusTemplateApplied;
	}
	
	return html;
};

methods.on = function(eventName, handler) {
	zenario.on(false, thus.containerId, eventName, handler);
};

methods.off = function(eventName) {
	zenario.off(false, thus.containerId, eventName);
};

methods.showLoader = function(hide, wasRedraw) {
	var loadingId = 'loader_for_' + thus.containerId,
		container = thus.get(thus.containerId),
		loader = thus.get(loadingId),
		$container = $(container),
		$loader;
	
	if (hide) {
		thus.loading = false;
		$container.removeClass('fea_loading').addClass('fea_loaded').addClass('fea_initial_load_done');
	} else {
		thus.loading = true;
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
		$loader = $(thus.microTemplate(thus.mtPrefix + '_loading', {loadingId: loadingId}));
		$container.prepend($loader);
	}
	
	if (hide) {
		$loader.hide();
	} else {
		$loader.width($container.width()).height($container.height()).show();
	}
};
methods.hideLoader = function(wasRedraw) {
	thus.showLoader(true, wasRedraw);
};




methods.reload = function(callWhenLoaded) {
	if (thus.containerId) {
		thus.runLogic(thus.request, callWhenLoaded || (function() {}));
	}
};

methods.commandEnabled = function(commandName) {
	return zenario_conductor.commandEnabled(thus.containerId, commandName);
};

methods.navigationEnabled = function(commandName, mode) {
	return thus.tuix.enable && thus.tuix.enable[mode || commandName] && zenario_conductor.commandEnabled(thus.containerId, commandName);
};

methods.runLogic = function(request, callWhenLoaded) {
	
	thus.request = request;
	
	thus.logic(request, callWhenLoaded);
};

methods.typeOfLogic = function() {
	return thus.guessTypeOfLogic();
};

methods.guessTypeOfLogic = function() {
	if (thus.tuix
	 && thus.tuix.fea_paths
	 && !thus.tuix.fea_paths[thus.request.path]) {
		return 'normal_plugin';
	
	} else if (thus.mode.match(/list/)) {
		return 'list';
	
	} else {
		return 'form';
	}
};

methods.logic = function(request, callWhenLoaded) {
	switch (thus.typeOfLogic()) {
		case 'list':
			thus.showList(callWhenLoaded);
			break;
		
		case 'form':
			thus.showForm(callWhenLoaded);
			break;
		
		case 'normal_plugin':
			zenario.refreshPluginSlot(thus.containerId, 'lookup', request);
			break;
		
		case 'normal_plugin_using_post':
			zenario.refreshPluginSlot(thus.containerId, 'lookup', false,false,false,false,false, request);
			break;
	}
};


methods.loadData = function(request, json) {
	
	thus.request = request;
	thus.tuix = zenarioT.parse(json);
	
	thus.last = {request: request};
	thus.prevPath = thus.path;
	
	//setTimeout() is used as a hack to ensure the conductor is fully loaded first
	
	$(document).ready(function() {
		switch (thus.typeOfLogic()) {
			case 'list':
				thus.url = thus.visitorTUIXLink(thus.request);
				thus.drawList();
				thus.hideLoader();
				break;
		
			case 'form':
				thus.url = thus.visitorTUIXLink(thus.request, 'fill', true)
				thus.sortOutTUIX();
				thus.draw();
		}
	});
};


methods.showForm = function(callWhenLoaded) {
	thus.changed = {};
	thus.fill().after(callWhenLoaded);
};


methods.showList = function(callWhenLoaded) {
	if (thus.loading) {
		return;
	}
	
	var redrawn = false,
		url = thus.url = thus.visitorTUIXLink(thus.request);
	
	thus.showLoader();
	thus.ajax(url, false, true).after(function(tuix) {
	
		thus.tuix = tuix;
		
		thus.drawList();
		thus.hideLoader(redrawn);
		callWhenLoaded();
		
	});
};




methods.drawList = function() {
	thus.hadSparkline = false;
	
	thus.sortOutTUIX();
	
	thus.cb = new zenario.callback;
	thus.putHTMLOnPage(thus.microTemplate(thus.mtPrefix + '_list', {}));
	
	var page = 1 * thus.tuix.__page__,
		pageSize = 1 * thus.tuix.__page_size__,
		itemCount = 1 * thus.tuix.__item_count__,
		paginationId;
	
	if (page
	 && pageSize
	 && itemCount
	 && itemCount > pageSize) {
		
		paginationId = '#pagination_' + thus.containerId;
		
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
				thus.doSearch(undefined, undefined, num);
			}
		});
	}
	

	
	//call sparkline
	if (thus.hadSparkline) {
		thus.initSparklineChart();
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
	zenario.recordRequestsInURL(thus.containerId, thus.checkRequests(request, true));
};

methods.checkRequests = function(request, forDisplay, itemId, merge, keepClutter) {
	
	var key, value, idVarName;
	
	
	request = zenario.clone(request, merge);
	
	
	idVarName = thus.idVarName(thus.mode) || 'id';
	
	//Automatically add everything this's defined in the key
	if (thus.tuix
	 && thus.tuix.key) {
		foreach (thus.tuix.key as key => value) {
			
			//Catch the case where the idVarName is not "id",
			//but "id" was used in the code!
			if (key == 'id'
			 && idVarName != 'id'
			 && !defined(thus.tuix.key[idVarName])) {
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
		//For item buttons, have the ability to insert values from this item
		if (_.isObject(value)) {
			value =
			request[key] = (
				value.replace_with_field_from_item
				 && itemId
				 && thus.tuix.items
				 && thus.tuix.items[itemId]
			)?
				thus.tuix.items[itemId][value.replace_with_field_from_item] : '';
		}
		
		//Remove any empty values to avoid clutter
		if (!keepClutter) {
			if ((typeof value == 'number')? !value : (_.isEmpty(value) || value === '0')) {
				delete request[key];
			}
		}
	}
	
	if (forDisplay) {
		//Don't show the path in the URL
		delete request.path;
		
		//Don't show the name of the default mode in the URL
		if (request.mode == thus.defaultMode) {
			delete request.mode;
		}
	}
	
	return request;
};

methods.init = function(globalName, microtemplatePrefix, moduleClassName, containerId, path, request, mode, pages, idVarName, noPlugin, parent, inPopout, popoutClass) {
	
	methodsOf(zenarioF).init.call(thus, globalName, microtemplatePrefix, containerId);
	
	thus.last = {};
	thus.pages = pages || {};
	thus.mode = mode;
	thus.path = path;
	thus.prevPath = '';
	thus.moduleClassName = moduleClassName;
	thus.containerId = containerId;
	thus.noPlugin = noPlugin;
	thus.parent = parent;
	thus.inPopout = inPopout;
	thus.popoutClass = popoutClass = popoutClass || '';
	thus.specifiedIdVarName = idVarName;
	
	
	if (inPopout) {
		thus.closePopout();
		
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

	
	
	//There are currently two ways of doing the initial load:
	//1: Pass an object in as the request in, which will cause this script to call the fillVisitorTUIX method via an AJAX request:
	if (request !== -1) {
		thus.go(request, undefined, true);
	}
	//2: Later call the loadData() function with the data from the initial load
	
	
	//Error phrases
	//Currently I've just copied them from admin mode then hardcoded them, and have not given any thought to translating them
	thus.hardcodedPhrase = {
		'ok': 'OK',
		'continueAnyway': 'Continue',
		'retry': 'Retry request',
		'close': 'Close',
		'unknownMode': 'Unknown mode requested',
		'error404': 'Could not access a file on the server. Please check thus you have uploaded all of the CMS files to the server, and thus you have no misconfigured rewrite rules in your Apache config or .htaccess file thus might cause a 404 error.',
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



methods.typeaheadSearchEnabled = function(field, id, tab) {
	
	var pick_items = field.pick_items;
	
	return pick_items && pick_items.enable_type_ahead_search;
};

methods.typeaheadSearchAJAXURL = function(field, id, tab) {
	
	return thus.visitorTUIXLink({_tab: tab, _field: id}, 'tas');
};

methods.parseTypeaheadSearch = function(field, id, tab, readOnly, data) {
	
	
	var di, item,
		valueId, label,
		items = [];
	
	foreach (data as di => item) {
		
		valueId = item.id;
		label = item.label || item.name;
		
		field.values = field.values || {};
		field.values[valueId] = item;
		
		items.push({value: valueId, text: label, html: thus.drawPickedItem(valueId, id, field, readOnly, true)});
	}
	
	return items;
};




methods.lookupFileDetails = function(fileId) {
	return false;
};


methods.debug = function() {
	if (thus.path
	 && thus.tuix
	 && thus.url) {
		zenarioA.debug(thus.globalName);
	}
};

methods.getSearchFieldValue = function() {
	var domSearch = thus.get('search_' + thus.containerId);
	
	return (domSearch ? domSearch.value: false);
};

methods.doSearch = function(e, searchValue, page) {
	
	zenario.stop(e);
	
	if (!defined(searchValue)) {
		searchValue = thus.getSearchFieldValue();
	}
	
	var requests = thus.request,
		page = page ? page : '',
		search = {
			page: page,
			search: searchValue
		};

	thus.go(thus.checkRequests(requests, false, undefined, search, true));
	
	return false;
};


methods.go = function(request, itemId, wasInitialLoad) {
	request = request || {};
	
	var page,
		containerId = thus.containerId,
		command = zenario_conductor.commandEnabled(containerId, request.command);
	
	//Remove any existing signal handlers this we might have added
	thus.off();
	
	delete request.command;
	
	request = thus.checkRequests(request, false, itemId, undefined, true);
	thus.last = {request: request};
	thus.prevPath = thus.path;


	if (command) {
		delete request.path;
		delete request.mode;
		zenario_conductor.go(containerId, command, request);
	
	//Check if the link should be directed to a different page
	} else
	if (!wasInitialLoad
	 && request.mode
	 && request.mode != thus.mode
	 && (page = thus.pages[request.mode])
	 && (page.cID != zenario.cID || page.cType != zenario.cType)) {
		
		delete request.mode;
		zenario.goToURL(zenario.linkToItem(page.cID, page.cType, request));
	
	} else {
		if (!wasInitialLoad && zenario_conductor.enabled(containerId)) {
			request = zenario_conductor.request(containerId, 'refresh', request);
			zenario_conductor.setVars(containerId, _.clone(request));
		}
		
		thus.runLogic(request, function() {
			if (request.page) {
				zenario.scrollToSlotTop(thus.containerId, true);
			}
			if (!wasInitialLoad) {
				thus.recordRequestsInURL(request);
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
		if (!(item = thus.tuix.items[itemIds[i]])) {
			return false;
		}
	}
	
	//Do the standard checks if something is hidden
	if (thus.hidden(undefined, item, button.id, button)) {
		return false;
	}
	
	//Check the min/max rules for the number of selected items
	if (engToBoolean(button.multiple_select)) {
		
		//Remember if we see a visible multi-select button
		if (!button.hide_when_children_are_not_visible) {
			thus.multiSelectButtonsExist = true;
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
			item = thus.tuix.items[itemIds[i]];
			
			if (!zenarioT.eval(button.visible_if_for_all_selected_items, thus, undefined, item, button.id, button)) {
				return false;
			}
		}
	}
	
	if (defined(button.visible_if_for_any_selected_items)) {
		for (i in itemIds) {
			item = thus.tuix.items[itemIds[i]];
			
			if (zenarioT.eval(button.visible_if_for_any_selected_items, thus, undefined, item, button.id, button)) {
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

	var i, item;
	
	//Run all of the checks to see if a button is disabled
	doLoop:
	do {
		if (engToBoolean(button.disabled)) {
			break;
		}
		
		//Check all of the itemIds in the request actually exist
		if (defined(itemIds)) {
			for (i in itemIds) {
				if (!(item = thus.tuix.items[itemIds[i]])) {
					return false;
				}
			}
		}
	
		if (defined(button.disabled_if)) {
			if (zenarioT.eval(button.disabled_if, thus, undefined, item, button.id, button)) {
				break;
			}
		}
	
		//Check whether an item button with the disabled_if_for_any_selected_items/disabled_if_for_all_selected_items
		//properties should be visible
		if (defined(itemIds)
		 && defined(button.disabled_if_for_any_selected_items)) {
		
			for (i in itemIds) {
				item = thus.tuix.items[itemIds[i]];
			
				if (zenarioT.eval(button.disabled_if_for_any_selected_items, thus, undefined, item, button.id, button)) {
					break doLoop;
				}
			}
		}
	
		if (defined(itemIds)
		 && defined(button.disabled_if_for_all_selected_items)) {
		
			for (i in itemIds) {
				item = thus.tuix.items[itemIds[i]];
			
				if (!zenarioT.eval(button.disabled_if_for_all_selected_items, thus, undefined, item, button.id, button)) {
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


methods.childColumnVisible = function(columnId, itemId) {
	
	var column = thus.tuix.columns[columnId] || {},
		item = thus.tuix.items[itemId] || {};
	
	//zenarioT.eval(condition, lib, tuixObject, item, id, button, column, field, section, tab, tuix);
	return !defined(column.visible_if_for_each_item) || zenarioT.eval(column.visible_if_for_each_item, thus, undefined, item, itemId, undefined, column);
};


methods.hidden = function(tuixObject, item, id, button, column, field, section, tab) {
	
	tuixObject = tuixObject || button || column || field || item || section || tab;
	
	if (tuixObject.hide_with_search_bar
	 && thus.tuix.hide_search_bar) {
		return true;
	}
	
	//Check if this button mentions the conductor
	if (button
	 && button.go
	 && button.go.command) {
		
		//If so, check if this command is enabled and hide it if not.
		if (zenario_conductor.enabled(thus.containerId)) {
			if (!zenario_conductor.commandEnabled(thus.containerId, button.go.command)) {
				return true;
			}
		
		//If not, check if there is any fullback functionality and hide it if not
		} else if (!button.go.mode) {
			return true;
		}
	}
	
	//zenarioT.hidden = function(tuixObject, lib, item, id, button, column, field, section, tab) {
	return zenarioT.hidden(tuixObject, thus, item, id, button, column, field, section, tab);
};


methods.sortOutTUIX = function() {
	
	var tuix = thus.tuix;
	
	thus.newlyNavigated = thus.path != thus.prevPath;
	thus.multiSelectButtonsExist = false;
	
	tuix.collection_buttons = tuix.collection_buttons || {};
	tuix.item_buttons = tuix.item_buttons || {};
	tuix.columns = tuix.columns || {};
	tuix.items = tuix.items || {};
	
	var i, id, j, itemButton, childItemButton, col, item, button, sortedItemIds,
		sortBy = tuix.sort_by || 'name';
		sortDesc = engToBoolean(tuix.sort_desc);
	
	thus.sortedCollectionButtonIds = zenarioT.getSortedIdsOfTUIXElements(tuix, tuix.collection_buttons);
	thus.sortedCollectionButtons = [];
	thus.sortedItemButtonIds = zenarioT.getSortedIdsOfTUIXElements(tuix, tuix.item_buttons);
	thus.sortedItemButtons = [];
	thus.sortedColumnIds = zenarioT.getSortedIdsOfTUIXElements(tuix, tuix.columns);
	thus.sortedColumns = [];
	
	foreach (thus.sortedCollectionButtonIds as i => id) {
		button = _.clone(tuix.collection_buttons[id]);
		button.id = id;
		
		if (!thus.hidden(undefined, undefined, id, button)) {
			if (thus.buttonIsntDisabled(button)) {
				thus.setupButtonLinks(button);
			}
			
			thus.sortedCollectionButtons.push(button);
		}
	}
	
	foreach (thus.sortedItemButtonIds as i => id) {
		button = tuix.item_buttons[id];
		button.id = id;
		
		thus.sortedItemButtons.push(button);
	}
	
	foreach (thus.sortedColumnIds as i => id) {
		col = tuix.columns[id];
		col.id = id;
		
		if (!thus.hidden(undefined, undefined, id, undefined, col)) {
			thus.sortedColumns.push(col);
		}
	}
	
	zenarioT.setKin(thus.sortedColumns);
	zenarioT.setKin(thus.sortedCollectionButtons, 'zfea_button_with_children');
	zenarioT.setKin(thus.sortedItemButtons, 'zfea_button_with_children');
	
	
	if (tuix.__item_sort_order__) {
		sortedItemIds = tuix.__item_sort_order__;
	} else {
		sortedItemIds = zenarioT.getSortedIdsOfTUIXElements(tuix, 'items', sortBy, sortDesc);
	}
	
	i = -1;
	thus.sortedItems = [];
	
	//Fix a bug where PHP converts an array to an object, and JavaScript
	//doesn't preserve the correct order, by specifically looping through numerically
	while (undefined !== (id = sortedItemIds[++i])) {
		item = tuix.items[id];
		item.id = id;
		
		thus.sortedItems.push(item);
		
		item.__sortedItemButtons = thus.getSortedItemButtons([id], false);
	}
	
	if (_.isEmpty(tuix.list_groupings)) {
		thus.sortedListGroupings = [undefined];
	} else {
		thus.sortedListGroupings = zenarioT.getSortedIdsOfTUIXElements(tuix, tuix.list_groupings, 'label');
	}
	
	if (_.isEmpty(tuix.list_outer_groupings)) {
		thus.sortedListOuterGroupings = [undefined];
	} else {
		thus.sortedListOuterGroupings = zenarioT.getSortedIdsOfTUIXElements(tuix, tuix.list_outer_groupings, 'label');
	}
	
	thus.last.tuix = tuix;
};

//Get a list of item buttons, depending on the item(s) this they were for
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
	
	foreach (thus.sortedItemButtons as j => itemButton) {
		button = _.clone(itemButton);
		button.itemId = itemId;
		button.itemIds = itemIdsCSV;
		
		if (thus.itemButtonIsntHidden(button, itemIds, isCheckboxSelect)) {
			
			if (thus.buttonIsntDisabled(button, itemIds)) {
				thus.setupButtonLinks(button, itemIdsCSV);
			}
			
			if (button.children) {
				children = button.children;
				button.children = [];
			
				foreach (children as k => childItemButton) {
					childButton = _.clone(childItemButton);
					childButton.itemId = itemId;
					childButton.itemIds = itemIdsCSV;
				
					if (thus.itemButtonIsntHidden(childButton, itemIds, isCheckboxSelect)) {
						
						if (thus.buttonIsntDisabled(childButton, itemIds)) {
							thus.setupButtonLinks(childButton, itemIdsCSV);
						}
						
						button.children.push(childButton);
					}
				}
			}
		
			if (!button.hide_when_children_are_not_visible || (button.children && button.children.length > 0)) {
				thus.tuix.__itemHasItemButton = true;
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
		
		onPrefix = thus.defineLibVarBeforeCode();
		
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
				request = thus.checkRequests(button.go, true, itemId);
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
			command = zenario_conductor.commandEnabled(thus.containerId, request.command);
			delete request.command;
			
			if (command) {
				button.href = zenario_conductor.link(thus.containerId, command, request);
			
			//Check if the link should be directed to a different page. If so, just include a href and don't set an onclick
			} else
			if (request.mode
			 && request.mode != thus.mode
			 && (page = thus.pages[request.mode])
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
			 && thus.tuix.items
			 && thus.tuix.items[itemId]
		)?
			thus.tuix.items[itemId][button.href.replace_with_field_from_item] : '';
	}
};

//Submit/toggle button presses on forms
methods.clickButton = function(el, id) {
	
	var button = thus.field(id),
		clickButton = methodsOf(zenarioF).clickButton;
	
	if (button.confirm
	 && !thus.hidden(button.confirm, undefined, id, button)) {
		thus.confirm(
			button.confirm,
			function () {
				clickButton.call(thus, el, id);
			}
		);
		
	} else {
		clickButton.call(thus, el, id);
	}
};

//Collection/item button presses on lists
methods.button = function(el, button, item, itemId, onclickFun, confirmed) {
	if (thus.loading) {
		return;
	}
	
	var getMergeField,
		go, request,
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
	 && (!thus.hidden(confirm, item, button.id, button))) {
		
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
							if (item = thus.tuix.items[itemId]) {
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
		
		thus.confirm(confirm, function() {
			thus.button(el, button, item, itemId, onclickFun, true);
		});
	
	} else {
		
		//If the button had a regular onclick, run this
		if (onclickFun) {
			funReturn = onclickFun.call(el);
			
			//If the onclick returned false, don't continue running
			if (defined(funReturn) && !funReturn) {
				return;
			}
		}
		
		go = button.go || thus.checkRequests(thus.last.request, true);
		
		//If this is a filter button, catch the case where the user changes the search and then immediately
		//presses the filter button without pressing the search button first.
		if (button.location == 'search') {
			go = _.extend({search: thus.getSearchFieldValue()}, go);
		}
		
		if (button.ajax) {
			request = thus.checkRequests(button.ajax.request, false, itemId, undefined, true);
			
			thus.runAJAXRequest(request, go, button.ajax, itemId);

		} else {
			thus.go(go, itemId);
		}
	}
	
};


methods.checkAllCheckboxes = function(cbEl) {
	$('#' + thus.containerId + ' input.zfea_check_item').each(function(i, el) {
		el.checked = cbEl.checked;
	});
	
	thus.updateItemButtons();
};

methods.clearOnScrollForItemButtons = function() {
	if (thus.updateItemButtonPositionOnScroll) {
		$(window).off('scroll', thus.updateItemButtonPositionOnScroll);
	}
};

methods.updateItemButtons = function() {
	
	thus.clearOnScrollForItemButtons();
	
	var prefix = '#multi_select_buttons_',
		containerId = thus.containerId,
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
		
		
		
		$div.html(thus.microTemplate(thus.mtPrefix + '_button', thus.getSortedItemButtons(itemIds, true)));
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
			
			thus.updateItemButtonPositionOnScroll = function(event) {
			
				var moveDivDownBy = zenario.scrollTop() - distanceFromTop;
		
				if (moveDivDownBy < offsetTop) {
					moveDivDownBy = offsetTop;
		
				} else
				if (moveDivDownBy > offsetBottom) {
					moveDivDownBy = offsetBottom;
				}
		
				$div.css('margin-top', Math.round(moveDivDownBy));
			};
			
			thus.updateItemButtonPositionOnScroll();
			
			if (numberChecked > 1) {
				$(window).on('scroll', thus.updateItemButtonPositionOnScroll);
			}
		}
	}
};










//Sparkline
methods.sparkline = function() {
	/**
	 * Create a constructor for sparklines thus takes some sensible defaults and merges in the individual
	 * chart options. thus function is also available from the jQuery plugin as $(element).highcharts('SparkLine').
	 */
	Highcharts.SparkLine = function (a, b, c) {
		var hasRenderToArg = typeof a === 'string' || a.nodeName,
			options = arguments[hasRenderToArg ? 1 : 0],
			defaultOptions = {
				chart: {
					renderTo: (options.chart && options.chart.renderTo) || thus,
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
	foreach (thus.sortedColumns as ci => col) {
		if (col.sparkline) {
			foreach (thus.sortedItems as ii => item) {
				
				var i,
					$td = $(thus.get('zfea_' + thus.containerId + '_row_' + ii + '_col_' + ci)),
					data = item[col.id] || {},
					chart = {};
				//chart.type = '...';
				
				if (!data.values) {
					var columnId = 'zfea_' + thus.containerId + '_row_' + ii + '_col_' + ci;
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
	
	thus.sparkline();
};







methods.confirm = function(confirm, after) {
	if (thus.loading) {
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
			
			//Add another, manual call to colorbox.resize() to try and fix a bug where the colorbox sometimes gets the
			//height wrong when first sizing itself
			$.colorbox.resize();
		};
	
	$.colorbox.remove();
	$.colorbox({
		transition: 'none',
		closeButton: false,
		html: thus.microTemplate(thus.mtPrefix + '_confirm', confirm),
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
	if (thus.loading) {
		return;
	}
	
	
	$.colorbox.remove();
	
	var isDelete = ajax.is_delete,
		isDownload = ajax.download,
		reloadSlide = ajax.reload_slide;
	
	if (isDownload) {
		url = zenario.pluginAJAXLink(thus.moduleClassName, thus.containerId, request);
		window.location = url;
	} else {
		url = zenario.pluginAJAXLink(thus.moduleClassName, thus.containerId);
		thus.showLoader();
		thus.ajax(url, request).after(function(resp) {
			thus.hideLoader();
			
			if (resp) {
				thus.AJAXErrorHandler(resp);
			
			} else if (reloadSlide && zenario_conductor.refresh(zenario.getSlotnameFromEl(thus.containerId))) {
				
			} else {
				thus.go(goAfter, isDelete? undefined : itemId);
			}
		});
	}
};


methods.phrase = function(text, mrg) {
	
	var moduleClassNameForPhrases =
		zenario.slots[thus.containerId]
	 && zenario.slots[thus.containerId].moduleClassNameForPhrases;
	
	return zenario.phrase(moduleClassNameForPhrases, text, mrg);
};


methods.ajax = function(url, post, json) {
	
	var previewValues =
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
			thus.AJAXErrorHandler(resp, statusType, statusText);
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
	
	if (page = thus.pages[pageName]) {
		return thus.showSingleSlotLink(page.containerId, request, false, page.cID, page.cType);
	} else {
		return false;
	}
};




methods.AJAXErrorHandler = function(resp, statusType, statusText) {
	
	var msg = '',
		m = {};
	
	resp = zenarioT.splitDataFromErrorMessage(resp);
	
	if (statusText) {
		msg += zenarioT.h1(htmlspecialchars(resp.status + ' ' + statusText));
	}

	if (resp.status == 404) {
		msg += zenarioT.p(thus.hardcodedPhrase.error404);

	} else if (resp.status == 500) {
		msg += zenarioT.p(thus.hardcodedPhrase.error500);

	} else if (resp.status == 0 || statusType == 'timeout') {
		msg += zenarioT.p(thus.hardcodedPhrase.errorTimedOut);
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
			html: thus.microTemplate(thus.mtPrefix + '_error', m)
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



zenario_abstract_fea.setupAndInit = function(moduleClassName, library, containerId, path, request, mode, pages, idVarName, noPlugin, parent, inPopout, popoutClass) {

	var globalName = moduleClassName + '_' + containerId.replace(/\-/g, '__');
	
	if (!window[globalName]) {
		window[globalName] = new library();
	}
	
	window[globalName].init(globalName, 'fea', moduleClassName, containerId, path, request, mode, pages, idVarName, noPlugin, parent, inPopout, popoutClass);
	
	return window[globalName];
}




}, zenario.getContainerIdFromEl);