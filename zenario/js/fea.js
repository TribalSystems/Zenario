/*
 * Copyright (c) 2026, Tribal Limited
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
	zenarioFEA
) {
	"use strict";



var methods = methodsOf(zenarioFEA);


methods.idVarName = function() {
	return thus.specifiedIdVarName || 'id';
};

//Small hack here. This method just exists so we can run "if (lib.isFEA)" in the code.
methods.isFEA = function() {};

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


methods.submitForm = function(confirm) {
	if (thus.ffoving < 4) {
		thus.ffoving = 4;
		thus.save(confirm);
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
methods.save = function(confirm) {
	return thus.ffov('save', confirm);
};

methods.setData = function(data) {
	thus.setDataDiff(data);
};

methods.sendStateToServer = function() {
	return thus.sendStateToServerDiff();
};

methods.visitorTUIXLink = function(requests, mode) {
	if (thus.noPlugin) {
		return zenario.visitorTUIXLink(thus.moduleClassName, thus.path, requests, mode);
	} else {
		return zenario.pluginVisitorTUIXLink(thus.moduleClassName, thus.containerId, thus.path, requests, mode);
	}
};

methods.setURLForSingleRequests = function() {
	return thus.url = thus.visitorTUIXLink(thus.request);
};

methods.setURLForSyncedRequests = function(action) {
	return thus.url = thus.visitorTUIXLink(thus.request, action, true);
};


methods.modifyPostOnLoad = function() {
	return false;
};

methods.sendsSignalOnEvent = function(eventName) {
	return thus.tuix
		&& thus.tuix.send_signals_on_events
		&& thus.tuix.send_signals_on_events[eventName];
};


methods.ffov = function(action, confirm) {
	
	var cb = new zenario.callback,
		url = thus.setURLForSyncedRequests(action),
		post,
		goneToURL = false;
	
	if (action == 'fill') {
		post = thus.modifyPostOnLoad();
	} else {
		thus.prevPath = thus.path;
		thus.checkValues();
		post = {_cms_formatAction: true, _cms_tuix: thus.sendStateToServer()};
		
		if (confirm) {
			post._cms_confirm = 1;
		}
	}
	
	if (!thus.loading) {
		thus.showLoader();
		
		var after = function(tuixToMergeIn) {
			thus.hideLoader();
			
			zenarioT.checkDumps(tuixToMergeIn);
			
			var js,
				runAfter,
				runAfterString,
				tuix,
				go = tuixToMergeIn.go,
				closePopout = thus.inPopout && tuixToMergeIn.close_popout,
				closing = go || closePopout;
			
			if (action == 'fill') {
				tuix = thus.tuix = tuixToMergeIn;
				
				js = tuix.js_before_first_load,
				runAfter = tuix.js_on_first_load;
			
			} else {
				thus.setData(tuixToMergeIn);
				tuix = thus.tuix;
				
				if (closing) {
					js = tuix.js_on_save;
				} else {
					runAfter = tuix.js_on_reformat;
				}
			}
		
			//Reload the opener if the reload_parent flag was set
			if (tuix.reload_parent) {
				thus.reloadParent();
			}
			
			//Allow FEA plugins to call a functions or methods to run before a FEA plugin is displayed.
			if (js) {
				zenarioT.run(js, thus);
			}
			
			//Allow FEA plugins to call a functions or methods to run after a FEA plugin has finished displaying.
			if (runAfter && !_.isFunction(runAfter)) {
				runAfterString = runAfter;
				
				runAfter = function() {
					zenarioT.run(runAfterString, thus);
				};
			}
			
			//If this is a popout, and the close_popout flag was set, close it
			if (closePopout) {
				thus.closePopout();
			}
			
			//If the stop_flow property was set, don't do a go() or a draw().
			if (tuix.stop_flow) {
				return;
			}
			
			
			if (go) {
				thus.go(go, undefined, undefined, runAfter);
			
			} else if (tuix.go_to_url && !goneToURL) {
				zenario.goToURL(tuix.go_to_url);
				goneToURL = true;
				
				//Set a timeout to re-liven the form, just in case the URL was a redirect to a download which wouldn't
				//unload the current window.
				setTimeout(function() {
					after(tuix);
				}, 350);
			
			} else {
				thus.drawForm();
				cb.done();
				
				var signalName;
				
				//Send signals if the flags to do so are enabled in the TUIX properties
				if (action == 'format' && (signalName = thus.sendsSignalOnEvent('formFormat'))) {
					zenario.sendSignal(signalName, thus.getFieldValues(true));
				}
				if (action == 'validate' && (signalName = thus.sendsSignalOnEvent('formValidate'))) {
					zenario.sendSignal(signalName, thus.getFieldValues(true));
				}
				if (action == 'save' && (signalName = thus.sendsSignalOnEvent('formSave'))) {
					zenario.sendSignal(signalName, thus.getFieldValues(true));
				}
			
				//Scroll to the top of the slot on a "format" or "validate" event
				if ((action == 'format' || action == 'validate') && tuix.scroll_to_top_of_slot_on_format_or_validate) {
					zenario.scrollToSlotTop(thus.containerId, true);
				}
				
				if (action == 'save' && tuix.scroll_after_save) {
					zenario.scrollToSlotTop(thus.containerId, true);
				}
				
				if (runAfter) {
					runAfter();
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
	
	var signalName;
	
	//Check the TUIX properties to see if we need to send the formRedraw signal,
	//and send it if so.
	if (signalName = thus.sendsSignalOnEvent('formRedraw')) {
		zenario.sendSignal(signalName, thus.getFieldValues(true));
	}
};


methods.draw2 = function() {
	thus.sortTabs();
	
	thus.cb = new zenario.callback;
	thus.putHTMLOnPage(thus.drawFields(thus.cb, thus.mtPrefix + '_form'));
	
	var DOMlastFieldInFocus;
	
	if (thus.path == thus.prevPath
	 && thus.lastFocus
	 && thus.lastFocus.id != ''
	 && (DOMlastFieldInFocus = thus.get(thus.lastFocus.id))) {
		DOMlastFieldInFocus.focus();
	
	} else {
		thus.focusFirstField();
	}
	
	thus.sendSignalAfterRedraw();
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
	
	if (zenarioT.showDevTools()) {
		thus._cms_formHTML = html;
	}
	
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

methods.openCustomPopout = function(options, id) {

	var popout = _.extend({}, options);
	
	if (id) {
		popout.href = zenario.addRequest(popout.href, 'id', id);
	}

	zenarioT.action(thus, {
		popout: popout
	});
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

//Allow the plugin to change which microtemplate is used to for the main display using a TUIX property
methods.microTemplate = function(template, data, filter, preMicroTemplate, postMicroTemplate) {
	
	var html, cusTemplate, cusTemplateApplied = false;
	
	if (!thus._cms_customTemplateApplied
	 && (template == 'fea_list' || template == 'fea_form')
	 && (cusTemplate = thus.tuix && thus.tuix.microtemplate)) {
		template = cusTemplate;
		thus._cms_customTemplateApplied = cusTemplateApplied = true;
	}
	
	html = methodsOf(zenarioF).microTemplate.call(thus, template, data, filter, preMicroTemplate, postMicroTemplate);
	
	if (cusTemplateApplied) {
		delete thus._cms_customTemplateApplied;
	}
	
	return html;
};


methods.displayDevTools = function() {
	
	if (!thus.tsLink && !zenarioT.showDevTools()) {
		return '';
	}
	
	var sel = '#fea_dev_tools_' + thus.containerId,
		$devToolsContainer = $(sel),
		html = thus.pMicroTemplate('dev_tools', {});
	
	if ($devToolsContainer.length) {
		$devToolsContainer.html(html).show();
		thus.addJQueryElements(sel);
		return '';
	} else {
		return html;
	}
};

methods.on = function(eventName, handler) {
	zenario.on(false, thus.containerId, eventName, handler);
};

methods.off = function(eventName) {
	zenario.off(false, thus.containerId, eventName);
};

methods.registeredEvents = function(eventName) {
	return zenario.registeredEvents(false, thus.containerId, eventName);
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
		$loader = $(thus.pMicroTemplate('loading', {loadingId: loadingId}));
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

methods.enabled = function(option) {
	return thus.tuix.enable && thus.tuix.enable[option];
};

methods.runLogic = function(request, callWhenLoaded) {
	
	thus.request = request;
	
	thus.doAjaxLoadThenShowPlugin(request, callWhenLoaded);
};

methods.typeOfLogic = function() {
	var path = thus.request.path || thus.path,
		moduleClassName = thus.moduleClassName,
		module = window[moduleClassName],
		feaType;
	
	if (!module) {
		throw ('Error in FEA init function, the module class "' + moduleClassName + '" does not exist.');
	}
	if (!path) {
		throw ('Error in FEA init function, path is not set in the request.');
	}
	
	feaType = module.feaPaths[path];
	
	if (!defined(feaType)) {
		throw ('Error in FEA init function, the path "' + path + '" does not exist. You may need to put your site into developer mode and/or clear the site cache.');
	}
	
	return feaType;
};

methods.loadData = function(request, json, TUIXSnippetLink) {
	
	thus.request = request;
	thus.tuix = zenarioT.parse(json);
	
	thus.last = {request: request};
	thus.prevPath = thus.path;
	
	if (TUIXSnippetLink) {
		thus.tsLink = TUIXSnippetLink;
	}
	
	//setTimeout() is used as a hack to ensure the conductor is fully loaded first
	
	$(document).ready(function() {
		thus.loadingDoneInAdvanceSoDrawPlugin();
	});
};

methods.doAjaxLoadThenShowPlugin = function(request, callWhenLoaded) {
	
	delete thus._cms_formHTML;
	
	switch (thus.typeOfLogic()) {
		case 'list':
			thus.doAjaxLoadThenShowList(callWhenLoaded);
			break;
		
		case 'form':
			thus.doAjaxLoadThenShowForm(callWhenLoaded);
			break;
		
		case 'dash':
			thus.doAjaxLoadThenShowDash(callWhenLoaded);
			break;
		
		case 'graph':
			thus.doAjaxLoadThenShowGraph(callWhenLoaded);
			break;
		
		//Assume just a normal plugin if nothing matches
		default:
			zenario.refreshPluginSlot(thus.containerId, 'lookup', request);
	}
};

methods.loadingDoneInAdvanceSoDrawPlugin = function() {
	var typeOfLogic = thus.typeOfLogic(),
		tuix = thus.tuix || {},
		js = thus.tuix.js_before_first_load,
		runAfter = thus.tuix.js_on_first_load;
	
	//Allow FEA plugins to call a functions or methods to run before a FEA plugin is displayed.
	if (js) {
		zenarioT.run(js, thus);
	}
	
	delete thus._cms_formHTML;
	
	switch (typeOfLogic) {
		case 'list':
			thus.setURLForSingleRequests();
			thus.drawList();
			break;
	
		case 'form':
			thus.setURLForSyncedRequests('fill');
			thus.drawForm();
			break;
		
		case 'dash':
			thus.setURLForSyncedRequests('fill');
			thus.drawDash();
			break;
		
		case 'graph':
			thus.setURLForSyncedRequests('fill');
			thus.drawGraph();
			break;
		
		default:
			console.error('"' + typeOfLogic + '" is not a valid value for the fea_type property. (If this value is out of date, you may need to clear the site cache.');
	}
	
	//Allow FEA plugins to call a functions or methods to run after a FEA plugin has finished displaying.
	if (runAfter) {
		zenarioT.run(runAfter, thus);
	}
};

methods.redraw = function() {
	var typeOfLogic = thus.typeOfLogic()
	
	switch (typeOfLogic) {
		case 'list':
			thus.drawList();
			break;
	
		case 'form':
			thus.drawForm();
			break;
		
		case 'dash':
			thus.drawDash();
			break;
		
		case 'graph':
			thus.drawGraph();
			break;
	}
};




methods.doAjaxLoadThenShowList = function(callWhenLoaded) {
	if (thus.loading) {
		return;
	}
	
	var url = thus.setURLForSingleRequests();
	
	thus.showLoader();
	thus.ajax(url, false, true).after(function(tuix) {
		
		zenarioT.checkDumps(tuix);
	
		thus.tuix = tuix;
		
		thus.drawList();
		
		
		var js = tuix.js_on_reformat;
		if (js) {
			zenarioT.run(js, thus);
		}
		
		if (callWhenLoaded) {
			callWhenLoaded();
		}
		
	});
};

methods.doAjaxLoadThenShowForm = function(callWhenLoaded) {
	thus.changed = {};
	thus.fill().after(callWhenLoaded);
};

methods.doAjaxLoadThenShowDash = function(callWhenLoaded) {
	thus.fillDash().after(callWhenLoaded);
};

methods.doAjaxLoadThenShowGraph = function(callWhenLoaded) {
	thus.fillGraph().after(callWhenLoaded);
};

methods.fillDash = function() {
	return thus.feaAJAX('fill', 'dash');
};

methods.fillGraph = function() {
	return thus.feaAJAX('fill', 'graph');
};

methods.formatDash = function() {
	return thus.feaAJAX('format', 'dash');
};

methods.formatGraph = function() {
	return thus.feaAJAX('format', 'graph');
};

methods.dashAJAX = function(action) {
	return thus.feaAJAX(action, 'dash');
};

methods.graphAJAX = function(action) {
	return thus.feaAJAX(action, 'graph');
};

methods.feaAJAX = function(action, typeOfLogic) {
	
	var cb = new zenario.callback,
		url = thus.setURLForSyncedRequests(action),
		post,
		goneToURL = false;
	
	if (action == 'fill') {
		post = thus.modifyPostOnLoad();
	} else {
		thus.prevPath = thus.path;
		thus.checkValues();
		post = {_cms_formatAction: true, _cms_tuix: thus.sendStateToServer()};
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
			
			switch (typeOfLogic) {
				case 'dash':
					thus.drawDash();
					break;
		
				case 'graph':
					thus.drawGraph();
					break;
			}
			cb.done();
		};
		
		thus.ajax(url, post, true).after(after);
	}
	
	return cb;
};





methods.drawForm = function() {
	thus.sortOutTUIX();
	thus.draw();
	
	thus.registerSignalHandlers();
	
	//If we're redrawing the form because the user tried to save but the server wanted a
	//confirmation, show the confirm box so the user can confirm.
	var tuix = thus.tuix;
	if (tuix._cms_showConfirm) {
		thus.confirm(tuix.confirm, function() {
			thus.submitForm(true);
		});
	}
};




methods.drawList = function() {
	
	thus.sortOutTUIX();
	
	var page = 1 * thus.tuix._cms_page,
		pageSize = 1 * thus.tuix._cms_pageSize,
		itemCount = 1 * thus.tuix._cms_itemCount,
		items = thus.tuix.items,
		ii, item, itemId, paginationId,
		ci, col;
	
	
	if (thus.hasBypass
	 && items
	 && itemCount === 1) {
		foreach (items as itemId => item) {
			thus.button(this, thus.hasBypass, item, itemId);
			return;
		}
	}
	 
	
	thus.cb = new zenario.callback;
	thus.putHTMLOnPage(thus.pMicroTemplate('list', {}));
	
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
			
			withSlider: true,
			withAcceleration: true,
			speed: 2,
			coeffAcceleration: 2,
			
			onPageClicked: function(a,num) { 
				thus.setVarAndReload('page', num);
			}
		});
	}
	

	
	//Add any sparkline charts in the list
	foreach (thus.sortedColumns as ci => col) {
		if (col.sparkline) {
			
			foreach (thus.sortedItems as ii => item) {
				
				
				var graph = Highcharts.merge(col.sparkline, item[col.id] || {}),
					$td = $(get('zfea_' + thus.containerId + '_row_' + ii + '_col_' + ci));
				
				//Quality of life feature to allow merging on series data.
				//Allow series to be defined in an associative array, but convert to a standard array
				//at this point as Highcharts doesn't support associative arrays.
				if (defined(graph.series)
				 && _.isObject(graph.series)
				 && !_.isArray(graph.series)) {
					graph.series = _.toArray(graph.series);
				}
				
				$td.highcharts('SparkLine', graph);
			}
		}
	}
	
	
	
	if (thus.tuix.map) {
		thus.initMap();
	}

	thus.hideLoader();
	
	thus.registerSignalHandlers();
};



methods.drawDash = function() {
	
	thus.dashCustomSetup();
	
	thus.sortOutTUIX();
	thus.drawDashHTML();
	
	thus.registerSignalHandlers();
};

methods.drawDashHTML = function() {
	thus.cb = new zenario.callback;
	thus.putHTMLOnPage(thus.microTemplate(thus.tuix.microtemplate, {}));
};

methods.dashCustomSetup = function() {
	//...
};

methods.drawGraph = function() {
	
	thus.graphCustomSetup();
	
	thus.sortOutTUIX();
	
	thus.cb = new zenario.callback;
	
	
	
	//We currently have two slightly different ways to implement a graph:
	
	//1. A simple option that just display a graph, but does not live update.
	//This uses the graph property in TUIX.
	
	//2. A more advanced graph that supports adding/removing axis or changing the date/time
	//range dynamically.
	//This uses the always_sync_this_data_between_client_and_server and
	//never_sync_this_data_between_client_and_server properties in TUIX.
	
	
	//Write the code to implement the simple version first.
	var tuix = thus.tuix,
		graph = tuix.graph;
	
	if (graph) {
		//Draw the HTML using the microtemplate.
		//(Needs to be done before we try and initialise the graph as the <idv> for the graph will need to exist in the DOM first.)
		thus.putHTMLOnPage(thus.microTemplate(tuix.microtemplate || 'fea_graph', {}));
		
		//Call highcharts to render the graph
		Highcharts.chart(graph);
		
		//And that's it for the simple version.
		return;
	}
	
	
	//Here's the code to support the advanced version.
	if (!thus._cms_graphSeriesData) {
		thus._cms_graphSeriesData = {};
	}
	
	var seriesData = thus._cms_graphSeriesData,
		sync = tuix.always_sync_this_data_between_client_and_server,
		noSync = tuix.never_sync_this_data_between_client_and_server,
		incomingSeriesData = noSync && noSync.seriesData || {},
		incomingGraph = sync.graph,
		existingGraph = thus._cms_graphData,
		thingsToCheck = ['yAxis', 'series'], ti, thing, i, ob, id, data;
	
	//Update the series data we have with anything new sent from the server
	foreach (incomingSeriesData as id => data) {
		seriesData[id] = data;
	}
	
	//Create a shallow-copied clone so I can change some properties without
	//messing with the sync tech, or with what you see in the dev tools
	incomingGraph = _.clone(incomingGraph);
	
	//Add a custom formatting function for legend items.
	//If the field names are in the format "Asset: Field name",
	//then I only want to show the "Asset:" bit on the first field.
	if (incomingGraph.legend && incomingGraph.legend.zenario_format_asset_names_and_fields) {
		incomingGraph.legend = _.clone(incomingGraph.legend);
		
		var lastAssetName;
		
		incomingGraph.legend.labelFormatter = function () {
			var splitter = ': ',
				split = (this.name + '').split(splitter),
				thisAssetName = split.shift(),
				thisFieldName = split.join(splitter);
			
			if (lastAssetName !== thisAssetName) {
				lastAssetName = thisAssetName;
				
				thisFieldName = thisAssetName + splitter + thisFieldName;
			}
            
            return thisFieldName;
        }
	}
	
	//Add the series data from the "noSync" section
	if (!_.isEmpty(incomingGraph.series)) {
		incomingGraph.series = _.clone(incomingGraph.series);
		
		foreach (incomingGraph.series as id) {
			if (seriesData[id]) {
				incomingGraph.series[id] = _.clone(incomingGraph.series[id]);
				incomingGraph.series[id].data = seriesData[id];
				
				//N.b. this might be needed if the line above doesn't work!
				//incomingGraph.series[id].data = _.toArray(seriesData[id]);
			}
		}
	}
	
	//Catch some cases where the sync function has been known to turned an array into
	//an object, and try to restore a proper array
	foreach (thingsToCheck as ti => thing) {
		if (thing) {
			if ((typeof incomingGraph[thing] === 'object') && !_.isArray(incomingGraph[thing])) {
				incomingGraph[thing] = _.toArray(incomingGraph[thing]);
			}
		}
	}
	
	
	//This code would hide the graph until there was least one y-axis, and at least one series.
	//I wrote it to try and reduce bugs, but right now things do seem to be working fine
	//if I show an empty graph.
	//if (_.isEmpty(incomingGraph.yAxis)
	// || _.isEmpty(incomingGraph.series)) {
	//	
	//	if (existingGraph) {
	//		existingGraph.destroy()
	//	}
	//	delete thus._cms_graphData;
	//	
	//	return;
	//}
	
	
	//Draw the HTML using the microtemplate.
	//(Needs to be done before we try and initialise the graph as the <div> for the graph will need to exist in the DOM first.)
	thus.putHTMLOnPage(thus.microTemplate(tuix.microtemplate, {}));
	
	
	//If the graph didn't previously exist on the page, initialise it
	if (!existingGraph) {
		thus._cms_graphData = Highcharts.chart(incomingGraph);

	} else {
		//Otherwise, update the existing graph dynamically
		foreach (thingsToCheck as ti => thing) {
			var incoming = {}, existing = {};
		
			foreach (existingGraph[thing] as i => ob) {
				id = ob.options.id;
				existing[id] = true;
			}
	
			foreach (incomingGraph[thing] as i => ob) {
				id = ob.id;
				incoming[id] = ob;
			}
		
			foreach (existing as id) {
				if (!incoming[id]) {
					existingGraph.get(id).remove();
				}
			}
	
			foreach (incomingGraph[thing] as i => ob) {
				id = ob.id;
				if (!existing[id]) {
				
					switch (thing) {
						case 'series':
							//https://api.highcharts.com/class-reference/Highcharts.Chart#addSeries
							//addSeries(options [, redraw] [, animation])
							//console.log('addSeries', ob, false);
							existingGraph.addSeries(ob, false);
							break;
					
						case 'yAxis':
							//https://api.highcharts.com/class-reference/Highcharts.Chart#addAxis
							//addAxis(options [, isX] [, redraw] [, animation])
							//console.log('addAxis', ob, false, false);
							existingGraph.addAxis(ob, false, false);
							break;
					}
				}
			}
		}
	
		//https://api.highcharts.com/class-reference/Highcharts.Chart#update
		//update(options [, redraw])
		//console.log('update', incomingGraph);
		existingGraph.update(incomingGraph);
	}
	
	thus.registerSignalHandlers();
};

methods.graphCustomSetup = function() {
	//...
};


//Check the handle_signals property name, and see if any signal handlers need to be registered.
methods.registerSignalHandlers = function() {
	
	var si, signal, signals = zenarioT.tuixToArray(thus.tuix.handle_signals),
		signalOnRegister;
	
	//Only run this if there are signals defined, and only run this once per instance.
	if (defined(signals) && !thus._cms_eventsAdded) {
		thus._cms_eventsAdded = true;
		
		//Don't be too picky with the format of the handle_signals property.
		signals = zenarioT.tuixToArray(thus.tuix.handle_signals);
		
		//Loop through each signal.
		if (signals !== []) {
			foreach (signals as si => signal) {	
				
				//Have the option to call a method when the signal is registered.
				signalOnRegister = signal + 'OnRegister';
				if (_.isFunction(thus[signalOnRegister])) {
					thus[signalOnRegister]();
				}
				
				if (_.isFunction(thus[signal])) {
					//This function call here is to create a local copy of the signal variable
					//that won't be updated by the next step of the for loop.
					(function(signal) {
						//And *this* function call here is to keep the this/thus variable still
						//pointing to the instance.
						thus.on(signal, function(data, callingLib) {
							thus[signal](data, callingLib);
						});
					})(signal);
				}
			}
		}
	}
};







methods.initMap = function() {
	
	var gMap,
		tuix = thus.tuix,
		locations = _.toArray(tuix.items),
		mapOptions = tuix.map.options,
		containerId = thus.containerId,
		googleMapsApiKey = tuix.map.api_key;
	
	//$('#refresh_button').click(function(){
	//	var mapZoom = gMap.getZoom();
	//	var mapCenter = gMap.getCenter();
	//	var mapLat = mapCenter.lat();
	//	var mapLng = mapCenter.lng();
	//	var request = 'mode=map_of_locations'+'&map_zoom='+mapZoom+'&map_lat='+mapLat+'&map_lng='+mapLng;
	//	
	//	zenario.refreshPluginSlot(containerId, 'lookup', false,false,false,false,false,request);
	//});
	
	
	var runWhenMapsLoaded = function() {
		var i, coordsExist = false;
		if(locations.length > 0) {
			for (i = 0; i < locations.length; ++i) {
				if (locations[i].latitude && locations[i].longitude) {
					coordsExist = true;
					break;
				}
			}
		}
	
		if(coordsExist){
			mapOptions = {
				scrollwheel: false,
				draggable: true
			}
		
			if(mapOptions.map_zoom && mapOptions.map_lat && mapOptions.map_lng ){
				mapOptions.zoom = mapOptions.map_zoom;
				mapOptions.center = new google.maps.LatLng(mapOptions.map_lat, mapOptions.map_lng);
			}

			var latitude, longitude;
			var bounds = new google.maps.LatLngBounds();
			gMap = new google.maps.Map(document.getElementById('map_' + containerId), mapOptions);
			var infowindow = new google.maps.InfoWindow();
			var contentString;
		
			for(i = 0; i < locations.length; ++i){
				latitude = locations[i]['latitude'];
				longitude = locations[i]['longitude'];
			
				if (latitude && longitude) {
					latlng = new google.maps.LatLng(latitude,longitude);
					marker = new google.maps.Marker({
						position: latlng,
						map: gMap,
						clickable: true
					});
				
					bounds.extend(latlng);
				
					//HTML for InfoWindow
					contentString = $('#' + containerId + ' .item_' + locations[i].id).html();
				
					google.maps.event.addListener(marker,'click', (function(marker,contentString,infowindow){ 
						return function() {
							infowindow.setContent(contentString);
							infowindow.open(gMap,marker);
						};
					})(marker,contentString,infowindow)); 	
				}
			}
			if(!mapOptions.map_zoom){
				// Keep zoom at appropriate level of detail when fitting bounds
				google.maps.event.addListenerOnce(gMap, 'bounds_changed', function() { 
					this.setZoom(Math.min(15, this.getZoom())); 
				});
				gMap.fitBounds(bounds);
			}
		}else{
			var map = new google.maps.Map(document.getElementById('map_' + containerId), {
				center: {lat: 52.482780, lng: -1.362305},
				zoom: 2,
				scrollwheel: false,
				draggable: false,
				disableDefaultUI: true
			});
			$('#no_locations_' + containerId).show();
		}
	};
	
	
	
	
	if (typeof google === 'object' && typeof google.maps === 'object') {
		runWhenMapsLoaded();
	
	} else {
		var callback = thus.globalName + '__runWhenMapsLoaded';
		window[callback] = runWhenMapsLoaded;
		
		var script = document.createElement('script');
		script.type = 'text/javascript';
		script.src = 'https://maps.googleapis.com/maps/api/js?v=3&key=' + googleMapsApiKey + '&callback=' + callback;
		document.body.appendChild(script);
	}
};







methods.getPathFromMode = function(mode) {
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
	
	
	idVarName = request.id_var_name || thus.idVarName(thus.mode) || 'id';
	
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
	thus.slotName = zenario.getSlotnameFromEl(containerId);
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



methods.typeaheadSearchEnabled = function(field, id, tab) {
	
	var pick_items = field.pick_items;
	
	return pick_items && pick_items.enable_type_ahead_search;
};

methods.typeaheadSearchAJAXURL = function(field, id, tab) {
	
	return thus.visitorTUIXLink(_.extend({_cms_currentTab: tab, _cms_field: id}, thus.tuix.key), 'tas');
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



methods.setSearchAndReloadIfNeeded = function(search, conductorSearchVar) {
	
	conductorSearchVar = conductorSearchVar || 'search';
	
	var prevSearch = zenario_conductor.getVar(thus.containerId, conductorSearchVar) || '',
		newSearch = zenario.pack(search) || '';
	
	if (prevSearch != newSearch) {
		thus.setVarAndReload('search', newSearch);
	}
};




methods.lookupFileDetails = function(fileId) {
	return false;
};


methods.debug = function(el, e) {
	if (thus.path
	 && thus.tuix
	 && thus.url) {
		zenarioA.debug(el, e, thus.globalName);
	}
};

methods.wrapperClassName = function() {
	return 'zfea zfea_' + thus.path +
		' ' + (thus.tuix.css_class || '') +
		' ' + (thus.tsLink? ' zfea_with_tslink' : '') +
		' ' + (zenarioT.showDevTools()? ' zfea_with_dev_tools' : '');
};


methods.newSimpleForm = function() {
	if (!thus.form) {
		thus.form = zenarioT.newSimpleForm(thus.containerId);
		thus.form.parentLib = thus;
	}
	
	return thus.form;
};

methods.drawSimpleForm = function(tuix, cb) {
	thus.newSimpleForm();
	return thus.form.drawTUIX(tuix, 'fea_simple_form', cb || thus.cb);
};


methods.getSearchFieldValue = function() {
	var domSearch = get('search_' + thus.containerId);
	
	return (domSearch ? domSearch.value: false);
};


methods.setVar = function(name, value, updateURL) {
	zenario_conductor.setVar(thus.containerId, name, value, updateURL);
	thus.request[name] = value;
};


methods.setVarAndReload = function(name, value, e) {
	zenario.stop(e);
	
	//Automatically clear any pagination when changing filters
	if (name !== 'page' && thus.request.page) {
		thus.setVar('page', '');
	}
	
	thus.setVar(name, value, true);
	
	thus.doAjaxLoadThenShowPlugin();
};

methods.doSearch = function(e, searchValue) {
	if (!defined(searchValue)) {
		searchValue = thus.getSearchFieldValue();
	}
	
	thus.setVarAndReload('search', searchValue, e);
	
	return false;
};

//Handle changing the sort order of a column
methods.changeSortCol = function(colId, sortDesc) {
	var columns = thus.tuix.columns || {},
		col = columns[colId] || {},
		canSortAsc = col.sort_asc,
		canSortDesc = col.sort_desc;
	
	if (canSortAsc || canSortDesc) {
		
		//Check if only one sort order is allowed on this column.
		//In that case, the column should always be sorted in that direction.
		if (canSortAsc && !canSortDesc) {
			sortDesc = 0;
		
		} else if (!canSortAsc && canSortDesc) {
			sortDesc = 1;
		
		//If the caller wants a specific direction, make sure we use that.
		} else if (defined(sortDesc)) {
		
		//Check if we're already sorting on this column. If this is the case, then flip the sort order
		} else if (thus.key('sortCol') == colId) {
			sortDesc = engToBoolean(!thus.key('sortDesc'));
		
		//Otherwise use the default option for the column
		} else {
			sortDesc = engToBoolean(col.sort_desc_by_default);
		}
		
		//console.log({canSortAsc, canSortDesc, colId, sortDesc});
		
		thus.setVar('sortDesc', sortDesc);
		thus.setVarAndReload('sortCol', colId);
	}
};


methods.checkThingEnabled = function(thing) {
	return thus.tuix.enable && thus.tuix.enable[thing];
};

methods.sortingEnabled = function(thing) {
	return thus.checkThingEnabled('sort_list') || thus.checkThingEnabled('sort_col_headers');
};


methods.go = function(request, itemId, wasInitialLoad, runAfter) {
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
		zenario_conductor.go(containerId, command, request, runAfter);
	
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
			if (runAfter) {
				runAfter();
			}
		});
	}
};


methods.itemButtonIsntHidden = function(button, itemIds, isCheckboxSelect) {
	
	var i, item, itemId,
		met = false,
		maxItems = 1, 
		minItems = 0,
		numItems = isCheckboxSelect? itemIds.length : 0;
	
	//Check all of the itemIds in the request actually exist
	foreach (itemIds as i => itemId) {
		if (!(item = thus.tuix.items[itemId])) {
			return false;
		}
		
		//Check if the button is not flagged as hidden on this item
		if (thus.tuix._cms_hiddenItemButtons
		 && thus.tuix._cms_hiddenItemButtons[button.id]
		 && thus.tuix._cms_hiddenItemButtons[button.id][itemId]) {
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
		foreach (itemIds as i) {
			item = thus.tuix.items[itemIds[i]];
			
			if (!zenarioT.eval(button.visible_if_for_all_selected_items, thus, undefined, item, button.id, button)) {
				return false;
			}
		}
	}
	
	if (defined(button.visible_if_for_any_selected_items)) {
		foreach (itemIds as i) {
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
			foreach (itemIds as i) {
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
		
			foreach (itemIds as i) {
				item = thus.tuix.items[itemIds[i]];
			
				if (zenarioT.eval(button.disabled_if_for_any_selected_items, thus, undefined, item, button.id, button)) {
					break doLoop;
				}
			}
		}
	
		if (defined(itemIds)
		 && defined(button.disabled_if_for_all_selected_items)) {
		
			foreach (itemIds as i) {
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
	button._cms_buttonIsDisabled = true;
	button.tooltip = button.disabled_tooltip || button.tooltip;
	
	return false;
};


methods.columnVisibleForItem = function(columnId, itemId) {
	
	var column = thus.tuix.columns[columnId] || {},
		item = thus.tuix.items[itemId] || {};
	
	if (thus.tuix._cms_hiddenColumns
	 && thus.tuix._cms_hiddenColumns[columnId]
	 && thus.tuix._cms_hiddenColumns[columnId][itemId]) {
		return false;
	}
	
	if (column.hide_if_empty && !item[columnId]) {
		return false;
	}
	
	if (defined(column.visible_if_for_each_item) && zenarioT.eval(column.visible_if_for_each_item, thus, undefined, item, itemId, undefined, column)) {
		return false;
	}
	
	return true;
};


methods.hidden = function(tuixObject, item, id, button, column, field, section, tab) {
	
	if (thus.debugRevealAllObjects) {
		return false;
	}
	
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
	
	thus.hasBypass = undefined;
	thus.newlyNavigated = thus.path != thus.prevPath;
	thus.multiSelectButtonsExist = false;
	
	//Make sure some objects are defined here.
	//This is a small hack to save a lot of extra code & effort checking if these are defined everytime we want to check them.
	tuix.items = tuix.items || {};
	tuix.columns = tuix.columns || {};
	tuix.item_buttons = tuix.item_buttons || {};
	tuix.collection_buttons = tuix.collection_buttons || {};
	//Note there is a small issue with this; these then appear in the dev tools even when they're not actually in the code!
	
	
	var i, id, j, itemButton, childItemButton, col, item, button, sortedItemIds,
		sortedButtonsAndColumnButtons,
		numberofItemsShown = _.size(tuix.items);
	
	thus.sortedCollectionButtonIds = zenarioT.getSortedIdsOfTUIXElements(tuix, tuix.collection_buttons);
	thus.sortedCollectionButtons = [];
	thus.visibleCollectionButtons = {};
	thus.sortedItemButtonIds = zenarioT.getSortedIdsOfTUIXElements(tuix, tuix.item_buttons);
	thus.sortedItemButtons = [];
	thus.sortedColumnIds = zenarioT.getSortedIdsOfTUIXElements(tuix, tuix.columns);
	thus.sortedColumns = [];
	thus.visibleColumns = {};
	
	
	foreach (thus.sortedCollectionButtonIds as i => id) {
		button = _.clone(tuix.collection_buttons[id]);
		button.id = id;
		
		if (!thus.hidden(undefined, undefined, id, button)) {
			if (thus.buttonIsntDisabled(button)) {
				thus.setupButtonLinks(button);
			}
			
			thus.sortedCollectionButtons.push(button);
			thus.visibleCollectionButtons[id] = button;
		}
	}
	
	foreach (thus.sortedItemButtonIds as i => id) {
		button = tuix.item_buttons[id];
		button.id = id;
		
		thus.sortedItemButtons.push(button);
	}
	
	//Get a list of columns that are not hidden, and handle some other logic
	thus.pcOfTotal = {};
	foreach (thus.sortedColumnIds as i => id) {
		col = tuix.columns[id];
		col.id = id;
		
		//Check the usual rules for something that's hidden
		if (thus.hidden(undefined, undefined, id, undefined, col)) {
			continue;
		}
		
		//Special rule for columns in FEA lists: if every cell is hidden, hide the column too!
		if (tuix._cms_hiddenColumns
		 && tuix._cms_hiddenColumns[id]
		 && _.size(tuix._cms_hiddenColumns[id]) == numberofItemsShown) {
			continue;
		}
		
		
		thus.sortedColumns.push(col);
		thus.visibleColumns[id] = col;
		
		if (col.convert_to_percentage_of_total) {
			thus.pcOfTotal[id] = 0;
			
			foreach (tuix.items as j => item) {
				if (item[id] == 1*item[id]) {
					thus.pcOfTotal[id] += 1*item[id];
				}
			}
		}
	}
	
	zenarioT.setKin(thus.sortedColumns);
	zenarioT.setKin(thus.sortedCollectionButtons, 'zfea_button_with_children');
	zenarioT.setKin(thus.sortedItemButtons, 'zfea_button_with_children');
	
	
	if (tuix._cms_itemSortOrder) {
		sortedItemIds = tuix._cms_itemSortOrder;
	} else {
		var sortBy = tuix.sort_by || 'name',
			sortDesc = engToBoolean(tuix.sort_desc);
		
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
		
		sortedButtonsAndColumnButtons = thus.getSortedItemButtons([id], false);
		
		item._cms_sortedItemButtons = sortedButtonsAndColumnButtons[0];
		item._cms_columnButtons = sortedButtonsAndColumnButtons[1];
	}
	
	if (_.isEmpty(tuix.list_groupings)) {
		thus.sortedListGroupings = [undefined];
	} else {
		thus.sortedListGroupings = zenarioT.getSortedIdsOfTUIXElements(tuix, tuix.list_groupings, 'ord');
	}
	
	if (_.isEmpty(tuix.list_outer_groupings)) {
		thus.sortedListOuterGroupings = [undefined];
	} else {
		thus.sortedListOuterGroupings = zenarioT.getSortedIdsOfTUIXElements(tuix, tuix.list_outer_groupings, 'ord');
	}
	
	thus.last.tuix = tuix;
};

//Get a list of item buttons, depending on the item(s) this they were for
methods.getSortedItemButtons = function(itemIds, isCheckboxSelect) {
		
	var j, itemButton,
		k, childItemButton,
		cols, colId, shownOnCol,
		button, children, childButton, hasChildren,
		sortedButtons = [],
		columnButtons = {},
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
				
				if (button.allow_bypass) {
					thus.hasBypass = button;
				}
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
			
			hasChildren = button.children && button.children.length > 0;
		
			if (!button.hide_when_children_are_not_visible || hasChildren) {
				
				//Check the show_as_link_on_column option on a column.
				//If it's set, and we can match the button up to that column, show the button as a link on
				//the column instead of as a button.
				shownOnCol = false;
				if (!isCheckboxSelect
				 && !hasChildren
				 && !button.parent
				 && (cols = button.show_as_link_on_column)) {
					
					//Allow the dev to nominate multiple columns for the same link if they wish.
					cols = zenarioT.csvToObject(cols);
					foreach (cols as colId) {
						if (thus.visibleColumns[colId]) {
							columnButtons[colId] = button;
							shownOnCol = true;
						}
					}
				}
				
				//If not showing as a link on a column, show as a button as normal
				if (!shownOnCol) {
					thus.tuix._cms_itemHasItemButton = true;
					sortedButtons.push(button);
				}
			}
		}
	}
	
	return [sortedButtons, columnButtons];
};

methods.setupButtonLinks = function(button, itemId) {
	
	var page,
		request,
		onclick,
		onPrefix,
		command,
		item, childId;
	
	if (button.go
	 || button.ajax
	 || button.export
	 || button.onclick
	 || button.confirm) {
		
		onPrefix = thus.defineLibVarBeforeCode();
		
		if (!button.onclick
		 || !button.onclick.startsWith(onPrefix)) {
			
			onclick = onPrefix;
			
			if (defined(itemId)) {
                var itemIds = (itemId + '').split(',');
                    count = itemIds.length;
				
				if (count < 2) {
					
					item = thus.tuix.items
						&& thus.tuix.items[itemId];
					
                    onclick += "var button = (lib.tuix.item_buttons||{})['" + jsEscape(button.id) + "'],"
                            + "itemId = '" + jsEscape(itemId) + "',"
                            + "itemIds = [itemId],"
                            + "item = (lib.tuix.items||{})[itemId],"
                            + "items = {}; items[itemId] = item;";
                    
                    if (childId = item['_child_matched_for_' + 'item_button' + '__' + button.id]) {
						onclick += "var childId = '" + jsEscape(childId) + "';";
                    }
                    
                } else {
                    onclick += "var button = (lib.tuix.item_buttons||{})['" + jsEscape(button.id) + "'],"
                            + "itemId = '" + jsEscape(itemId) + "',"
                            + "itemIds = itemId.split(','),"
                            + "item,"
    						+ "items = _.pick(lib.tuix.items, itemIds);";
                }
			} else {
				onclick += "var button = (lib.tuix.collection_buttons||{})['" + jsEscape(button.id) + "'],"
						+ "itemId,"
						+ "itemIds=[],"
						+ "item,"
						+ "items = {};";
			}
			
			
			//Some special logic for someone using the href and onclick properties of a button.
			//Don't use the lib.button() function as a wrapper in this situation, as that
			//will likely cause the logic to not behave as expected!
			if (button.href
			 && button.onclick) {
				onclick += button.onclick;
			
			//Otherwise call lib.button() to handle the click event.
			} else {
				onclick += "lib.button(this, button, item, itemId";
			
				if (button.go) {
					request = thus.checkRequests(button.go, true, itemId);
				}
			
				if (button.onclick) {
					onclick += ", function() {" + button.onclick + "}";
				}
			
				onclick += "); return false;";
			}
		
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
		item = thus.tuix.items
			&& thus.tuix.items[itemId];
		button.href = item? item[button.href.replace_with_field_from_item] : '';
	}
};

//Submit/toggle button presses on forms
methods.clickButton = function(id, confirmed) {
	
	var button = thus.field(id),
		go;
	
	if (!confirmed
	 && button.confirm
	 && !thus.hidden(button.confirm, undefined, id, button)) {
		thus.confirm(
			button.confirm,
			function () {
				thus.clickButton(id, true);
			}
		);
	
	//Allow "go" requests on form buttons if they are on FEAs
	} else if (go = button.go) {
		thus.go(go);
		
	} else {
		methodsOf(zenarioF).clickButton.call(thus, id);
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
		isHTML,
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
			isHTML = confirm.html;
			
			//Not currently implemented, and might scrap this idea:
			//if (confirm.message_microtemplate) {
			//	//Don't try to apply merge fields if we're going to be using a microtemplate in the message anyway
			//
			//} else
			if (numItems === 1) {
				if (defined(confirm.title)) {
					confirm.title = zenario.applyMergeFields(confirm.title, item);
				}
				confirm.message = zenario.applyMergeFields(confirm.message, item, undefined, isHTML);
			
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
				
				if (defined(confirm.title)) {
					confirm.title = zenario.applyMergeFields(confirm.multiple_select_title || confirm.title, undefined, getMergeField);
				}
				confirm.message = zenario.applyMergeFields(confirm.multiple_select_message || confirm.message, undefined, getMergeField, isHTML);
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
		
		if (button.export) {
			//Create a file download that links to the plugin's exportVisitorTUIX() method.
			request = thus.checkRequests(button.export.request, false, itemId, undefined, true);
			
			var url = thus.visitorTUIXLink(request, 'export'),
				$a = $('<a>'),
				a = $a[0];
			
			//We serve a download by creating a temporary anchor element and virtually clicking it.
			a.href = url;
			$a.appendTo(document.body);
			
			//Note: For some reason, both $a.click() and $a.trigger('click') don't work! It needs 
			//to be a native browser a.click() to function. I wasn't able to work out why when debugging.
			a.click();
			
			//Remove the temporary element when done
			$a.remove();

		} else if (button.ajax) {
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
		
		
		buttons = thus.getSortedItemButtons(itemIds, true)[0];
		if (buttons.length > 0) {
			$div.html(thus.pMicroTemplate('button', thus.getSortedItemButtons(itemIds, true)[0]));
		} else {
			$div.html('No actions available');
		}
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










methods.confirm = function(confirm, after) {
	if (thus.loading) {
		return;
	}
	
	if (!_.isFunction(after) && after._cms_isConfirmed) {
		delete after._cms_isConfirmed;
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
		html: thus.pMicroTemplate('confirm', confirm),
		className: cssClassName,
		onOpen: addClassName
	});
	
	addClassName();

	if (!_.isFunction(after)) {
		$('#zfea_do_it').click(function() {
			$.colorbox.remove();
			after._cms_isConfirmed = true;
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
	
	var toast = ajax.toast,
		isDelete = ajax.is_delete,
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
			
			if (toast) {
				zenarioT.toast(toast);
			}
			
			if (resp.responseText) {
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
	
	var previewPost =
			zenario.adminId
		 && windowParent
		 && windowParent.zenarioAB
		 && windowParent.zenarioAB.previewPost;
	
	if (previewPost) {
		if (post === false
		 || !defined(post)) {
			post = {};
		}
		
		if (_.isObject(post)) {
			post.overrideSettings = previewPost;
		} else {
			post += '&overrideSettings=' + encodeURIComponent(previewPost);
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
	
	
	var showErrorMessage = function() {
		
		m.body = msg;
		m.retry = !!resp.zenario_retry;
		m.continueAnyway = resp.zenario_continueAnyway && resp.data;
		m.close = m.retry || m.continueAnyway;
		
		if (thus.inPopout) {
			thus.closePopout();
			
			//Don't show the "retry" button if showing the FEA form in a popup, as this isn't implemented
			//probably and just bugs out.
			//However still show the close button instead of the OK button for consistency
			//with how the rest of the error messages work.
			delete m.retry;
		}
		
		$.colorbox({
			className: 'zfea_error_box',
			transition: 'none',
			closeButton: false,
			html: thus.pMicroTemplate('error', m)
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


methods.initPopout = function(moduleClassName, library, path, mode, popoutClass, request, showCloseButtonAtTopRight) {
	return zenario_abstract_fea.initPopout(moduleClassName, library, path, mode, popoutClass, thus.containerId, request, thus, showCloseButtonAtTopRight);
};


}, zenarioFEA);