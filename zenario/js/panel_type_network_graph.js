/*
 * Copyright (c) 2023, Tribal Limited
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
	panelTypes
) {
	"use strict";

var methods = methodsOf(
	panelTypes.network_graph = extensionOf(panelTypes.grid)
);


methods.init = function() {
};

//You should return the page size you wish to use, or false to disable pagination
methods.returnPageSize = function() {
	return false;
};

//methods.showPanel = function($header, $panel, $footer) {
//	this.setHeader($header);
//	this.showViewOptions($header);
//	
//	this.drawItems($panel);
//	this.setScroll($panel);
//	
//	this.setTooltips($header, $panel, $footer);
//};
//
//methods.drawItems = function($panel) {
//	thus.items = this.getMergeFieldsForItemsAndColumns(true);
//	$panel.html(thus.microTemplate('zenario_organizer_grid', this.items));
//	$panel.show();
//};

/*methods.drawPagination = function($footer) {
	$footer.html('').show();
};*/


methods.drawItems = function($panel) {
	
	//thus.items = this.getMergeFieldsForItemsAndColumns(true);
	thus.items = {no_items_message: zenarioO.panelProp('no_items_message') || phrase.noItems};
	
	if (_.isEmpty(thus.tuix.items)) {
		$panel.html(thus.microTemplate('zenario_organizer_grid', thus.items));
	
	} else {
		
		var id, item,
			options = $.extend(
				true, {},
				{

					boxSelectionEnabled: false,
					//autounselectify: true,
					//autolock: true,
					//autoungrabify: true,

					style: [
						{
							selector: 'node',
							css: {
								'content': 'data(label)',
								'text-valign': 'center',
								'text-halign': 'center',
								'background-color': 'data(color)',
								'border-color': 'data(color)',
								'border-opacity': 0.5,
								'border-width': 1,
								'border-style': 'solid'
							}
						}, {
							selector: 'node > $node',
							css: {
								'text-opacity': 0.7
							}
						}, {
							selector: '$node > node',
							css: {
								'padding-top': '10px',
								'padding-left': '10px',
								'padding-bottom': '10px',
								'padding-right': '10px',
								'text-valign': 'bottom',
								'text-halign': 'center',
								'font-size': 15,
								'background-color': '#ddd',
								'background-opacity': 0.5,
								'border-color': '#eee',
								'border-opacity': 1,
								'border-width': 1,
								'border-style': 'solid'
							}
						}, {
							selector: 'edge',
							css: {
								'target-arrow-shape': 'triangle',
								'target-arrow-color': 'data(color)',
								'line-style': 'solid',
								'line-color': 'data(color)',
								'content': 'data(label)',
								//'color': 'data(color)',
								'text-opacity': 0.8,
								'font-size': 14,
								'text-valign': 'center',
								'text-halign': 'center',
								'text-rotation': 'autorotate',
								'curve-style': 'bezier',
								'opacity': 0.625
							}
						}, {
							selector: 'edge.dotted',
							css: {
								'line-style': 'dotted'
							}
						}, {
							selector: 'edge.dashed',
							css: {
								'line-style': 'dashed'
							}
						}, {
							selector: ':selected',
							css: {
								'border-color': '#000',
								'border-opacity': 1,
								'border-width': 1,
								'border-style': 'solid',
								'background-blacken': 0.3,
								'line-color': 'black'
							}
						}
					],

					elements: {
						nodes: [],
						edges: []
					},
					
					
					//
					// Different types of layout
					//
					
					//Circle layout, items are arranged in a circle
					//layout: {
					//	name: 'circle',
					//	padding: 5,
					//	radius: 100
					//}
					
					//Breadthfirst layout - a smart layout this arranges things in a grid, trying to keep linked items
					//close to each other and reducing the ammount of spaghetti. Not fool-proof though!
					layout: {
						name: 'breadthfirst'
						//fit: true, // whether to fit the viewport to the graph
						//directed: false, // whether the tree is directed downwards (or edges can point in any direction if false)
						//padding: 30, // padding on fit
						//circle: false, // put depths in concentric circles if true, put depths top down if false
						//spacingFactor: 1.75, // positive spacing factor, larger => more space between nodes (N.B. n/a if causes overlap)
						//boundingBox: undefined, // constrain layout bounds; { x1, y1, x2, y2 } or { x1, y1, w, h }
						//avoidOverlap: true, // prevents node overlap, may overflow boundingBox if not enough space
						//roots: undefined, // the roots of the trees
						//maximalAdjustments: 0, // how many times to try to position the nodes in a maximal way (i.e. no backtracking)
						//animate: false, // whether to transition the node positions
						//animationDuration: 500, // duration of animation in ms if enabled
						//animationEasing: undefined, // easing of animation if enabled
						//ready: undefined, // callback on layoutready
						//stop: undefined // callback on layoutstop
					}
					
					
					//Manual layout - here you need to manually set the position of each node
					//layout: {
					//	name: 'preset',
					//	// map of (node id) => (position obj); or function(node){ return somPos; }
					//	positions: {"state_A":{"x":209,"y":190.66666666666666},"state_O":{"x":182.875,"y":286}, ... },
					//	zoom: undefined, // the zoom level to set (prob want fit = false if set)
					//	pan: undefined, // the pan level to set (prob want fit = false if set)
					//	fit: true, // whether to fit to viewport
					//	padding: 30, // padding on fit
					//	animate: false, // whether to transition the node positions
					//	animationDuration: 500, // duration of animation in ms if enabled
					//	animationEasing: undefined, // easing of animation if enabled
					//	ready: undefined, // callback on layoutready
					//	stop: undefined // callback on layoutstop
					//}
					
					
				},
				thus.tuix.cytoscape
			);
		
		var topLevelCount = 0,
			needToDeselect = false;
		
		foreach (thus.tuix.items as id => item) {
			
			//We're about to add some custom properties to an item,
			//and also cytoscape will also add a lot of custom properties,
			//so make a clone of it to avoid adding lots of confusing properties when
			//inspecting the original data, e.g. in the dev tools.
			item = {data: zenario.clone(item)};
			
			//Mark if the item should start selected
			item.selected = thus.selectedItems && thus.selectedItems[id];
			
			item.classes = item.data.classes || '';
			
			if (!item.data.color) {
				item.data.color = 'grey';
			}
			
			//Make items unselectable if requested
			if (engToBoolean(item.data.unselectable)) {
				item.selected =
				item.selectable = false; 
				
				//Watch out for the case where Organizer or the UI tries to auto-select something this shouldn't be selected
				if (thus.selectedItems[id]) {
					delete thus.selectedItems[id];
					needToDeselect = true;
				}
			}
			
			//Allow states to be moved, slides and paths should inherit their position using the position of the states
			//so they shouldn't be independantly movable
			item.grabbable = item.data.type == 'state';
			
			switch (item.data.type) {
				case 'state':
				case 'slide':
				case 'node':
					if (!item.data.parent) {
						++topLevelCount;
					}
					options.elements.nodes.push(item);
					break;
				case 'path':
				case 'edge':
					options.elements.edges.push(item);
					break;
			}
		}
		
		//Try to set a sensible radius, depending on how many states are being shown
		if (!options.layout.radius && topLevelCount) {
			options.layout.radius = Math.ceil(50 * Math.log(topLevelCount));
		}
		
		thus.cyOptions = options;
		thus.drawCytoscape($panel);
		
		
		//Check to see if there were any specific positions set for this nest.
		//If there are any saved positions in the database, override the defaults for each position this was saved
		if (!_.isEmpty(thus.tuix.positions)) {
			var pos;
			foreach (thus.tuix.positions as id => pos) {
				if (thus.tuix.items[id]) {
					thus.cy.$('#' + id).position(pos);
				}
			}
			thus.cy.fit();
		}
	}

	
	$panel.show();
	
	//Deal with the case where Organizer or the UI tries to auto-select something this shouldn't be selected
	//by deselecting it and then redrawing the item buttons
	if (needToDeselect) {
		zenarioO.deselectAllItems();
		thus.setButtonsWrapper();
	}
};

methods.drawCytoscape = function($panel) {
	$panel.html('');
	
	var draggedSomething = false,
		options = zenario.clone(thus.cyOptions);
	options.container = $panel;
	
	thus.cy = cytoscape(options);
	
	//Every time the admin clicks something, check with cytoscape to see what items they currently have
	//selected, and update Organizer/the buttons/the hash in the URL
	thus.cy.on('select unselect', 'node, edge', function(e) {
		
		//n.b. if I ever want the id of the item I can use
		//event.cyTarget.data && event.cyTarget.data('id')
		
		thus.selectedItems = {};
		
		var i, item, id,
			items = thus.cy.$(':selected').jsons();
		
		if (!_.isEmpty(items)) {
			foreach (items as i => item) {
				if (id = item.data && item.data.id) {
					thus.selectedItems[id] = true;
				}
			}
		}
		
		thus.setButtonsWrapper();
	});
	
	//If the admin moves a node, update the position object
	thus.cy.on('grab', 'node', function(e) {
		draggedSomething = false;
	});
	thus.cy.on('drag', 'node', function(e) {
		draggedSomething = true;
	});
	thus.cy.on('free', 'node', function(e) {
		
		if (!draggedSomething) {
			return;
		}
		
		var id, item,
			noPositionsWereSetBefore = _.isEmpty(thus.tuix.positions),
			noPositionsWereChangedBefore = !thus.tuix.positionsChanged;
		
		thus.tuix.positions = {};
		
		foreach (thus.tuix.items as id => item) {
			if (item.type == 'state') {
				thus.tuix.positions[id] = thus.cy.$('#' + id).position();
				thus.tuix.positionsChanged = true;
			}
		}
		
		//If no positions were set before, and they are now, we may need to redraw the buttons
		if (noPositionsWereSetBefore || noPositionsWereChangedBefore) {
			zenarioO.setButtons();
		}
	});
};

methods.setButtonsWrapper = function() {
	
	zenarioO.setButtons();
	zenarioO.setHash();
};

//This method should cause an item to be selected.
//It is called after your panel is drawn so you should update the state of your items
//on the page.
methods.selectItem = function(id) {
	thus.selectedItems[id] = true;
	if (thus.cy
	 && thus.tuix.items
	 && thus.tuix.items[id]
	 && !engToBoolean(thus.tuix.items[id].unselectable)) {
		thus.cy.$('#' + id).select();
	}
};

//This method should cause an item to be deselected
//It is called after your panel is drawn so you should update the state of your items
//on the page.
methods.deselectItem = function(id) {
	delete thus.selectedItems[id];
	thus.cy && thus.cy.$('#' + id).deselect();
};



methods.sizePanel = function($header, $panel, $footer, $buttons) {
	if (thus.cy) {
		thus.cy.resize();
		thus.cy.fit();
	}
	methodsOf(panelTypes.grid).sizePanel.call(thus, $header, $panel, $footer, $buttons);
};

methods.onUnload = function($header, $panel, $footer) {
	//this.saveScrollPosition($panel);
	thus.cy && thus.cy.destroy();
};

methods.getItemPosition = function($panel, itemId) {
	return false;
};



}, zenarioO.panelTypes);




/*

As a reference, these are the styles available for nodes, with their default values:
	
{
	'events': 'yes',
	'text-events': 'no',
	'text-valign': 'top',
	'text-halign': 'center',
	'color': '#000',
	'text-outline-color': '#000',
	'text-outline-width': 0,
	'text-outline-opacity': 1,
	'text-opacity': 1,
	'text-decoration': 'none',
	'text-transform': 'none',
	'text-wrap': 'none',
	'text-max-width': 9999,
	'text-background-color': '#000',
	'text-background-opacity': 0,
	'text-background-margin': 0,
	'text-border-opacity': 0,
	'text-border-width': 0,
	'text-border-style': 'solid',
	'text-border-color': '#000',
	'text-background-shape': 'rectangle',
	'font-family': 'Helvetica Neue, Helvetica, sans-serif',
	'font-style': 'normal',
	// 'font-variant': fontVariant,
	'font-weight': 'normal',
	'font-size': 16,
	'min-zoomed-font-size': 0,
	'text-rotation': 'none',
	'source-text-rotation': 'none',
	'target-text-rotation': 'none',
	'visibility': 'visible',
	'display': 'element',
	'opacity': 1,
	'z-index': 0,
	'label': '',
	'text-margin-x': 0,
	'text-margin-y': 0,
	'source-label': '',
	'source-text-offset': 0,
	'source-text-margin-x': 0,
	'source-text-margin-y': 0,
	'target-label': '',
	'target-text-offset': 0,
	'target-text-margin-x': 0,
	'target-text-margin-y': 0,
	'overlay-opacity': 0,
	'overlay-color': '#000',
	'overlay-padding': 10,
	'shadow-opacity': 0,
	'shadow-color': '#000',
	'shadow-blur': 10,
	'shadow-offset-x': 0,
	'shadow-offset-y': 0,
	'text-shadow-opacity': 0,
	'text-shadow-color': '#000',
	'text-shadow-blur': 5,
	'text-shadow-offset-x': 0,
	'text-shadow-offset-y': 0,
	'transition-property': 'none',
	'transition-duration': 0,
	'transition-delay': 0,
	'transition-timing-function': 'linear',

	// node props
	'background-blacken': 0,
	'background-color': '#999',
	'background-opacity': 1,
	'background-image': 'none',
	'background-image-opacity': 1,
	'background-position-x': '50%',
	'background-position-y': '50%',
	'background-repeat': 'no-repeat',
	'background-fit': 'none',
	'background-clip': 'node',
	'background-width': 'auto',
	'background-height': 'auto',
	'border-color': '#000',
	'border-opacity': 1,
	'border-width': 0,
	'border-style': 'solid',
	'height': 30,
	'width': 30,
	'shape': 'ellipse',
	'shape-polygon-points': '-1, -1,   1, -1,   1, 1,   -1, 1',

	// compound props
	'padding-top': 0,
	'padding-bottom': 0,
	'padding-left': 0,
	'padding-right': 0,
	'position': 'origin',
	'compound-sizing-wrt-labels': 'include'
}

In addition, these properties also apply to edges:
{
	// edge props
	'line-style': 'solid',
	'line-color': '#999',
	'control-point-step-size': 40,
	'control-point-weights': 0.5,
	'segment-weights': 0.5,
	'segment-distances': 20,
	'edge-distances': 'intersection',
	'curve-style': 'bezier',
	'haystack-radius': 0
}

*/