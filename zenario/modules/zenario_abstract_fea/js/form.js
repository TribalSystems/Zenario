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




zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioAB, zenarioAT, zenarioO,
	get, engToBoolean, htmlspecialchars, ifNull, jsEscape, phrase,
	extensionOf, methodsOf, has,
	getContainerIdFromEl, zenarioGrid
) {
	"use strict";



	var zenarioFEA = createZenarioLibrary('FEA', zenarioF),
		methods = methodsOf(zenarioFEA);


methods.idVarName = function(mode) {
	return 'id';
};




//Extend the parent function validateFormatOrRedrawForField() and add the
//option to have a save button
methods.validateFormatOrRedrawForField = function(field) {
	
	field = this.field(field);
	
	if (engToBoolean(field.save_onchange)) {
		this.save();
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


methods.ffov = function(mode) {
	
	var that = this,
		cb = new zenario.callback,
		url = this.url = zenario.pluginVisitorTUIXLink(this.moduleClassName, this.containerId, this.path, this.customisationName, this.request, mode, true),
		post = false;
	
	if (mode != 'fill') {
		this.prevPath = this.path;
		this.checkValues();
		post = {_format: true, _tuix: this.sendStateToServer()};
	}
	
	if (!this.loading) {
		this.showLoader();
		this.ajax(url, post, true).after(function(tuix) {
			that.hideLoader();
		
			if (mode == 'fill') {
				that.tuix = tuix;
			} else {
				that.setData(tuix);
			}
		
			if (that.tuix.go) {
				if (that.tuix.transition_out) {
					that.transitionOut(that.tuix.transition_out);
				}
				if (that.tuix.transition_in_on_next_screen) {
					that.transitionInOnNextScreen = that.tuix.transition_in_on_next_screen;
				}
				
				that.go(that.containerId, that.tuix.go);
			
			} else {
				that.sortOutTUIX(that.containerId, that.tuix);
				that.draw();
				cb.call();
			}
		});
	}
	
	return cb;
};

methods.draw = function() {
	this.draw2();
};
methods.redrawTab = function() {
	this.draw2();
	this.hideLoader(true);
};
methods.draw2 = function() {
	this.sortTabs();
	
	this.tuix.form_title = this.getTitle();
	
	var DOMlastFieldInFocus,
		cb = new zenario.callback;
	
	$('#' + this.containerId).html(this.drawFields(cb, this.mtPrefix + '_form', this.tuix));
	
	zenario.addJQueryElements(this.containerId + ' ');
	//zenario.tooltips(this.containerId + ' .zfea_field_tooltip');
	
	if (this.path == this.prevPath
	 && this.lastFieldInFocus
	 && (DOMlastFieldInFocus = get(this.lastFieldInFocus))) {
		DOMlastFieldInFocus.focus();
	}
	
	cb.call();
};

methods.ajaxURL = function() {
	return zenario.pluginAJAXLink(undefined, this.containerId, {path: this.path});
};





methods.microTemplate = function(template, data, filter) {
	
	var html, d;
	
	if (_.isArray(data)) {
		for (d in data) {
			data[d].that = this;
			if (!data[d].tuix) data[d].tuix = this.tuix;
		}
	} else {
		data.that = this;
		if (!data.tuix) data.tuix = this.tuix;
	}
	
	html = zenario.microTemplate(template, data, filter);
	
	if (_.isArray(data)) {
		for (d in data) {
			delete data[d].that;
			delete data[d].tuix;
		}
	} else {
		delete data.that;
		delete data.tuix;
	}
	
	return html;
};

methods.showLoader = function(hide, wasRedraw) {
	var loaderId = 'loader_for_' + this.containerId,
		container = get(this.containerId),
		loader = get(loaderId),
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
		$loader = $(this.microTemplate(this.mtPrefix + '_loading', {loaderId: loaderId}));
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
		this.runLogic(this.containerId, this.request, callWhenLoaded || (function() {}));
	}
};

methods.checkModeAndPathOnRequest = function(request) {
	if (request.mode) {
		request.path = this.getPathFromMode(request.mode);
	} else if (request.path) {
		request.mode = this.getModeFromPath(request.path);
	} else {
		request.mode = this.defaultMode;
		request.path = this.getPathFromMode(this.defaultMode);
	}
};

methods.runLogic = function(containerId, request, callWhenLoaded) {
	this.checkModeAndPathOnRequest(request);
	
	this.mode = request.mode;
	this.path = request.path;
	this.request = request;
	this.containerId = containerId;
	
	this.logic(containerId, request, callWhenLoaded);
};

methods.logic = function(containerId, request, callWhenLoaded) {
	if (request.path.match(/list/)) {
		this.showList(callWhenLoaded);
	} else {
		this.showForm(callWhenLoaded);
	}
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
		url = this.url = zenario.pluginVisitorTUIXLink(this.moduleClassName, this.containerId, this.request.path, this.customisationName, this.request);
	
	this.showLoader();
	this.ajax(url, false, true).after(function(tuix) {
	
		that.tuix = tuix;
		
		that.drawList();
		that.hideLoader(redrawn);
		callWhenLoaded();
		
	});
};

//These signals will fire if the page is resized and switches between mobile/desktop views
//assetwolf_2.resizedToMobile = function() {
//	console.log('resizedToMobile');
//};
//
//assetwolf_2.resizedToDesktop = function() {
//	console.log('resizedToDesktop');
//};

methods.sizeTableListCells = function() {
	$('.zfea_with_responsive_table .zfea_table_list_wrap tr').each(function(i, el) {
		var maxHeight = 0,
			$children = $(el).children();
	
		$children.each(function(i, child) {
			$(child).css('height', '');
		});
		$children.each(function(i, child) {
			maxHeight = Math.max(maxHeight, $(child).height());
		});
		$children.each(function(i, child) {
			$(child).height(maxHeight);
		});
	});
};

methods.sizeTableListCellsIfNeededAfterDelay = function() {
	if (zenario.mobile) {
		var that = this;
		setTimeout(function() {
			that.sizeTableListCells();
		}, 0);
	}
};




methods.drawList = function() {
	this.hadSparkline = false;
	
	this.sortOutTUIX(this.containerId, this.tuix);
	
	$('#' + this.containerId).html(this.microTemplate(this.mtPrefix + '_list', {}));
	
	var that = this,
		page = 1 * this.tuix.__page__,
		pageSize = 1 * this.tuix.__page_size__,
		itemCount = 1 * this.tuix.__item_count__,
		paginationId;
	
	//	To do:
	//		- add the jPaginator library to the page
	//		- rewrite and implement this pagination logic that I copied from Organizer
	
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
				that.go(that.containerId, {page: num});
			}
		});
	}
	

	zenario.addJQueryElements(this.containerId + ' ');
	
	//call sparkline
	if (this.hadSparkline) {
		this.initSparklineChart();
	}
	
	if (!this.addedResizeListener
	 && window.addEventListener) {
		window.addEventListener("resize", this.sizeTableListCells);
	}
	this.addedResizeListener = true;
	
	if (zenario.mobile) {
		this.sizeTableListCellsIfNeededAfterDelay();
	}

};




methods.getPathFromMode = function(mode) {
	//N.b. "create" is just an alias for "edit" to give a nicer URL
	return 'zenario_' + mode;
};
methods.getModeFromPath = function(path) {
	return path.replace(/^zenario_/, '');
};


methods.recordRequestsInURL = function(containerId, request) {
	zenario.recordRequestsInURL(containerId, this.checkRequests(request, true));
};

methods.checkRequests = function(request, forDisplay, itemId, merge, keepClutter) {
	
	var key, value, idVarName;
	
	
	request = zenario.clone(request, merge);
	
	this.checkModeAndPathOnRequest(request);
	
	idVarName = this.idVarName(request.mode || this.mode) || 'id';
	
	//Automatically add everything that's defined in the key
	if (this.tuix
	 && this.tuix.key) {
		foreach (this.tuix.key as key => value) {
			
			//Catch the case where the idVarName is not "id",
			//but "id" was used in the code!
			if (key == 'id'
			 && idVarName != 'id'
			 && this.tuix.key[idVarName] === undefined) {
				key = idVarName;
			}
			
			if (request[key] === undefined) {
				request[key] = value;
			}
		}
	}
	
	if (request[idVarName] === undefined) {
		if (itemId) {
			request[idVarName] = itemId;
		} else if (keepClutter) {
			request[idVarName] = '';
		}
	}
	
	//Remove any empty values to avoid clutter
	if (!keepClutter) {
		foreach (request as key => value) {
			if (value === ''
			 || value === false) {
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

methods.init = function(globalName, microtemplatePrefix, moduleClassName, containerId, request, setDefaultMode, pages, hasEditPerms, customisationName) {
	
	methodsOf(zenarioF).init.call(this, globalName, microtemplatePrefix);
	
	this.last = {};
	this.pages = pages || {};
	this.mode = '';
	this.path = '';
	this.prevPath = '';
	this.customisationName = customisationName || '';
	this.containerId = containerId;
	this.defaultMode = setDefaultMode;
	this.hasEditPerms = hasEditPerms;
	this.moduleClassName = moduleClassName;
	this.go(containerId, request, undefined, true);
	
	
	//Error phrases
	//Currently I've just copied them from admin mode then hardcoded them, and have not given any thought to translating them
	this.hardcodedPhrase = {
		'ok': 'OK',
		'retry': 'Retry',
		'cancel': 'Cancel',
		'unknownMode': 'Unknown mode requested',
		'error404': 'Could not access a file on the server. Please check that you have uploaded all of the CMS files to the server, and that you have no misconfigured rewrite rules in your Apache config or .htaccess file that might cause a 404 error.',
		'error500': "Something on the server is incorrectly set up or misconfigured.",
		'errorTimedOut': "There was no reply or a blank reply from the server.\n\nThis could be a temporary network problem, or could be a bug in the application."
	};
};

methods.editModeOn = function(tab) {
	return this.hasEditPerms;
};

methods.editModeAlwaysOn = function(tab) {
	return this.hasEditPerms;
};

methods.debug = function() {
	if (this.path
	 && this.tuix
	 && this.url) {
		zenarioA.debug(this.globalName);
	}
};

methods.doSearch = function(e) {
	
	zenario.stop(e);

	var requests = this.request,//{{JSON.stringify(m.that.request)|escape}},
		search = {search: get('search_' + this.containerId).value};

	this.go(this.containerId, this.checkRequests(requests, false, undefined, search));
	
	return false;
}


methods.go = function(containerId, request, itemId, wasInitialLoad) {
	containerId = getContainerIdFromEl(containerId);
	
	//If a mode or path is not specified, assume we stay on the default path
	if (request.mode === undefined
	 && request.path === undefined) {
		request.path = this.path;
	}
	
	request = this.checkRequests(request, false, itemId);
	this.last[containerId] = {request: request};
	this.prevPath = this.path;
	
	//Check if the link should be directed to a different page
	var page,
		that = this,
		command = request.command;
	
	delete request.command;

	if (command
	 && zenario_conductor.commandEnabled(containerId, command)) {
		delete request.mode;
		delete request.path;
		zenario_conductor.go(containerId, command, request);
	
	} else
	if (!wasInitialLoad
	 && request.mode
	 && request.mode != this.defaultMode
	 && (page = this.pages[request.mode])
	 && (page.cID != zenario.cID || page.cType != zenario.cType)) {
		
		delete request.mode;
		zenario.goToURL(zenario.linkToItem(page.cID, page.cType, request));
	
	} else {
		this.runLogic(containerId, request, function() {
			if (!wasInitialLoad) {
				that.recordRequestsInURL(containerId, request);
			}
			
			if (that.transitionInOnNextScreen) {
				that.transitionIn(that.transitionInOnNextScreen);
			
			} else if (that.newlyNavigated && that.tuix.transition_in) {
				that.transitionIn(that.tuix.transition_in);
			}
			
			delete that.transitionInOnNextScreen;
		});
	}
};


methods.hidden = function(tuixObject, item, id, tuix, button, column, field, section, tab) {
	tuixObject = tuixObject || button || column || field || item || section || tab;
	
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
	
	if (!this.hasEditPerms
	 && engToBoolean(tuixObject.needs_edit_perms)) {
		return true;
	} else {
		return zenarioA.hidden(tuixObject, item, id, tuix, button, column, field, section, tab)
	}
};


methods.sortOutTUIX = function(containerId, tuix) {
	containerId = getContainerIdFromEl(containerId);
	
	this.newlyNavigated = this.path != this.prevPath;
	
	tuix.collection_buttons = tuix.collection_buttons || {};
	tuix.item_buttons = tuix.item_buttons || {};
	tuix.columns = tuix.columns || {};
	tuix.items = tuix.items || {};
	
	var i, id, j, jd, col, item, button,
		firstItem = false,
		sortBy = tuix.sort_by || 'name';
		sortDesc = engToBoolean(tuix.sort_desc);
	
	tuix.sortedCollectionButtonIds = zenarioA.getSortedIdsOfTUIXElements(tuix, tuix.collection_buttons);
	tuix.sortedCollectionButtons = [];
	tuix.sortedItemButtonIds = zenarioA.getSortedIdsOfTUIXElements(tuix, tuix.item_buttons);
	tuix.sortedItemButtons = [];
	tuix.sortedColumnIds = zenarioA.getSortedIdsOfTUIXElements(tuix, tuix.columns);
	tuix.sortedColumns = [];
	
	if (tuix.__item_sort_order__) {
		tuix.sortedItemIds = tuix.__item_sort_order__;
	} else {
		tuix.sortedItemIds = zenarioA.getSortedIdsOfTUIXElements(tuix, 'items', sortBy, sortDesc);
	}
	tuix.sortedItems = [];

	foreach (tuix.sortedColumnIds as i => id) {
		col = tuix.columns[id];
		col.id = id;
		
		if (!this.hidden(undefined, undefined, id, tuix, undefined, col)) {
			tuix.sortedColumns.push(col);
		}
	}
	
	foreach (tuix.sortedItemIds as i => id) {
		item = tuix.items[id];
		item.id = id;
		
		tuix.sortedItems.push(item);
		
		item.sortedItemButtons = [];
		
		foreach (tuix.sortedItemButtonIds as j => jd) {
			button = _.clone(tuix.item_buttons[jd]);
			button.id = jd;
			this.setupButtonLinks(containerId, button, id);
			
			if (firstItem) {
				tuix.sortedItemButtons.push(button);
			}
			
			if (!this.hidden(undefined, item, jd, tuix, button)) {
				item.sortedItemButtons.push(button);
			}
		}
		
		firstItem = false;
	}
	foreach (tuix.sortedCollectionButtonIds as i => id) {
		button = tuix.collection_buttons[id];
		button.id = id;
		
		if (this.hidden(undefined, undefined, id, tuix, button)) {
			continue;
		}
		
		this.setupButtonLinks(containerId, button);
		
		if (!this.hidden(undefined, item, id, tuix, button)) {
			tuix.sortedCollectionButtons.push(button);
		}
	}
	
	zenarioA.setKin(tuix.sortedColumns);
	zenarioA.setKin(tuix.sortedCollectionButtons, 'zfea_button_with_children');
	zenarioA.setKin(tuix.sortedItemButtons, 'zfea_button_with_children');
	
	this.last[containerId].tuix = tuix;
};

methods.setupButtonLinks = function(containerId, button, itemId) {
	containerId = getContainerIdFromEl(containerId);
	
	var page,
		request,
		onclick,
		command;
	
	if (button.go
	 || button.ajax
	 || button.confirm) {
		
		if (!button.onclick
		 || !button.onclick.match(/\bzzdonezzthiszz\b/)) {
			onclick = this.globalName + ".button(this, '" + jsEscape(containerId) + "', '" + jsEscape(button.id) + "'";
		
			if (itemId) {
				onclick += ", '" + jsEscape(itemId) + "'";
			} else {
				onclick += ", undefined";
			}
		
			if (button.go) {
				request = this.checkRequests(button.go, true, itemId);
			}
		
			if (button.onclick) {
				onclick += ", function () {" + button.onclick + "}";
			}
		
			onclick += "); return false; /* zzdonezzthiszz */";
		
			button.onclick = onclick;
		}
	}
	
	//Check if this button has a "go" link
	if (button.href === undefined) {
		if (request) {
			command = request.command;
			delete request.command;
			
			if (command
			 && zenario_conductor.commandEnabled(containerId, command)) {
			 	delete request.mode;
			 	delete request.path;
				button.href = zenario_conductor.link(containerId, command, request);
			
			//Check if the link should be directed to a different page. If so, just include a href and don't set an onclick
			} else
			if (request.mode
			 && request.mode != this.defaultMode
			 && (page = this.pages[request.mode])
			 && (page.cID != zenario.cID || page.cType != zenario.cType)) {
			
				delete request.mode;
				button.href = zenario.linkToItem(page.cID, page.cType, request);
				return;
		
			} else {
				button.href = zenario.linkToItem(zenario.cID, zenario.cType, request);
			}
		}
	}
};

//Submit/toggle button presses on forms
methods.clickButton = function(el, id) {
	
	var that = this,
		button = this.field(id),
		clickButton = methodsOf(zenarioF).clickButton;
	
	if (button.confirm
	 && !this.hidden(button.confirm, undefined, id, this.tuix, button, undefined, button)) {
		awf.confirm(
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
methods.button = function(el, containerId, buttonName, itemId, onclickFun, confirmed) {
	if (this.loading) {
		return;
	}
	
	delete this.transitionInOnNextScreen;
	
	containerId = getContainerIdFromEl(containerId);
	
	var that = this,
		button,
		item = false,
		last = this.last[containerId],
		request,
		confirm,
		funReturn;
	
	if (itemId !== undefined) {
		item = last.tuix.items[itemId];
		button = last.tuix.item_buttons[buttonName];
	} else {
		button = last.tuix.collection_buttons[buttonName];
	}
	
	if (!confirmed
	 && (confirm =
	 		button.confirm
	 	|| (button.go && button.go.confirm)
	 	|| (button.ajax && button.ajax.confirm))
	 && (!this.hidden(confirm, item, buttonName, last.tuix, button))) {
		
		if (item) {
			confirm = _.extend({}, confirm);
			confirm.message = zenario.applyMergeFields(confirm.message, item);
		}
		
		this.confirm(confirm, function() {
			that.button(el, containerId, buttonName, itemId, onclickFun, true);
		});
	
	} else {
		
		//If the button had a regular onclick, run that
		if (onclickFun) {
			funReturn = onclickFun.call(el);
			
			//If the onclick returned false, don't continue running
			if (funReturn !== undefined && !funReturn) {
				return;
			}
		}
		
		if (button.ajax) {
			request = this.checkRequests(button.ajax.request, false, itemId, undefined, true);
			this.runAJAXRequest(containerId, request, last.request);

		} else {
			if (button.transition_out) {
				this.transitionOut(button.transition_out);
			}
			if (button.transition_in_on_next_screen) {
				this.transitionInOnNextScreen = button.transition_in_on_next_screen;
			}
			this.go(containerId, button.go, itemId);
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
					$td = $(get('zfea_' + this.containerId + '_row_' + ii + '_col_' + ci)),
					data = item[col.id],
					chart = {};
				//chart.type = '...';
				
				if (!data.values) {
					var columnId = 'zfea_' + this.containerId + '_row_' + ii + '_col_' + ci;
					$('#'+columnId).html(data.no_data_message);
					continue;
				}
				
				data.values = $.map(data.values, parseFloat);
				var colour;
				if(data.colour){
					colour = data.colour;
				}else{
					colour = "#82CAFF";
				}
			
				$td.highcharts('SparkLine', {
					colors: [colour],
					series: [{
						data: data.values,
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
	
	zenario.loadLibrary('zenario/libraries/not_to_redistribute/highcharts/highcharts.min.js', function() {
		that.sparkline();
		
		that.sizeTableListCellsIfNeededAfterDelay();
	});
};









methods.transitionIn = function(transition_in) {
	var $slot = $('#' + this.containerId),
		$zfea = $slot.find('.zfea'),
		options = transition_in.options || {};
	
	if (_.isString(options.duration)) {
		options.duration *= 1;
	}
	if (_.isArray(options.easing)) {
		options.easing = $.bez(options.easing);
	}
	
	if (transition_in.initial) {
		$zfea.css(transition_in.initial);
	}
	
	if (transition_in.animate) {
		$zfea.animate(transition_in.animate, options);
	}
};

//Play a transition out, when the current view is removed and replaced with something else
methods.transitionOut = function(transition_out) {
	
	if (!transition_out
	 || !transition_out.animate) {
		return;
	}
	
	var $slot = $('#' + this.containerId),
		$cloneS = $slot.clone()
			.attr('id', '')
			.css('position', 'absolute')
			.addClass('zenario_slot_dummy_for_transition')
			.width($slot.width())
			.height($slot.height())
			.insertBefore($slot),
		$zfea = $slot.find('.zfea'),
		$cloneZ = $cloneS.find('.zfea')
			.removeClass('zfea_newly_navigated')
			.addClass('zfea_dummy_for_transition'),
		
		options = transition_out.options || {},
		slotOriginalHeightPixels = $slot.height(),
		slotOriginalHeightCSS = $slot.css('min-height');
	
	options.complete = function() {
		$cloneS.remove();
		$slot.css('min-height', slotOriginalHeightCSS);
	};
	$slot.css('min-height', slotOriginalHeightPixels + 'px');
	
	if (_.isString(options.duration)) {
		options.duration *= 1;
	}
	if (_.isArray(options.easing)) {
		options.easing = $.bez(options.easing);
	}
	$cloneZ.animate(transition_out.animate, options);
	$zfea.hide();
};

methods.confirm = function(confirm, after) {
	if (this.loading) {
		return;
	}
	
	if (!_.isFunction(after) && after._zenario_confirmed) {
		delete after._zenario_confirmed;
		return true;
	}

	
	$.colorbox({
		transition: 'none',
		closeButton: false,
		html: this.microTemplate(this.mtPrefix + '_confirm', confirm),
		className: 'zfea_colorbox_content' + (confirm.css_class? ' ' + confirm.css_class : '')
	});

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


methods.runAJAXRequest = function(containerId, request, goAfter) {
	if (this.loading) {
		return;
	}
	
	containerId = getContainerIdFromEl(containerId);
	
	$.colorbox.remove();
	
	var that = this,
		url = zenario.pluginAJAXLink(this.moduleClassName, containerId);
	
	this.showLoader();
	this.ajax(url, request).after(function(resp) {
		that.hideLoader();
	
		if (resp) {
			that.AJAXErrorHandler(resp);
		} else {
			that.go(containerId, goAfter);
		}
	});
};


methods.phrase = function(text, mrg) {
	
	var moduleClassNameForPhrases =
		zenario.slots[this.containerId]
	 && zenario.slots[this.containerId].moduleClassNameForPhrases;
	
	return zenario.phrase(moduleClassNameForPhrases, text, mrg);
};


methods.ajax = function(url, post, json) {
	
	var previewValues = windowParent
	 && windowParent.zenarioAB
	 && windowParent.zenarioAB.previewValues;
	
	if (previewValues) {
		if (post === false
		 || post === undefined) {
			post = {};
		}
		
		if (_.isObject(post)) {
			post.overrideSettings = previewValues;
		} else {
			post += '&overrideSettings=' + encodeURIComponent(previewValues);
		}
	}
	
	//zenario.ajax(url, post, json, useCache, retry, timeout, settings, AJAXErrorHandler, onRetry)
	return zenario.ajax(url, post, json, false, true, undefined, undefined, this.AJAXErrorHandler, function() {
		$.colorbox.remove();
	});
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
	
	if (_.isString(resp)) {
		msg = htmlspecialchars(resp);
	} else {
		if (statusText) {
			msg += '<h1><b>' + htmlspecialchars(resp.status + ' ' + statusText) + '</b></h1>';
		}
	
		if (resp.status == 404) {
			msg += '<p>' + this.hardcodedPhrase.error404 + '</p>';
	
		} else if (resp.status == 500) {
			msg += '<p>' + this.hardcodedPhrase.error500 + '</p>';
	
		} else if (resp.status == 0 || statusType == 'timeout') {
			msg += '<p>' + this.hardcodedPhrase.errorTimedOut + '</p>';
		}
	
		if (resp.responseText) {
			msg += '<div>' + resp.responseText + '</div>';
		}
	}
	
	
	showErrorMessage = function() {
		
		m.body = msg;
		m.retry = !!resp.zenario_retry;
		
		$.colorbox({
			transition: 'none',
			closeButton: false,
			html: that.microTemplate(that.mtPrefix + '_error', m)
		});
		
		if (resp.zenario_retry) {
			$('#zfea_retry').click(function() {
				setTimeout(resp.zenario_retry, 1);
			});
		}
	}
	
	if (resp.status == 0 || statusType == 'timeout') {
		setTimeout(showErrorMessage, 750);
	} else {
		showErrorMessage();
	}
};




	



}, zenario.getContainerIdFromEl, window.zenarioGrid || {});