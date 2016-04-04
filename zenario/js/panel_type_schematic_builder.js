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

zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioAB, zenarioAT, zenarioO,
	get, engToBoolean, htmlspecialchars, ifNull, jsEscape, phrase,
	extensionOf, methodsOf, has,
	panelTypes
) {
	"use strict";

var methods = methodsOf(
	panelTypes.schematic_builder = extensionOf(panelTypes.base)
);

methods.init = function() {
	this.canvas = false;
};

//Use this to add any requests you need to the AJAX URL used to call your panel
methods.returnAJAXRequests = function() {
	return {no_cache: 1};
};

//You should return the page size you wish to use, or false to disable pagination
methods.returnPageSize = function() {
	return false;
};

//Return whether you want searching/sorting/pagination to be done server-side.
//If you return true, sorting and pagination will be applied on the server.
//If you return false, your sortAndSearchItems() method will be called instead.
methods.returnDoSortingAndSearchingOnServer = function() {
	return false;
};

methods.returnSearchingEnabled = function() {
	return false;
};

methods.returnPanelTitle = function() {
	return this.tuix.title;
};

methods.getAJAXURL = function() {
	return URLBasePath + 'zenario/ajax.php?' 
		+ '__pluginClassName__=' + this.tuix.class_name 
		+ '&__path__=' + zenarioO.path 
		+ '&method_call=handleOrganizerPanelAJAX';
};

methods.showPanel = function($header, $panel, $footer) {
	var that = this;
	$header.html('').show();
	
	var html = zenarioA.microTemplate('zenario_organizer_schematic_builder', {});
	$panel.html(html).show();
	
	$footer.html('').show();
	
	
	// Init fabric canvas
	var canvasOptions = {
		width: 700,
		height: 500
	};
	this.canvas = new fabric.Canvas('organizer_schematic_builder_canvas', canvasOptions);
	fabric.Object.prototype.custom = {};
	
	// Load saved schematic from JSON
	this.layer = 'background';
	if (this.tuix.schematic_json) {
		this.canvas.loadFromJSON(this.tuix.schematic_json, function() {
			if (that.tuix.start_layer) {
				that.layer = that.tuix.start_layer;
			}
			that.changeCanvasLayer(that.layer);
			that.canvas.renderAll();
		});
	}
	
	// Select object
	this.canvas.on('object:selected', function(u) {
		var object = u.target;
		if (that.layer == 'foreground' && object.custom.layer == 'foreground') {
			var mergeFields = {
			
			};
			var html = zenarioA.microTemplate('zenario_organizer_schematic_builder_forground_field_details', mergeFields);
			$('#organizer_schematic_builder .foreground_item_fields').html(html);
		}
	});
	
	
	// Events
	
	// Clear background images
	$('#organizer_schematic_builder__clear_background').on('click', function() {
		if (that.layer == 'background') {
			that.clearCanvasLayer('background');
		}
	});
	
	// Upload background image
	$('#organizer_schematic_builder__add_background_image').on('click', function() {
		if (that.layer == 'background') {
			var html = '<input type="file" name="Filedata" accept="image/*">';
			var $input = $(html);
			var requests = zenarioO.getKey();
			requests.action = 'upload_background_image';
			$input.on('change', function() {
				zenarioA.doHTML5Upload(this.files, that.getAJAXURL(), requests, function(images) {
					that.uploadImagesCallback(images);
				});
			});
			$input.click();
		}
	});
	
	// Add background image from library
	$('#organizer_schematic_builder__add_library_background_image').on('click', function() {
		if (that.layer == 'background') {
			var path = 'zenario__content/panels/image_library';
			zenarioA.organizerSelect(function(path, key, row) {
				var requests = zenarioO.getKey();
				requests.action = 'get_library_image_link';
				requests.image_id = key.id;
				zenario.ajax(that.getAJAXURL(), requests).after(function(imageJSON) {
					var image = JSON.parse(imageJSON);
					that.addImageToCanvas(image.link, key.id, 'background');
				});
			}, undefined, false, path, path, path, path, true);
		}
	});
	
	// Save
	$('#organizer_schematic_builder__save').on('click', function() {
		
		var layerIsBackground = false;
		if (that.layer == 'background') {
			layerIsBackground = true;
			that.changeCanvasLayer('foreground', true);
		}
		
		var json = JSON.stringify(that.canvas.toJSON(['custom']));
		var requests = zenarioO.getKey();
		
		requests.action = 'save_background_image';
		requests.json = json;
		
		zenarioA.nowDoingSomething('saving', true);
		zenario.ajax(that.getAJAXURL(), requests).after(function(responses) {
			if (layerIsBackground) {
				that.changeCanvasLayer('background', true);
			}
			zenarioA.nowDoingSomething();
		});
	});
	
	// Duplicate
	$('#organizer_schematic_builder__duplicate_background_image').on('click', function() {
		var image = that.canvas.getActiveObject();
		if (that.layer == 'background') {
			if (image && image.custom.layer == 'background') {
				var cache = false;
				var imageId = false;
				if (image.custom.cache_id) {
					cache = true;
					imageId = image.custom.cache_id;
				} else if (image.custom.image_id) {
					imageId = image.custom.image_id;
				}
				
				that.addImageToCanvas(image.src, imageId, 'background', cache);
			}
		}
	});
	
	// Delete
	$('#organizer_schematic_builder__delete_background_image').on('click', function() {
		if (that.layer == 'background') {
			var image = that.canvas.getActiveObject();
			if (image && image.custom.layer == 'background') {
				that.canvas.remove(image).renderAll();
			}
		}
	});
	
	// Move image forwards 1 place
	$('#organizer_schematic_builder__move_background_image_forward').on('click', function() {
		if (that.layer == 'background') {
			var image = that.canvas.getActiveObject();
			if (image && image.custom.layer == 'background') {
				var objects = that.canvas.getObjects();
				var currentIndex = objects.indexOf(image);
				if (objects.length >= currentIndex && objects[currentIndex + 1].custom.layer == 'background') {
					that.canvas.bringForward(image);
				}
			}
		}
	});
	
	// Move image backwards 1 place
	$('#organizer_schematic_builder__move_background_image_back').on('click', function() {
		if (that.layer == 'background') {
			var image = that.canvas.getActiveObject();
			if (image && image.custom.layer == 'background') {
				var objects = that.canvas.getObjects();
				var currentIndex = objects.indexOf(image);
				if (currentIndex > 0) {
					that.canvas.sendBackwards(image);
				}
			}
		}
	});
	
	// Move image to first background image
	$('#organizer_schematic_builder__move_background_image_first').on('click', function() {
		if (that.layer == 'background') {
			var image = that.canvas.getActiveObject();
			if (image && image.custom.layer == 'background') {
				var objects = that.canvas.getObjects();
				var pos = false;
				for (var i = 0; i < objects.length; i++) {
					if (objects[i].custom.layer == 'foreground') {
						pos = i - 1;
						break;
					}
				}
				if (pos === false) {
					pos = objects.length - 1;
				}
				image.moveTo(pos);
			}
		}
	});
	
	// Move image to last background image
	$('#organizer_schematic_builder__move_background_image_last').on('click', function() {
		if (that.layer == 'background') {
			var image = that.canvas.getActiveObject();
			if (image && image.custom.layer == 'background') {
				that.canvas.sendToBack(image);
			}
		}
	});
	
	// Switch to edit the background
	$('#organizer_schematic_builder__edit_background').on('click', function() {
		if (that.layer == 'foreground') {
			that.changeCanvasLayer('background');
		}
	});
	
	// Switch to edit the foreground
	$('#organizer_schematic_builder__edit_foreground').on('click', function() {
		if (that.layer == 'background') {
			that.changeCanvasLayer('foreground');
		}
	});
	
	// Clear foreground images
	$('#organizer_schematic_builder__clear_foreground').on('click', function() {
		if (that.layer == 'foreground') {
			that.clearCanvasLayer('foreground');
		}
	});
	
	// Add foreground image
	$('#organizer_schematic_builder__add_foreground_image').on('click', function() {
		if (that.layer == 'foreground') {
			var circle = new fabric.Circle({
				radius: 20,
				fill: 'red',
				left: 20,
				top: 20,
				custom: {
					layer: 'foreground'
				}
			});
			that.canvas.add(circle);
		}
	});
	
	// Delete foreground image
	$('#organizer_schematic_builder__delete_foreground_image').on('click', function() {
		if (that.layer == 'foreground') {
			var image = that.canvas.getActiveObject();
			if (image && image.custom.layer == 'foreground') {
				that.canvas.remove(image).renderAll();
			}
		}
	});
	
};

methods.showButtons = function($buttons) {
	$buttons.html('').show();
};

methods.addImageToCanvas = function(link, imageId, layer, isCached) {
	var that = this;
	if (link && imageId) {
		// Create new image object
		fabric.Image.fromURL(link, function(oImg) {
			if (isCached) {
				oImg.custom.cache_id = imageId;
			} else {
				oImg.custom.image_id = imageId;
			}
			if (layer) {
				oImg.custom.layer = layer;
			}
			oImg.src = link;
			that.canvas.add(oImg);
			
			// Move object behind all exisiting foreground objects 
			if (layer == 'background') {
				var pos = false;
				var objects = that.canvas.getObjects();
				
				for (var i = 0; i < objects.length; i++) {
					if (objects[i].custom.layer == 'foreground') {
						pos = i;
						break;
					}
				}
				
				if (pos !== false) {
					var object = objects[objects.length - 1];
					object.moveTo(pos);
				}
			}
		});
	}
};

methods.clearCanvasLayer = function(layer) {
	if (layer) {
		var that = this;
		var objects = this.canvas.getObjects();
		var remove = [];
		for (var i = 0; i < objects.length; i++) {
			if (objects[i].custom.layer == layer) {
				remove.push(objects[i]);
			}
		}
		for (i = 0; i < remove.length; i++) {
			that.canvas.remove(remove[i]);
		}
		this.canvas.renderAll();
	}
};

methods.changeCanvasLayer = function(layer, stopRender) {
	if (layer) {
		var that = this;
		this.layer = layer;
		this.canvas.getObjects().forEach(function(object, i) {
			if (layer == 'background') {
				if (object.custom.layer == 'foreground') {
					object.lockMovementX = true;
					object.lockMovementY = true;
					object.selectable = false;
					object.hasControls = false;
					object.opacity = 0.1;
				} else if (object.custom.layer == 'background') {
					object.lockMovementX = false;
					object.lockMovementY = false;
					object.selectable = true;
					object.hasControls = true;
				}
				
			} else if (layer == 'foreground') {
				if (object.custom.layer == 'foreground') {
					object.lockMovementX = false;
					object.lockMovementY = false;
					object.selectable = true;
					object.hasControls = true;
					object.opacity = 1;
				} else if (object.custom.layer == 'background') {
					object.lockMovementX = true;
					object.lockMovementY = true;
					object.selectable = false;
					object.hasControls = false;
				} 
			}
		});
		// Unselect and redraw
		if (!stopRender) {
			that.canvas.deactivateAll().renderAll();
		}
	}
};

methods.uploadImagesCallback = function(images) {
	if (images) {
		var image = images[0];
		this.addImageToCanvas(image.link, images[0].id, 'background', true);
	}
};


//This method should cause an item to be selected.
//It is called after your panel is drawn so you should update the state of your items
//on the page.
methods.selectItem = function(id) {
	methodsOf(panelTypes.base).selectItem.call(this, id);
	this.updateEventsDetails();
};

//This method should cause an item to be deselected
//It is called after your panel is drawn so you should update the state of your items
//on the page.
methods.deselectItem = function(id) {
	methodsOf(panelTypes.base).deselectItem.call(this, id);
	this.updateEventsDetails();
};


//This updates the checkbox for an item, if you are showing checkboxes next to items,
//and the "all items selected" checkbox, if it is on the page.
methods.updateItemCheckbox = function(id, checked) {
	
	//No checkboxes on the calendar, so we do nothing
};

//Return whether you want to enable inspection view
methods.returnInspectionViewEnabled = function() {
	return false;
};

//This method should open inspection view
methods.openInspectionView = function(id) {
	//...
};

//This method should close inspection view
methods.closeInspectionView = function(id) {
	//...
};



}, zenarioO.panelTypes);