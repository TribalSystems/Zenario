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
	panelTypes
) {
	"use strict";

var methods = methodsOf(
	panelTypes.schematic_builder = extensionOf(panelTypes.base)
);

if (typeof(fabric) !== 'undefined') {
	fabric.Rect.prototype.resizeToScale = function() {
		this.width *= this.scaleX;
		this.height *= this.scaleY;
		this.scaleX = 1;
		this.scaleY = 1;
	};
}

methods.init = function() {
	this.canvas = false;
	this.nextUniqueId = 0;
	this.lastSelectedObjectId = false;
	this.assetBlockOpacity = 0.8;
	// Whether there are any local changes
	this.changingForm = false;
	// Build layers structure
	this.layers = {
		background: {
			ord: 1,
			groups: {
				images: {
					ord: 1,
					objects: []
				}
			}
		},
		foreground: {
			ord: 2,
			groups: {
				asset_block: {
					ord: 1,
					objects: []
				},
				hotspot: {
					ord: 2,
					objects: []
				}
			}
		}
	};
};

methods.showPanel = function($header, $panel, $footer) {
	
	$header.html('').hide();
	
	var that = this;
	var start_layer = 'start_layer';
	var mergeFields = {
		controls: this.getLayerControlsMergeFields(start_layer),
		width: this.tuix.canvas.width,
		height: this.tuix.canvas.height
	};
	var html = zenarioA.microTemplate('zenario_organizer_schematic_builder', mergeFields);
	$panel.html(html).show();
	this.initLayerControls(start_layer);
	
	$footer.html('').show();
	
	// Init fabric canvas
	var canvasOptions = {
		width: this.tuix.canvas.width,
		height: this.tuix.canvas.height
	};
	this.canvas = new fabric.Canvas('organizer_schematic_builder_canvas', canvasOptions);
	
	// Load saved schematic from JSON
	this.layer = 'background';
	if (this.tuix.schematic.json) {
		this.canvas.loadFromJSON(this.tuix.schematic.json, function() {
			that.layer = start_layer;
			
			var objects = that.canvas.getObjects();
			for (var i = 0; i < objects.length; i++) {
				
				// Add objects into layers structure to keep track of position
				that.putObjectIntoLayersStructure(objects[i]);
				
				// Hack for path fill bug (path fill is black, not transparent), also hotspots text background color
				if (objects[i].get('type') == 'group') {
					var groupObjects = objects[i].getObjects();
					for (var j = 0; j < groupObjects.length; j++) {
						if (groupObjects[j].type == 'path') {
							groupObjects[j].setFill('');
							break;
						} else if (groupObjects[j].custom && groupObjects[j].custom.type == 'label') {
							groupObjects[j].backgroundColor = 'rgba(255,255,255,0.8)';
						}
					}
					if (objects[i].custom.group == 'hotspot') {
						objects[i].hasControls = false;
					}
				}
				// Hack for this property not saving. Disable all rotation.
				objects[i].setControlVisible('mtr', false);
			}
			
			// Put objects into order
			that.setObjectOrder();
			
			// Move to start layer
			that.changeCanvasLayer(that.layer);
		});
	}
	
	// Select object
	this.canvas.on('object:selected', function(u) {
		var object = u.target;
		that.updateLayerControls(that.layer, object);
		if (that.layer == 'foreground') {
			that.setForegroundItemControls(object);
		}
	});
	
	// Clear selection
	this.canvas.on('selection:cleared', function() {
		that.updateLayerControls(that.layer);
	});
	
	this.canvas.on('object:scaling', function(e) {
		if (e.target.resizeToScale) {
			e.target.resizeToScale();
		}
	});
	
	this.canvas.on('object:modified', function(e) {
		that.changeMadeToPanel();
	});
	
};

// Marks when a change is made to the form, shows buttons and stops you navigating away
methods.changeMadeToPanel = function() {
	var that = this;
	if (!this.changingForm) {
		this.changingForm = true;
		window.onbeforeunload = function() {
			return 'You are currently editing this schematic. If you leave now you will lose any unsaved changes';
		}
		var warningMessage = 'Please either save your changes, or click Reset to discard them, before exiting the form editor.';
		zenarioO.disableInteraction(warningMessage);
		zenarioO.setButtons();
	}
};

methods.putObjectIntoLayersStructure = function(object) {
	if (object.custom && object.custom.layer && object.custom.group && object.custom.id) {
		this.layers[object.custom.layer].groups[object.custom.group].objects.push(object.custom.id);
	}
};

methods.removeObjectFromLayersStructure = function(object, clearLayer) {
	// Remove everything from a single layer
	if (clearLayer) {
		for (var group in this.layers[clearLayer].groups) {
			this.layers[clearLayer].groups[group].objects = [];
		}
	// Remove a single object
	} else if (object.custom && object.custom.layer && object.custom.group && object.custom.id) {
		var index = $.inArray(object.custom.id, this.layers[object.custom.layer].groups[object.custom.group].objects);
		if (index !== -1) {
			this.layers[object.custom.layer].groups[object.custom.group].objects.splice(index, 1);
		}
	}
};

methods.moveObjectInLayersStructure = function(object, moveTo) {
	if (object.custom && object.custom.layer && object.custom.group && object.custom.id) {
		var group = this.layers[object.custom.layer].groups[object.custom.group];
		var index = $.inArray(object.custom.id, group.objects);
		
		if (index !== -1) {
			var to = false;
			if (moveTo == 'first') {
				to = group.objects.length - 1;
			} else if (moveTo == 'last') {
				to = 0;
			} else if (moveTo == 'up') {
				to = (index == (group.objects.length - 1)) ? (group.objects.length - 1) : index + 1;
			} else if (moveTo == 'down') {
				to = (index == 0) ? 0 : index - 1;
			}
			
			if (to !== false) {
				group.objects.splice(to, 0, group.objects.splice(index, 1)[0]);
			}
		}
	}
};

methods.setObjectOrder = function() {
	// Get current objects indexed by unique Id
	var indexedObjects = {};
	var objects = this.canvas.getObjects();
	for (var i = 0; i < objects.length; ++i) {
		if (objects[i].custom.id) {
			indexedObjects[objects[i].custom.id] = objects[i];
		}
	}
	
	var ord = 0;
	
	// Create ordered arrays out of layers object
	var orderedLayers = _.values(this.layers);
	orderedLayers.sort(this.sortByOrd);
	
	// Also order groups in layers
	for (var i = 0; i < orderedLayers.length; ++i) {
		var orderedGroups = _.values(orderedLayers[i].groups);
		orderedGroups.sort(this.sortByOrd);
		
		
		// Finally get ordered fields (already in order)
		for (var j = 0; j < orderedGroups.length; ++j) {
			var orderedObjects = orderedGroups[j].objects;
			
			// Use this ordered list to set the canvas order
			for (var k = 0; k < orderedObjects.length; ++k) {
				indexedObjects[orderedObjects[k]].moveTo(ord++);
			}
		}
	}
};

methods.sortByOrd = function(a, b) {
	if (!a.ord || !b.ord) {
		return 0;
	} else if (a.ord < b.ord) {
		return -1
	} else if (a.ord > b.ord) {
		return 1
	}
	return 0;
};

methods.showButtons = function($buttons) {
	var that = this;
	
	if (this.changingForm) {
		//Change the buttons to apply/cancel buttons
		var mergeFields = {
			confirm_text: 'Save changes',
			cancel_text: 'Reset'
		};
		$buttons.html(this.microTemplate('zenario_organizer_apply_cancel_buttons', mergeFields));
			//Add an event to the Apply button to save the changes
			var lock = false
			$buttons.find('#organizer_applyButton').click(function() {
				var layerIsBackground = false;
			
			// Make sure to save as foreground so objects are not saved with opacity changed
			if (that.layer == 'background') {
				layerIsBackground = true;
				that.changeCanvasLayer('foreground', true);
			} else {
				// Save custom fields of active object
				var object = that.canvas.getActiveObject();
				if (object) {
					that.saveObjectFields(object);
				}
			}
			
			var json = JSON.stringify(that.canvas.toJSON(['custom']));
			var requests = zenarioO.getKey();
			
			requests.action = 'save_schematic';
			requests.json = json;
			
			zenarioA.nowDoingSomething('saving', true);
			zenario.ajax(that.getAJAXURL(), requests).after(function(responses) {
				if (layerIsBackground) {
					that.changeCanvasLayer('background', true);
				}
				
				window.onbeforeunload = false;
				zenarioO.enableInteraction();
				
				that.changingForm = false;
				zenarioO.setButtons();
				
				zenarioA.nowDoingSomething();
				var toast = {
					message_type: 'success',
					message: 'Your changes have been saved!'
				};
				zenarioA.toast(toast);
			});
		});
		
		// Reset changes
		$buttons.find('#organizer_cancelButton').click(function() {
			if (confirm('Are you sure you want to discard all your unsaved changes?')) {
				window.onbeforeunload = false;
				zenarioO.enableInteraction();
				
				that.changingForm = false;
				zenarioO.reload();
			}
		});
		
	} else {
		//Remove the buttons, but don't actually hide them as we want to keep some placeholder space there
		$buttons.html('').show();
	}
};

methods.addImageToCanvas = function(link, imageId, layer, group, isCached) {
	var that = this;
	if (link && imageId) {
		// Create new image object
		fabric.Image.fromURL(link, function(oImg) {
			var customProperties = {};
			if (isCached) {
				customProperties.cache_id = imageId;
			} else {
				customProperties.image_id = imageId;
			}
			if (layer) {
				customProperties.layer = layer;
				if (group) {
					customProperties.group = group;
				} else {
					customProperties.group = 'images';
				}
			}
			oImg.src = link;
			that.addToCanvas(oImg, customProperties);
		});
	}
};

methods.addToCanvas = function(object, customProperties) {
	// Add custom properties to new object
	if (customProperties) {
		if (!object.custom) {
			object.custom = {};
		}
		for (var name in customProperties) {
			object.custom[name] = customProperties[name];
		}
		object.custom.id = 't' + (++this.nextUniqueId);
		if (!object.custom.tuix) {
			object.custom.tuix = {};
		}
	}
	
	object.setControlVisible('mtr', false);
	this.canvas.add(object);
	
	this.putObjectIntoLayersStructure(object);
	this.setObjectOrder();
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
			// Go to background layer. Foreground objects are hidden.
			if (layer == 'background') {
				if (object.custom.layer == 'foreground') {
					object.visible = false;
				} else if (object.custom.layer == 'background') {
					object.selectable = true;
				}
			// Go to foreground layer. Background objects are disabled
			} else if (layer == 'foreground') {
				if (object.custom.layer == 'foreground') {
					object.visible = true;
					object.selectable = true;
				} else if (object.custom.layer == 'background') {
					object.selectable = false;
				} 
			// Go to preview layer, as would be seen on front end
			} else {
				object.visible = true;
				object.selectable = false;
			}
		});
		// Unselect and redraw
		if (!stopRender) {
			this.updateLayerControls(layer);
			this.canvas.deactivateAll().renderAll();
		}
	}
};

// Add a background image
methods.uploadImagesCallback = function(images) {
	if (images) {
		this.changeMadeToPanel();
		var image = images[0];
		this.addImageToCanvas(image.link, images[0].id, 'background', false, true);
	}
};

// Save values of an object
methods.saveObjectFields = function(object) {
	// Save custom fields
	var customFields = $('#organizer_schematic_builder__foreground_item_custom_fields').serializeArray();
	if (customFields) {
		if (!object.custom.tuix) {
			object.custom.tuix = {};
		}
		for (var i = 0; i < customFields.length; i++) {
			object.custom.tuix[customFields[i].name] = customFields[i].value;
		}
	}
};

methods.updateLayerControls = function(layer, object) {
	// Save previous object custom fields
	if (this.lastSelectedObjectId) {
		var lastSelectedObject = false;
		var objects = this.canvas.getObjects();
		for (var i = 0; i < objects.length; i++) {
			if (objects[i].custom.id == this.lastSelectedObjectId) {
				lastSelectedObject = objects[i];
				break;
			}
		}
		if (lastSelectedObject) {
			this.saveObjectFields(lastSelectedObject);
		}
		
	}
	
	this.lastSelectedObjectId = (object && object.custom) ? object.custom.id : false;
	
	// Update controls
	var mergeFields = this.getLayerControlsMergeFields(layer, object);
	
	var html = zenarioA.microTemplate('zenario_organizer_schematic_builder_controls', mergeFields);
	$('#organizer_schematic_builder .controls').html(html);
	this.initLayerControls(layer, object);
};

methods.getLayerControlsMergeFields = function(layer, object) {
	var that = this;
	var mergeFields = {
		layer: layer,
		itemSelected: false,
		customFields: false,
		is_system_schematic: this.tuix.schematic.is_system
	};
	if (object) {
		if (object.custom && object.custom.layer == layer) {
			mergeFields.itemSelected = true;
			if (object.custom.layer == 'background') {
				mergeFields.opacity = object.opacity !== undefined ? (object.opacity * 100).toFixed() : 100;
			}
		} else if (object._objects && object._objects.length == 2) {
			mergeFields.multiItemSelected = true;
		}
	}
	return mergeFields;
};

methods.setForegroundItemControls = function(object) {
	var that = this;
	// Load foreground object custom fields
	if (object 
		&& object.custom
		&& object.custom.layer == 'foreground'
	) {
		var mergeFields = {};
		if (object.custom.group == 'hotspot') {
			mergeFields.foreground_type = 'hotspot';
			mergeFields.foreground_item_controls_title = 'Editing a hotspot';
			
			this.tuix.hotspot_properties = {};
			this.tuix.hotspot_properties.field_id = {
				ord: 1,
				label: 'Field:',
				type: 'select',
				values: {},
				empty_value:' -- Select --'
			};
			this.tuix.hotspot_properties.latest_status_position = {
				ord: 2,
				label: 'Show field status:',
				type: 'select',
				values: {
					top: {
						ord: 1,
						label: 'Top'
					},
					left: {
						ord: 2,
						label: 'Left'
					},
					right: {
						ord: 3,
						label: 'Right'
					},
					bottom: {
						ord: 4,
						label: 'Bottom'
					},
					'': {
						ord: 5,
						label: 'Only show on click'
					}
				},
				value: 'right'
			};
			this.tuix.hotspot_properties.size = {
				ord: 3,
				label: 'Size:',
				type: 'select',
				values: {
					small: {
						ord: 1,
						label: 'Small'
					},
					medium: {
						ord: 1,
						label: 'Medium'
					},
					large: {
						ord: 1,
						label: 'Large'
					}
				},
				value: this.tuix.schematic.default_icon_size ? this.tuix.schematic.default_icon_size : 'medium'
			};
			
			if (this.tuix.schematic.is_system) {
				this.tuix.hotspot_properties.equipment_id = {
					ord: 0.1,
					label: 'Equipment:',
					pick_items: {
						path: this.tuix.hotspot_asset_panel.path,
						target_path: this.tuix.hotspot_asset_panel.target_path,
						select_phrase: 'Select equipment...'
					},
					//TODO look at this
					format_onchange: true
				}
			}
			
			// Set field values
			for (var prop in this.tuix.hotspot_properties) {
				if (object.custom.tuix[prop] !== undefined) {
					this.tuix.hotspot_properties[prop].value = object.custom.tuix[prop];
				}
			}
			
			// Load values list for field Id
			var requests = zenarioO.getKey();
			requests.action = 'format_hotspot_details';
			requests.fields = JSON.stringify(this.tuix.hotspot_properties);
			zenario.ajax(this.getAJAXURL(), requests).after(function(dataJSON) {
				
				var data = JSON.parse(dataJSON);
				if (data.fields) {
					var customFields = that.getItemControlsMergeFields(data.fields);
					
					mergeFields.customFields = customFields;
					// Pass mergefields to microtemplate
					var html = zenarioA.microTemplate('zenario_organizer_schematic_builder_item_controls', mergeFields);
					$('#organizer_schematic_builder__foreground_fields_wrapper').html(html);
					that.initForegroundItemControls(object);
				}
				if (data.field_id_extra_details) {
					that.tuix.field_id_extra_details = data.field_id_extra_details;
				}
			});
			
		} else if (object.custom.group == 'asset_block') {
			mergeFields.foreground_type = 'asset_block';
			mergeFields.foreground_item_controls_title = 'Editing an asset block';
			
			this.tuix.asset_block_properties = {};
			this.tuix.asset_block_properties.equipment_id = {
				ord: 1,
				label: 'Equipment:',
				pick_items: {
					path: this.tuix.asset_block_asset_panel.path,
					target_path: this.tuix.asset_block_asset_panel.target_path,
					select_phrase: 'Select equipment...'
				}
			}
			
			// Set field values
			for (var prop in this.tuix.asset_block_properties) {
				this.tuix.asset_block_properties[prop].value = object.custom.tuix[prop];
			}
			
			var customFields = that.getItemControlsMergeFields(this.tuix.asset_block_properties);
			if (customFields) {
				mergeFields.customFields = customFields;
			}
			
			// Pass mergefields to microtemplate
			var html = zenarioA.microTemplate('zenario_organizer_schematic_builder_item_controls', mergeFields);
			$('#organizer_schematic_builder__foreground_fields_wrapper').html(html);
			that.initForegroundItemControls(object);
		}
	}
};

methods.getItemControlsMergeFields = function(fields) {
	var customFields = [];
	for (var prop in fields) {
		var customField = _.clone(fields[prop]);
		// Get field name
		customField.name = prop;
		// If select then create ordered array from values
		if (customField.type == 'select') {
			var values = [];
			for (var valueId in customField.values) {
				var valueField = _.clone(customField.values[valueId]);
				valueField.value = valueId;
				values.push(valueField);
			}
			values.sort(this.sortByOrd);
			customField.values = values;
		}
		customFields.push(customField);
	}
	customFields.sort(this.sortByOrd);
	return customFields;
};

methods.initForegroundItemControls = function(object) {
	var that = this;
	// Delete foreground object
	$('#organizer_schematic_builder__delete_foreground_image').on('click', function() {
		var image = that.canvas.getActiveObject();
		if (image && image.custom.layer == 'foreground') {
			var name = 'foreground image';
			if (image.custom.group == 'hotspot') {
				name = 'hotspot';
			} else if (image.custom.group == 'asset_block') {
				name = 'asset block';
			}
			zenarioA.showMessage('Are you sure you want to remove this ' + name + '?', 'Yes', 'warning', true);
			$('#zenario_fbMessageButtons input.submit_selected').on('click', function() { 
				that.changeMadeToPanel();
				that.removeObjectFromLayersStructure(image);
				that.lastSelectedObjectId = false;
				that.canvas.remove(image).renderAll();
			});
		}
	});
	
	// Duplicate foreground object
	$('#organizer_schematic_builder__duplicate_foreground_image').on('click', function() {
		var image = that.canvas.getActiveObject();
		if (image && image.custom.layer == 'foreground') {
			that.changeMadeToPanel();
			that.duplicateObject(image);
		}
	});
	
	// Update hotspot latest value position
	$('#organizer_schematic_builder__foreground_item_custom_fields select[name="latest_status_position"]').on('change', function() {
		that.changeMadeToPanel();
		var position = $(this).val();
		var hotspot = that.canvas.getActiveObject();
		that.changeHotspotValueTextPosition(hotspot, position);
	});
	
	// Update hotspot size (small, medium, large)
	$('#forground_item_field__size').on('change', function() {
		that.changeMadeToPanel();
		// Save current fields to get current hotspot text position
		that.saveObjectFields(object);
		
		var hotspot = that.canvas.getActiveObject();
		var size = $(this).val();
		that.resizeHotspot(hotspot, size);
	});
	
	$('#forground_item_field__field_id').on('change', function() {
		that.changeMadeToPanel();
		// Always store current field Id
		that.saveObjectFields(object);
		
		var fieldId = $(this).val();
		var fieldExtraDetails = that.tuix.field_id_extra_details[fieldId];
		
		if (!fieldExtraDetails) {
			fieldExtraDetails = {};
		}
		
		if (fieldExtraDetails) {
			var hotspot = that.canvas.getActiveObject();
			var objects = hotspot.getObjects();
			// Update hotspot icon
			if (fieldExtraDetails.icon && that.tuix.icons[fieldExtraDetails.icon] && that.tuix.icons[fieldExtraDetails.icon]['default']) {
				for (var i = 0; i < objects.length; i++) {
					if (objects[i].custom && objects[i].custom.type == 'hotspot') {
						objects[i].setSrc(that.tuix.icons[fieldExtraDetails.icon]['default'], function() {
							that.canvas.renderAll();
						});
						break;
					}
				}
			}
			// Update hotspot label
			if (fieldExtraDetails.label) {
				for (var i = 0; i < objects.length; i++) {
					if (objects[i].custom && objects[i].custom.type == 'label') {
						objects[i].setText(fieldExtraDetails.label);
						break;
					}
				}
			}
			
			// If hotspot had a label, re-add this to recalculate its position
			if (hotspot.custom.tuix && hotspot.custom.tuix.latest_status_position) {
				that.changeHotspotValueTextPosition(hotspot, hotspot.custom.tuix.latest_status_position);
			}
		}

	});
	
	// Add field events
	for (var prop in this.tuix.hotspot_properties) {
		this.initForegroundItemField(prop, this.tuix.hotspot_properties[prop], object);
	}
	for (var prop in this.tuix.asset_block_properties) {
		this.initForegroundItemField(prop, this.tuix.asset_block_properties[prop], object);
	}
};

methods.initForegroundItemField = function(name, field, object) {
	var that = this;
	// Pick items events
	if (field.pick_items) {
		// Set picker names
		this.setAssetOrganizerName(object.custom.tuix[name], name, field);
		// Listen for picker changes
		$('#' + name + '__choose_button').on('click', function() {
			var path = field.pick_items.path;
			zenarioA.organizerSelect(function(path, key, row) {
				for (var i in key._items) {
					that.changeMadeToPanel();
					var id = zenario.decodeItemIdForOrganizer(i);
					$('#' + name).val(id);
					
					// After changing equipment reload the controls to get new field Ids list
					var currentObject = that.canvas.getActiveObject();
					that.saveObjectFields(currentObject);
					that.setForegroundItemControls(currentObject);
					break;
				}
			}, undefined, false, path, path, path, path, true);
		});
	}
};

methods.setAssetOrganizerName = function(id, fieldName, field) {
	var path = field.pick_items.target_path;
	var panel = zenarioA.getItemFromOrganizer(path, id);
	var name = zenarioA.formatOrganizerItemName(panel, id);
	
	$('#' + fieldName + '__display_text').val(name);
}

// Add JS events to controls section
methods.initLayerControls = function(layer, object) {
	var that = this;
	
	if (layer == 'background') {
		if (!object) {
			// Clear background images
			$('#organizer_schematic_builder__clear_background').on('click', function() {
				zenarioA.showMessage('Are you sure you want to remove everything from the background?', 'Yes', 'warning', true);
				$('#zenario_fbMessageButtons input.submit_selected').on('click', function() { 
					that.changeMadeToPanel();
					that.removeObjectFromLayersStructure(false, layer);
					that.clearCanvasLayer(layer);
				});
			});
			
			// Upload background image
			$('#organizer_schematic_builder__add_background_image').on('click', function() {
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
			});
			
			// Add background image from library
			$('#organizer_schematic_builder__add_library_background_image').on('click', function() {
				if (layer == 'background') {
					var path = 'zenario__content/panels/image_library';
					zenarioA.organizerSelect(function(path, key, row) {
						var requests = zenarioO.getKey();
						requests.action = 'get_library_image_link';
						requests.image_id = key.id;
						zenario.ajax(that.getAJAXURL(), requests).after(function(imageJSON) {
							that.changeMadeToPanel();
							var image = JSON.parse(imageJSON);
							that.addImageToCanvas(image.link, key.id, 'background');
						});
					}, undefined, false, path, path, path, path, true);
				}
			});
		} else {
			var image = that.canvas.getActiveObject();
			// Duplicate
			$('#organizer_schematic_builder__duplicate_background_image').on('click', function() {
				if (image && image.custom.layer == 'background') {
					that.changeMadeToPanel();
					that.duplicateObject(object);
				}
			});
			
			// Delete background image
			$('#organizer_schematic_builder__delete_background_image').on('click', function() {
				zenarioA.showMessage('Are you sure you want to remove this background image?', 'Yes', 'warning', true);
				$('#zenario_fbMessageButtons input.submit_selected').on('click', function() { 
					that.changeMadeToPanel();
					that.removeObjectFromLayersStructure(image);
					that.lastSelectedObjectId = false;
					that.canvas.remove(image).renderAll();
				});
			});
			
			// Opacity sider
			var objectOpacity = image.opacity !== undefined ? (image.opacity * 100) : 100;
			$('#organizer_schematic_builder__background_opacity_slider').slider({
				min: 0,
				max: 100,
				step: 1,
				value: objectOpacity,
				slide: function(event, ui) {
					$('#organizer_schematic_builder__background_opacity').val(ui.value + '%');
					object.opacity = (ui.value / 100);
					that.canvas.renderAll();
					that.changeMadeToPanel();
				}
			});
			
			// Move image forwards 1 place
			$('#organizer_schematic_builder__move_background_image_forward').on('click', function() {
				if (image && image.custom.layer == 'background') {
					that.changeMadeToPanel();
					that.moveObjectInLayersStructure(image, 'up');
					that.setObjectOrder();
				}
			});
			
			// Move image backwards 1 place
			$('#organizer_schematic_builder__move_background_image_back').on('click', function() {
				if (image && image.custom.layer == 'background') {
					that.changeMadeToPanel();
					that.moveObjectInLayersStructure(image, 'down');
					that.setObjectOrder();
				}
			});
			
			// Move image to first background image
			$('#organizer_schematic_builder__move_background_image_first').on('click', function() {
				if (image && image.custom.layer == 'background') {
					that.changeMadeToPanel();
					that.moveObjectInLayersStructure(image, 'first');
					that.setObjectOrder();
				}
			});
			
			// Move image to last background image
			$('#organizer_schematic_builder__move_background_image_last').on('click', function() {
				if (image && image.custom.layer == 'background') {
					that.changeMadeToPanel();
					that.moveObjectInLayersStructure(image, 'last');
					that.setObjectOrder();
				}
			});
			
			// Go back to background layer
			$('#organizer_schematic_builder__back_to_background').on('click', function() {
				that.changeCanvasLayer('background');
			});
			
			// Connect objects with arrows
			$('#organizer_schematic_builder__create_line_1').on('click', function() {
				if (layer == 'background') {
					var group = that.canvas.getActiveGroup();
					if (group && group._objects.length == 2 && !group.hasMoved()) {
						that.changeMadeToPanel();
						var groupObjects = group._objects;
						var lineType = $('#organizer_schematic_builder__arrow_type').val();
						that.connectWithArrows(groupObjects, 1, lineType);
					}
				}
			});
			$('#organizer_schematic_builder__create_line_2').on('click', function() {
				if (layer == 'background') {
					var group = that.canvas.getActiveGroup();
					if (group && group._objects.length == 2 && !group.hasMoved()) {
						that.changeMadeToPanel();
						var groupObjects = group._objects;
						var lineType = $('#organizer_schematic_builder__arrow_type').val();
						that.connectWithArrows(groupObjects, 2, lineType);
					}
				}
			});
		}
	} else if (layer == 'foreground') {
		if (!object) {
			// Clear foreground images
			$('#organizer_schematic_builder__clear_foreground').on('click', function() {
				zenarioA.showMessage('Are you sure you want to remove everything from the foreground?', 'Yes', 'warning', true);
				$('#zenario_fbMessageButtons input.submit_selected').on('click', function() { 
					that.changeMadeToPanel();
					that.removeObjectFromLayersStructure(false, layer);
					that.clearCanvasLayer(layer);
				});
			});
			
			// Add foreground hotspot
			$('#organizer_schematic_builder__add_foreground_hotspot').on('click', function() {
				that.changeMadeToPanel();
				that.addHotspot();
			});
			
			// Add foreground asset block
			$('#organizer_schematic_builder__add_foreground_asset_block').on('click', function() {
				that.changeMadeToPanel();
				that.addAssetBlock();
			});
		} else {
			// Go back to foreground layer
			$('#organizer_schematic_builder__back_to_foreground').on('click', function() {
				that.changeCanvasLayer('foreground');
			});
		}
	} else if (layer == 'start_layer') {
		// Switch to edit the background
		$('#organizer_schematic_builder__edit_background').on('click', function() {
			that.changeCanvasLayer('background');
		});
		
		// Switch to edit the foreground
		$('#organizer_schematic_builder__edit_foreground').on('click', function() {
			that.changeCanvasLayer('foreground');
		});
	}
	
	// Go back to start layer
	$('#organizer_schematic_builder__back_to_start').on('click', function() {
		that.changeCanvasLayer('start_layer');
	});
};

methods.duplicateObject = function(object) {
	if (this.layer == 'foreground') {
		this.saveObjectFields(object);
		if (object.custom.group == 'hotspot') {
			this.addHotspot(object);
		} else if (object.custom.group == 'asset_block') {
			this.addAssetBlock(object);
		}
	} else {
		var newObject = fabric.util.object.clone(object);
		newObject.set("top", object.top + 5);
		newObject.set("left", object.left + 5);
		this.addToCanvas(newObject);
	}
};

// Change the position of the latest value text for a hotspot
methods.changeHotspotValueTextPosition = function(hotspot, position) {
	// Get current field text
	var text = '[Latest value]';
	if (hotspot.custom.tuix.field_id) {
		var details = this.tuix.field_id_extra_details[hotspot.custom.tuix.field_id];
		if (details.label) {
			text = details.label;
		}
	}
	// Remove existing text
	var objects = hotspot.getObjects();
	var objectsToRemove = [];
	for (var i = 0; i < objects.length; ++i) {
		if (objects[i].custom.type == 'label') {
			text = objects[i].getText();
			objectsToRemove.push(objects[i]);
		}
	}
	for (var i = 0; i < objectsToRemove.length; ++i) {
		hotspot.removeWithUpdate(objectsToRemove[i]);
	}
	
	var width = parseInt(hotspot.getWidth());
	var height = parseInt(hotspot.getHeight());
	
	// Add text
	if (position) {
		var properties = {
			hasBorders: false,
			fontSize: 15,
			fontFamily: 'Tahoma, Geneva, sans-serif',
			backgroundColor: 'rgba(255,255,255,0.8)',
			top: hotspot.getTop(),
			left: hotspot.getLeft(),
			custom: {
				type: 'label'
			}
		};
		if (position == 'top') {
			properties.top -= 25;
			properties.left += width / 2;
			properties.originX = 'center';
		} else if (position == 'left') {
			properties.top += (height / 2);
			properties.left -= 10;
			properties.originX = 'right';
			properties.originY = 'center';
		} else if (position == 'right') {
			properties.top += (height / 2);
			properties.left += width + 10;
			properties.originX = 'left';
			properties.originY = 'center';
		} else if (position == 'bottom') {
			properties.top += height + 5;
			properties.left += width / 2;
			properties.originX = 'center';
		}
		hotspot.addWithUpdate(new fabric.Text(text, properties));
	}
	
	// Render canvas
	this.canvas.renderAll();
};

// Change a hotspots size
methods.resizeHotspot = function(hotspot, size) {
	// Get size in pixels
	var diameter = this.tuix.icon_sizes.small;
	if (size == 'small') {
		diameter = this.tuix.icon_sizes.small;
	} else if (size == 'medium') {
		diameter = this.tuix.icon_sizes.medium;
	} else if (size == 'large') {
		diameter = this.tuix.icon_sizes.large;
	}
	var objects = hotspot.getObjects();
	for (var i = 0; i < objects.length; ++i) {
		// Find the target in the hotspot group
		if (objects[i].custom && objects[i].custom.type == 'hotspot') {
			// Resize
			objects[i].scaleToHeight(diameter);
			// Hack to get object to resize after member is resized (might be a better way)
			var dummyObject = new fabric.Circle();
			hotspot.addWithUpdate(dummyObject);
			hotspot.removeWithUpdate(dummyObject);
			break;
		}
	}
	
	// If hotspot had a label, re-add this to recalculate its position
	if (hotspot.custom.tuix && hotspot.custom.tuix.latest_status_position) {
		this.changeHotspotValueTextPosition(hotspot, hotspot.custom.tuix.latest_status_position);
	}
	
	this.canvas.renderAll();
};

// Add a hotspot
methods.addHotspot = function(target) {
	var that = this;
	var objects = [];
	var hotspot = new fabric.Group([], {
		hasControls: false
	});
	var top = 40;
	var left = 40;
	
	if (target) {
		top = target.top + 5;
		left = target.left + 5;
	}
	
	var url = this.tuix.icons['undefined']['default'];
	fabric.Image.fromURL(url, function(oImg) {
		oImg.custom = {
			type: 'hotspot'
		};
		var position = 'right';
		var size = that.tuix.schematic.default_icon_size ? that.tuix.schematic.default_icon_size : 'medium';
		if (target) {
			position = target.custom.tuix.latest_status_position;
			size = target.custom.tuix.size;
			hotspot.custom = {
				tuix: $.extend(true, {}, target.custom.tuix)
			};
			
			if (target.custom.tuix.field_id) {
				var fieldExtraDetails = that.tuix.field_id_extra_details[target.custom.tuix.field_id];
				if (fieldExtraDetails.icon 
					&& that.tuix.icons[fieldExtraDetails.icon] 
					&& that.tuix.icons[fieldExtraDetails.icon]['default']
				) {
					oImg.setSrc(that.tuix.icons[fieldExtraDetails.icon]['default'], function() {
						that.canvas.renderAll();
					});
				}
			}
		}
		if (that.tuix.icon_sizes[size]) {
			oImg.scaleToHeight(that.tuix.icon_sizes[size]);
		}
		
		hotspot.addWithUpdate(oImg);
		var customProperties = {
			layer: 'foreground',
			group: 'hotspot'
		};
		that.addToCanvas(hotspot, customProperties);
		
		// Default show text to the right
		that.changeHotspotValueTextPosition(hotspot, position);
		
		hotspot.set('top', top);
		hotspot.set('left', left);
		hotspot.setCoords();
		
		that.canvas.renderAll();
	});
};

methods.addAssetBlock = function(target) {
	var width = 160;
	var height = 200;
	var left = 20;
	var top = 20;
	if (target) {
		width = target.width;
		height = target.height;
		left = target.left + 5;
		top = target.top + 5;
	}
	
	var rectangle = new fabric.Rect({
		width: width,
		height: height,
		left: left,
		top: top,
		fill: 'transparent',
		opacity: this.assetBlockOpacity,
		strokeWidth: 3,
		stroke: '#0080FF',
		strokeLineJoin: 'round'
	});
	
	var customProperties = {
		layer: 'foreground',
		group: 'asset_block'
	};
	
	if (target) {
		rectangle.custom = $.extend(true, {}, target.custom);
	}
	
	this.addToCanvas(rectangle, customProperties);
};

// Method to connect 2 background objects with a flowchart arrow
methods.connectWithArrows = function(groupObjects, mode, lineType) {
	var objects = [];
	var group = new fabric.Group([]);
	
	// object 1 is furthest left
	if (groupObjects[0].originalLeft <= groupObjects[1].originalLeft) {
		objects.push(groupObjects[0], groupObjects[1]);
	} else {
		objects.push(groupObjects[1], groupObjects[0]);
	}
	
	// Get center ponints of objects
	var w1 = objects[0].width * objects[0].scaleX;
	var h1 = objects[0].height * objects[0].scaleY;
	
	var x1 = objects[0].originalLeft + (w1 / 2);
	var y1 = objects[0].originalTop + (h1 / 2);
	
	var w2 = objects[1].width * objects[1].scaleX;
	var h2 = objects[1].height * objects[1].scaleY;
	
	var x2 = objects[1].originalLeft + (w2 / 2);
	var y2 = objects[1].originalTop + (h2 / 2);
	
	var pathString = '';
	var strokeWidth = 3;
	var arrowOffset = 7;
	
	// Draw 1 line
	// Check if we can draw a single line
	
	// Horizontal line from object 1 center
	if ((y1 >= (y2 - (h2 / 2))) && (y1 <= (y2 + (h2 / 2)))) {
		x1 += (w1 / 2);
		x2 -= (w2 / 2);
		
		if (lineType == 'double' || lineType == 'single_left') {
			this.drawTriangle(group, -90, x1, y1, arrowOffset, strokeWidth / 2);
			x1 += arrowOffset;
		}
		if (lineType == 'double' || lineType == 'single_right') {
			this.drawTriangle(group, 90, x2, y1, -arrowOffset, strokeWidth / 2);
			x2 -= arrowOffset;
		}
		
		pathString = 'M ' + x1  + ' ' + y1 + ' L ' + x2 + '  ' + y1;
	// Horizontal line from object 2 center
	} else if ((y2 >= (y1 - (h1 / 2))) && (y2 <= (y1 + (h1 / 2)))) {
		x1 += (w1 / 2);
		x2 -= (w2 / 2);
		
		if (lineType == 'double' || lineType == 'single_left') {
			this.drawTriangle(group, -90, x1, y2, arrowOffset, strokeWidth / 2);
			x1 += arrowOffset;
		}
		if (lineType == 'double' || lineType == 'single_right') {
			this.drawTriangle(group, 90, x2, y2, -arrowOffset, strokeWidth / 2);
			x2 -= arrowOffset;
		}
		
		pathString = 'M ' + x1  + ' ' + y2 + ' L ' + x2 + '  ' + y2;
	// Vertical line from object 1 center
	} else if ((x1 >= (x2 - (w2 / 2))) && (x1 <= (x2 + (w2 / 2)))) {
		var lineUp = (y2 <= y1);
		if (lineUp) {
			y1 -= (h1 / 2);
			y2 += (h2 / 2);
			
			if (lineType == 'double' || lineType == 'single_left') {
				this.drawTriangle(group, 0, x1, y2, strokeWidth / 2, arrowOffset);
				y2 += arrowOffset;
			}
			if (lineType == 'double' || lineType == 'single_right') {
				this.drawTriangle(group, 180, x1, y1, strokeWidth / 2, -arrowOffset);
				y1 -= arrowOffset;
			}
			
		} else {
			y1 += (h1 / 2);
			y2 -= (h2 / 2);
			
			if (lineType == 'double' || lineType == 'single_left') {
				this.drawTriangle(group, 0, x1, y1, strokeWidth / 2, arrowOffset);
				y1 += arrowOffset;
			}
			if (lineType == 'double' || lineType == 'single_right') {
				this.drawTriangle(group, 180, x1, y2, strokeWidth / 2, -arrowOffset);
				y2 -= arrowOffset;
			}
		}
		
		pathString = 'M ' + x1  + ' ' + y1 + ' L ' + x1 + '  ' + y2;
	// Vertical line from object 2 center
	} else if ((x2 >= (x1 - (w1 / 2))) && (x2 <= (x1 + (w1 / 2)))) {
		var lineUp = (y2 <= y1);
		if (lineUp) {
			y1 -= (h1 / 2);
			y2 += (h2 / 2);
			
			if (lineType == 'double' || lineType == 'single_left') {
				this.drawTriangle(group, 0, x2, y2, strokeWidth / 2, arrowOffset);
				y2 += arrowOffset;
			}
			if (lineType == 'double' || lineType == 'single_right') {
				this.drawTriangle(group, 180, x2, y1, strokeWidth / 2, -arrowOffset);
				y1 -= arrowOffset;
			}
			
		} else {
			y1 += (h1 / 2);
			y2 -= (h2 / 2);
			
			if (lineType == 'double' || lineType == 'single_left') {
				this.drawTriangle(group, 0, x2, y1, strokeWidth / 2, arrowOffset);
				y1 += arrowOffset;
			}
			if (lineType == 'double' || lineType == 'single_right') {
				this.drawTriangle(group, 180, x2, y2, strokeWidth / 2, -arrowOffset);
				y2 -= arrowOffset;
			}
		}
		
		pathString = 'M ' + x2  + ' ' + y1 + ' L ' + x2 + '  ' + y2;
	}
	
	// Draw 2 lines
	if (!pathString) {
		// mode 1: line from side of object 1
		if (mode == 1) {
			x1 += w1 / 2;
			if (y1 <= y2) {
				y2 -= h2 / 2;
				if (lineType == 'double' || lineType == 'single_right') {
					this.drawTriangle(group, 180, x2, y2, strokeWidth / 2, -arrowOffset);
					y2 -= arrowOffset;
				}
			} else {
				y2 += h2 / 2;
				if (lineType == 'double' || lineType == 'single_right') {
					this.drawTriangle(group, 0, x2, y2, strokeWidth / 2, arrowOffset);
					y2 += arrowOffset;
				}
			}
			if (lineType == 'double' || lineType == 'single_left') {
				this.drawTriangle(group, -90, x1, y1, arrowOffset, strokeWidth / 2);
				x1 += arrowOffset;
			}
			pathString = 'M ' + x1 + ' ' + y1 + ' L ' + x2 + ' ' + y1 + ' L ' + x2 + ' ' + y2;
		
		// mode 2: line from top/bottom of object 1
		} else if (mode == 2) {
			x2 -= w2 / 2;
			if (y1 <= y2) {
				y1 += h1 / 2;
				if (lineType == 'double' || lineType == 'single_left') {
					this.drawTriangle(group, 0, x1, y1, strokeWidth / 2, arrowOffset);
					y1 += arrowOffset;
				}
			} else {
				y1 -= h1 / 2;
				if (lineType == 'double' || lineType == 'single_left') {
					this.drawTriangle(group, 180, x1, y1, strokeWidth / 2, -arrowOffset);
					y1 -= arrowOffset;
				}
			}
			if (lineType == 'double' || lineType == 'single_right') {
				this.drawTriangle(group, 90, x2, y2, -arrowOffset, strokeWidth / 2);
				x2 -= arrowOffset;
			}
			pathString = 'M ' + x1 + ' ' + y1 + ' L ' + x1 + ' ' + y2 + ' L ' + x2 + ' ' + y2;
		}
	}
	
	// Create path and add to arrow group
	var path = new fabric.Path(pathString, {
		fill: '', 
		stroke: 'black', 
		strokeWidth: strokeWidth,
		hasBorders: false
	});
	group.addWithUpdate(path);
	
	// Custom properties for arrow group
	var customProperties = {
		layer: 'background',
		group: 'images'
	};
	
	// Add arrow to canvas and move to background layer
	this.addToCanvas(group, customProperties);
	
	// Re-render canvas, unselect objects
	this.canvas.deactivateAll().renderAll();
}

// Method to add a triangle to a group for arrows
methods.drawTriangle = function(group, angle, x, y, offsetX, offsetY) {
	var triangle = new fabric.Triangle({
		left: x + offsetX,
		top: y + offsetY,
		originX: 'center',
		originY: 'center',
		fill: 'black',
		stroke: 'black',
		opacity: 1,
		angle: angle,
		height: 12,
		width: 10,
		hasBorders: false
	});
	group.addWithUpdate(triangle);
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

methods.returnShowLeftColumn = function() {
	return false;
};

methods.getAJAXURL = function() {
	return URLBasePath + 'zenario/ajax.php?' 
		+ '__pluginClassName__=' + this.tuix.class_name 
		+ '&__path__=' + zenarioO.path 
		+ '&method_call=handleOrganizerPanelAJAX';
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