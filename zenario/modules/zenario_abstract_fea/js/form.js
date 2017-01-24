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
	getSlotnameFromEl, zenarioGrid
) {
	"use strict";



	var zenarioFEA = createZenarioLibrary('FEA', zenarioF),
		methods = methodsOf(zenarioFEA);







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
		url = this.url = zenario.pluginVisitorTUIXLink(this.moduleClassName, this.slotName, this.path, this.customisationName, this.request, mode, true),
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
				
				that.go(that.slotName, that.tuix.go);
			
			} else {
				that.sortOutTUIX(that.slotName, that.tuix);
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
methods.draw2 = function() {
	this.sortTabs();
	
	this.tuix.form_title = this.getTitle();
	
	var cb = new zenario.callback;
	$('#plgslt_' + this.slotName).html(this.drawFields(cb, this.mtPrefix + '_form', this.tuix));
	
	zenario.addJQueryElements('#plgslt_' + this.slotName + ' ');
	
	cb.call();
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

methods.showLoader = function(hide) {
	this.removeResponsiveHandler();
	
	var containerId = 'plgslt_' + this.slotName,
		loaderId = 'loader_for_' + containerId,
		container = get(containerId),
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
methods.hideLoader = function() {
	this.showLoader(true);
};




methods.reload = function(callWhenLoaded) {
	if (this.slotName) {
		this.runLogic(this.slotName, this.request, callWhenLoaded || (function() {}));
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

methods.runLogic = function(slotName, request, callWhenLoaded) {
	this.removeResponsiveHandler();
	this.checkModeAndPathOnRequest(request);
	
	this.mode = request.mode;
	this.path = request.path;
	this.request = request;
	this.slotName = slotName;
	
	this.logic(slotName, request, callWhenLoaded);
};

methods.logic = function(slotName, request, callWhenLoaded) {
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
		url = this.url = zenario.pluginVisitorTUIXLink(this.moduleClassName, this.slotName, this.request.path, this.customisationName, this.request);
	
	this.showLoader();
	this.ajax(url, false, true).after(function(tuix) {
		that.hideLoader();
	
		that.tuix = tuix;
		that.sortOutTUIX(that.slotName, that.tuix);
		
		delete that.responsiveHandler;
		
		var minWidth = window.matchMedia && zenarioGrid.responsive && 1 * zenarioGrid.minWidth;
		
		if (!minWidth) {
			that.drawList();
			callWhenLoaded();
		
		} else {
			enquire.register(
				that.responsiveQuery = 'screen and (min-width: ' + minWidth + 'px)',
				that.responsiveHandler = {
					match : function() {
						that.drawList();
						callWhenLoaded();
					},
					doesntMatch : function() {
						that.drawList(true);
						callWhenLoaded();
					}
				}
			);
		}
		
	});
};


methods.drawList = function(responsive) {
	this.responsive = responsive;
	this.hadSparkline = false;
	
	$('#plgslt_' + this.slotName).html(this.microTemplate(this.mtPrefix + '_list', {}));
	
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
				that.go(that.slotName, {page: num});
			}
		});
	}
	

	zenario.addJQueryElements('#plgslt_' + this.slotName + ' ');
	
	//call sparkline
	if (this.hadSparkline) {
		this.initSparklineChart();
	}
};

methods.removeResponsiveHandler = function() {
	if (this.responsiveHandler) {
		enquire.unregister(this.responsiveQuery, this.responsiveHandler);
		delete this.responsiveHandler;
	}
};



methods.getPathFromMode = function(mode) {
	//N.b. "create" is just an alias for "edit" to give a nicer URL
	return 'zenario_' + mode.replace(/^create_/, 'edit_');
};
methods.getModeFromPath = function(path) {
	return path.replace(/^zenario_/, '').replace(/^create_/, 'edit_');
};


methods.recordRequestsInURL = function(slotName, request) {
	zenario.recordRequestsInURL(slotName, this.checkRequests(request, true));
};

methods.checkRequests = function(request, forDisplay, merge, keepClutter) {
	var key, value;
	
	
	request = _.extend({}, request, merge);
	
	this.checkModeAndPathOnRequest(request);
	
	//Automatically add everything that's defined in the key
	if (this.tuix
	 && this.tuix.key) {
		foreach (this.tuix.key as key => value) {
			if (request[key] === undefined) {
				request[key] = value;
			}
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

methods.init = function(globalName, microtemplatePrefix, moduleClassName, slotName, request, setDefaultMode, pages, hasEditPerms, customisationName) {
	
	methodsOf(zenarioF).init.call(this, globalName, microtemplatePrefix);
	
	this.last = {};
	this.pages = pages || {};
	this.mode = '';
	this.path = '';
	this.prevPath = '';
	this.customisationName = customisationName || '';
	this.slotName = slotName;
	this.containerId = 'plgslt_' + slotName;
	this.defaultMode = setDefaultMode;
	this.hasEditPerms = hasEditPerms;
	this.moduleClassName = moduleClassName;
	this.go(slotName, request, undefined, true);
	
	
	//Error phrases
	//Currently I've just copied them from admin mode then hardcoded them, and have not given any thought to translating them
	this.hardcodedPhrase = {
		'ok': 'OK',
		'retry': 'Retry',
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

	this.go(this.slotName, this.checkRequests(requests, false, search));
	
	return false;
}


methods.go = function(slotName, request, itemId, wasInitialLoad) {
	slotName = getSlotnameFromEl(slotName);
	
	//If a mode or path is not specified, assume we stay on the default path
	if (request.mode === undefined
	 && request.path === undefined) {
		request.path = this.path;
	}
	
	request = this.checkRequests(request, false, itemId? {id: itemId} : undefined);
	this.last[slotName] = {request: request};
	this.prevPath = this.path;
	
	//Check if the link should be directed to a different page
	var page,
		that = this;
	
	if (!wasInitialLoad
	 && request.mode
	 && request.mode != this.defaultMode
	 && (page = this.pages[request.mode])
	 && (page.cID != zenario.cID || page.cType != zenario.cType)) {
		
		delete request.mode;
		zenario.goToURL(zenario.linkToItem(page.cID, page.cType, request));
	
	} else {
		this.runLogic(slotName, request, function() {
			if (!wasInitialLoad) {
				that.recordRequestsInURL(slotName, request);
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


methods.hidden = function(tuixObject, item, id) {
	
	if (!this.hasEditPerms
	 && engToBoolean(tuixObject.needs_edit_perms)) {
		return true;
	} else {
		return zenarioA.hidden(tuixObject, true, item, id)
	}
};


methods.sortOutTUIX = function(slotName, tuix) {
	slotName = getSlotnameFromEl(slotName);
	
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
		
		if (this.hidden(col, undefined, id)) {
			continue;
		}
		
		tuix.sortedColumns.push(col);
	}
	
	zenarioA.setKin(tuix.sortedColumns);
	
	foreach (tuix.sortedItemIds as i => id) {
		item = tuix.items[id];
		item.id = id;
		
		tuix.sortedItems.push(item);
		
		item.sortedItemButtons = [];
		
		foreach (tuix.sortedItemButtonIds as j => jd) {
			button = _.clone(tuix.item_buttons[jd]);
			button.id = jd;
			this.setupButtonLinks(slotName, button, id);
			
			if (firstItem) {
				tuix.sortedItemButtons.push(button);
			}
			
			if (!this.hidden(button, item, jd)) {
				item.sortedItemButtons.push(button);
			}
		}
		
		firstItem = false;
	}
	foreach (tuix.sortedCollectionButtonIds as i => id) {
		button = tuix.collection_buttons[id];
		button.id = id;
		
		if (this.hidden(button, undefined, id)) {
			continue;
		}
		
		this.setupButtonLinks(slotName, button);
		
		if (!this.hidden(button, item, jd)) {
			tuix.sortedCollectionButtons.push(button);
		}
	}
	
	this.last[slotName].tuix = tuix;
};

methods.setupButtonLinks = function(slotName, button, itemId) {
	slotName = getSlotnameFromEl(slotName);
	
	var page,
		request,
		onclick;
	
	if (button.go
	 || button.ajax
	 || button.confirm) {
		
		onclick = this.globalName + ".button(this, '" + jsEscape(slotName) + "', '" + jsEscape(button.id) + "'";
		
		if (itemId) {
			onclick += ", '" + jsEscape(itemId) + "'";
			
			if (button.go) request = this.checkRequests(button.go, true, {id: itemId});
		} else {
			onclick += ", undefined";
			
			if (button.go) request = this.checkRequests(button.go, true);
		}
		
		if (button.onclick) {
			onclick += ", function () {" + button.onclick + "}";
		}
		
		onclick += "); return false;";
		
		button.onclick = onclick;
	}
	
	//Check if this button has a "go" link
	if (button.href === undefined) {
		if (request) {
			//Check if the link should be directed to a different page. If so, just include a href and don't set an onclick
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

methods.button = function(el, slotName, buttonName, itemId, onclickFun, confirmed) {
	if (this.loading) {
		return;
	}
	
	delete this.transitionInOnNextScreen;
	
	slotName = getSlotnameFromEl(slotName);
	
	var that = this,
		button,
		item = false,
		last = this.last[slotName],
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
	 && (!this.hidden(confirm, itemId, buttonName))) {
		
		if (item) {
			confirm = _.extend({}, confirm);
			confirm.message = zenario.applyMergeFields(confirm.message, item);
		}
		
		this.confirm(confirm, function() {
			that.button(el, slotName, buttonName, itemId, onclickFun, true);
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
			request = this.checkRequests(button.ajax.request, false, {id: itemId}, true);
			this.command(slotName, request, last.request);

		} else {
			if (button.transition_out) {
				this.transitionOut(button.transition_out);
			}
			if (button.transition_in_on_next_screen) {
				this.transitionInOnNextScreen = button.transition_in_on_next_screen;
			}
			this.go(slotName, button.go, itemId);
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
					$td = $(get('zfea_' + this.slotName + '_row_' + ii + '_col_' + ci)),
					data = item[col.id],
					chart = {};
				//chart.type = '...';
				
				if (!data.values) {
					var columnId = 'zfea_' + this.slotName + '_row_' + ii + '_col_' + ci;
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

	});
};









methods.transitionIn = function(transition_in) {
	var $slot = $('#plgslt_' + this.slotName),
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
	
	var $slot = $('#plgslt_' + this.slotName),
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


methods.command = function(slotName, request, goAfter) {
	if (this.loading) {
		return;
	}
	
	slotName = getSlotnameFromEl(slotName);
	
	$.colorbox.remove();
	
	var that = this,
		url = zenario.pluginAJAXLink(this.moduleClassName, slotName);
	
	this.showLoader();
	this.ajax(url, request).after(function(resp) {
		that.hideLoader();
	
		if (resp) {
			that.AJAXErrorHandler(resp);
		} else {
			that.go(slotName, goAfter);
		}
	});
};


methods.phrase = function(text, mrg) {
	
	var moduleClassNameForPhrases =
		zenario.slots[this.slotName]
	 && zenario.slots[this.slotName].moduleClassNameForPhrases;
	
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
		return this.showSingleSlotLink(page.slotName, request, false, page.cID, page.cType);
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




	



}, zenario.getSlotnameFromEl, window.zenarioGrid || {});