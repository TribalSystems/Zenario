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


methods.ffov = function(action) {
	
	var cb = new zenario.callback,
		url = thus.setURLForSyncedRequests(action),
		post,
		goneToURL = false;
	
	if (action == 'fill') {
		post = thus.modifyPostOnLoad();
	} else {
		thus.prevPath = thus.path;
		thus.checkValues();
		post = {_format: true, _tuix: thus.sendStateToServer()};
	}
	
	if (!thus.loading) {
		thus.showLoader();
		
		var after = function(tuix) {
			thus.hideLoader();
			
			zenarioT.checkDumps(tuix);
			
			if (action == 'fill') {
				thus.tuix = tuix;
			} else {
				thus.setData(tuix);
			}
			
			var js = thus.tuix.js,
				runAfter = thus.tuix.js_after,
				runAfterString;
		
			//Reload the opener if the reload_parent flag was set
			if (thus.tuix.reload_parent) {
				thus.reloadParent();
			}
			
			//If the js flag was set, execute that code
			if (js) {
				zenarioT.eval(js, thus);
			}
			
			if (runAfter && !_.isFunction(runAfter)) {
				runAfterString = runAfter;
				
				runAfter = function() {
					zenarioT.eval(runAfterString, thus);
				};
			}
			
			//If this is a popout, and the close_popout flag was set, close it
			if (thus.tuix.close_popout && thus.inPopout) {
				thus.closePopout();
			}
			
			//If the stop_flow property was set, don't do a go() or a draw().
			if (thus.tuix.stop_flow) {
				return;
			}
			
			
			if (thus.tuix.go) {
				thus.go(thus.tuix.go, undefined, undefined, runAfter);
			
			} else if (thus.tuix.go_to_url && !goneToURL) {
				zenario.goToURL(thus.tuix.go_to_url);
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
				if ((action == 'format' || action == 'validate') && thus.tuix.scroll_to_top_of_slot_on_format_or_validate) {
					zenario.scrollToSlotTop(thus.containerId, true);
				}
				
				if (action == 'save' && thus.tuix.scroll_after_save) {
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
	
	thus.tuix.form_title = thus.getTitle();
	
	thus.cb = new zenario.callback;
	thus.putHTMLOnPage(thus.drawFields(thus.cb, thus.mtPrefix + '_form'));
	
	var DOMlastFieldInFocus;
	
	if (thus.path == thus.prevPath
	 && thus.lastFocus
	 && thus.lastFocus.id != ''
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
	
	if (zenarioT.showDevTools()) {
		thus.__lastFormHTML = html;
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
	
	if (!thus.__cusTemplateApplied
	 && (template == 'fea_list' || template == 'fea_form')
	 && (cusTemplate = thus.tuix && thus.tuix.microtemplate)) {
		template = cusTemplate;
		thus.__cusTemplateApplied = cusTemplateApplied = true;
	}
	
	html = methodsOf(zenarioF).microTemplate.call(thus, template, data, filter, preMicroTemplate, postMicroTemplate);
	
	if (cusTemplateApplied) {
		delete thus.__cusTemplateApplied;
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
	
	delete thus.__lastFormHTML;
	
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
	var typeOfLogic = thus.typeOfLogic();
	
	delete thus.__lastFormHTML;
	
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
};




methods.doAjaxLoadThenShowList = function(callWhenLoaded) {
	if (thus.loading) {
		return;
	}
	
	var url = thus.setURLForSingleRequests();
	
	thus.showLoader();
	thus.ajax(url, false, true).after(function(tuix) {
	
		thus.tuix = tuix;
		
		thus.drawList();
		callWhenLoaded();
		
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
};




methods.drawList = function() {
	thus.hadSparkline = false;
	
	thus.sortOutTUIX();
	
	var page = 1 * thus.tuix.__page__,
		pageSize = 1 * thus.tuix.__page_size__,
		itemCount = 1 * thus.tuix.__item_count__,
		items = thus.tuix.items,
		item, itemId, paginationId;
	
	
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
			
			//withSlider: true,
			//minSlidesForSlider: 2,
			//
			//withAcceleration: true,
			//speed: 2,
			//coeffAcceleration: 2,
			
			onPageClicked: function(a,num) { 
				thus.doSearch(undefined, undefined, undefined, num);
			}
		});
	}
	

	
	//call sparkline
	if (thus.hadSparkline) {
		thus.initSparklineChart();
	}
	
	
	if (thus.tuix.map) {
		thus.initMap();
	}

	thus.hideLoader();
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
	
	if (!thus._storedGraphSeriesData) {
		thus._storedGraphSeriesData = {};
	}
	
	var seriesData = thus._storedGraphSeriesData,
		sync = thus.tuix.always_sync_this_data_between_client_and_server,
		noSync = thus.tuix.never_sync_this_data_between_client_and_server,
		incomingSeriesData = noSync && noSync.seriesData || {},
		incomingGraph = sync.graph,
		existingGraph = thus._graph,
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
	//	delete thus._graph;
	//	
	//	return;
	//}
	
	
	//Draw the HTML using the microtemplate.
	//(Needs to be done before we try and initialise the graph as the <idv> for the graph will need to exist in the DOM first.)
	thus.putHTMLOnPage(thus.microTemplate(thus.tuix.microtemplate, {}));
	
	
	//If the graph didn't previously exist on the page, initialise it
	if (!existingGraph) {
		thus._graph = Highcharts.chart(incomingGraph);

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
	
	//Only run this once per instance.
	if (!thus._addedEvents) {
		thus._addedEvents = true;
		
		//Don't be too picky with the format of the handle_signals property.
		var si, signal, signals = zenarioT.tuixToArray(thus.tuix.handle_signals),
			signalOnRegister;
		
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
						thus.on(signal, function(data) {
							thus[signal](data);
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
	
	return thus.visitorTUIXLink(_.extend({_tab: tab, _field: id}, thus.tuix.key), 'tas');
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
	var domSearch = thus.get('search_' + thus.containerId);
	
	return (domSearch ? domSearch.value: false);
};

methods.doSearch = function(e, searchValue, requests, page) {
	
	zenario.stop(e);
	
	if (!defined(searchValue)) {
		searchValue = thus.getSearchFieldValue();
	}
	
	requests = _.extend({}, thus.request, requests);
	
	var page = page ? page : '',
		search = {
			page: page,
			search: searchValue
		};

	thus.go(thus.checkRequests(requests, false, undefined, search, true));
	
	return false;
};

methods.changeSortCol = function(colId, sortDesc) {
	var columns = thus.tuix.columns || {},
		col = columns[colId] || {},
		req = {};
	
	if (col.sort_asc || col.sort_desc) {
		
		if (thus.key('page') || thus.request.page) {
			req.page = 1;
		}
		
		if (col.sort_asc && !col.sort_desc) {
			sortDesc = 0;
		
		} else if (!col.sort_asc && col.sort_desc) {
			sortDesc = 1;
		
		} else if (!defined(sortDesc)) {
			sortDesc = engToBoolean(thus.key('sortCol') == colId && !thus.key('sortDesc'));
		}
		
		req.sortDesc = sortDesc;
		req.sortCol = colId;
		
		thus.go(req);
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
		if (thus.tuix._hiddenItemButtons
		 && thus.tuix._hiddenItemButtons[button.id]
		 && thus.tuix._hiddenItemButtons[button.id][itemId]) {
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
	button._isDisabled = true;
	button.tooltip = button.disabled_tooltip || button.tooltip;
	
	return false;
};


methods.columnVisibleForItem = function(columnId, itemId) {
	
	var column = thus.tuix.columns[columnId] || {},
		item = thus.tuix.items[itemId] || {};
	
	if (thus.tuix._hiddenColumns
	 && thus.tuix._hiddenColumns[columnId]
	 && thus.tuix._hiddenColumns[columnId][itemId]) {
		return false;
	}
	
	//zenarioT.eval(condition, lib, tuixObject, item, id, button, column, field, section, tab, tuix);
	if (defined(column.visible_if_for_each_item) && zenarioT.eval(column.visible_if_for_each_item, thus, undefined, item, itemId, undefined, column)) {
		return false;
	}
	
	return true;
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
		sortBy = tuix.sort_by || 'name',
		sortDesc = engToBoolean(tuix.sort_desc),
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
		if (tuix._hiddenColumns
		 && tuix._hiddenColumns[id]
		 && _.size(tuix._hiddenColumns[id]) == numberofItemsShown) {
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
		
		sortedButtonsAndColumnButtons = thus.getSortedItemButtons([id], false);
		
		item.__sortedItemButtons = sortedButtonsAndColumnButtons[0];
		item.__columnButtons = sortedButtonsAndColumnButtons[1];
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
		colId,
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
				
				
				if (!isCheckboxSelect
				 && !hasChildren
				 && !button.parent
				 && (colId = button.show_as_link_on_column)
				 && (thus.visibleColumns[colId])) {
					columnButtons[colId] = button;
				} else {
					thus.tuix.__itemHasItemButton = true;
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
		item = thus.tuix.items
			&& thus.tuix.items[itemId];
		button.href = item? item[button.href.replace_with_field_from_item] : '';
	}
};

//Submit/toggle button presses on forms
methods.clickButton = function(id) {
	
	var button = thus.field(id),
		clickButton = methodsOf(zenarioF).clickButton;
	
	if (button.confirm
	 && !thus.hidden(button.confirm, undefined, id, button)) {
		thus.confirm(
			button.confirm,
			function () {
				clickButton.call(thus, id);
			}
		);
		
	} else {
		clickButton.call(thus, id);
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
		html: thus.pMicroTemplate('confirm', confirm),
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