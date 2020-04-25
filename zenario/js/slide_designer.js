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

/*
	This file contains JavaScript source code.
	The code here is not the code you see in your browser. Before this file is downloaded:
	
		1. Compilation macros are applied (e.g. "foreach" is a macro for "for .. in ... hasOwnProperty").
		2. It is minified (e.g. using Google Closure Compiler).
	
	For more information, see js_minify.shell.php.
*/


zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioT, zenarioAB, zenarioAT, zenarioO,
	encodeURIComponent, defined, engToBoolean, get, htmlspecialchars, jsEscape, phrase,
	extensionOf, methodsOf, has,
	zenarioSD
) {
	"use strict";


var formId = 'settings',
	linksId = 'download_links',
	closeButtonId = 'close_button';

zenarioSD.gridId = 'grid';
zenarioSD.globalName = 'zenarioSD';
zenarioSD.addToolbarId = 'grid_add_toolbar';
zenarioSD.mtPrefix = 'zenario_slide_designer_';


zenarioSD.gridId = 'grid';

zenarioSD.load = function() {
	var data = JSON.parse($('#data').val());
	
	zenarioSD.init(data, 'grid', data.layout_id, data.file_base_name);
	
	zenarioSD.drawForm();
};


zenarioSD.clearAddToolbar = function() {
	$('#' + zenarioSD.addToolbarId).hide().html('');
};

zenarioSD.drawAddToolbar = function() {
	$('#' + zenarioSD.addToolbarId).show().html(
		zenarioSD.microTemplate(zenarioSD.mtPrefix + 'add_toolbar', {})
	);

	$('#' + zenarioSD.addToolbarId + ' a')
		.draggable({
			connectToSortable: '.zenario_grids',
			helper: "clone",
			revert: "invalid"
		});
	$( "#"+ zenarioSD.addToolbarId ).draggable({ handle: "div.draggable" });
};


$(document).ready(function(){
   if(data && data.name!= null){
        document.title = 'Editing "'+ data.name +'" with Slide Designer';
    }
    else {
        document.title = 'Editing with Slide Designer';
    }
    $( "#"+ zenarioSD.addToolbarId + ' .draggable' ).hover(function(){
    $(this).css("cursor", "move");
    }, function(){
    $(this).css("cursor", "pointer");
 });
});


/**$('#load').on('click', zenarioSD.load);

$('#save').on('click', function () {
	var data = zenarioSD.ajaxData();
	$('#data').val(data);
});
**/
//Allow an Admin to save a grid design to the database
zenarioSD.save = function(saveAs) {
	
	if (!zenarioSD.layoutId) {
		saveAs = true;
	}
	
	var data;
	if(!confirm(phrase.gridSaveConfirmMessage)){

	    return;
	}
	if (saveAs) {
		var cssClasses = ['zenario_grid_box', 'zenario_grid_new_layout_box'];
		
		if (data = zenario.nonAsyncAJAX(
			zenarioSD.ajaxURL(),
			{
				saveas: 1,
				data: zenarioSD.ajaxData(),
				layoutId: zenarioSD.layoutId
			},
			true
		)) {
			
			$.colorbox({
				transition: 'none',
				html: zenarioSD.microTemplate(zenarioSD.mtPrefix + 'save_prompt', {name: zenarioSD.newLayoutName || data.oldLayoutName || ''}),
				
				onOpen: function() { zenario.addClassesToColorbox(cssClasses); },
				onClosed: function() { zenario.removeClassesToColorbox(cssClasses); },
				
				onComplete: function() {
					$('#zenario_grid_new_layout_save').click(function() {
						$('#zenario_grid_error').hide();
						
						if (data = zenario.nonAsyncAJAX(
							zenarioSD.ajaxURL(), {
								saveas: 1,
								confirm: 1,
								data: zenarioSD.ajaxData(),
								layoutId: zenarioSD.layoutId,
								layoutName: zenarioSD.newLayoutName = get('zenario_grid_layout_name').value
							},
							true
						)) {
							if (data.error) {
								$('#zenario_grid_error').html(htmlspecialchars(data.error)).slideDown();
							} else {
								$.colorbox.close();
								zenarioSD.layoutName = zenarioSD.newLayoutName;
								zenarioSD.markAsSaved(data);
							}
						} else {
							$.colorbox.close();
						}
					});
					get('zenario_grid_layout_name').focus();
				}
			});
		}
	
	} else {
		if (data = zenario.nonAsyncAJAX(
			zenarioSD.ajaxURL(),
			{
				save: 1,
				data: zenarioSD.ajaxData(),
				layoutId: zenarioSD.layoutId
			},
			true
		)) {
			if (data.success) {
				zenarioSD.markAsSaved(data, true);
				
			
			} else {
				zenarioA.floatingBox(data.message, phrase.gridSave, 'warning', true, true);
				
				$('#zenario_fbMessageButtons .submit_selected').click(function() {
					setTimeout(function() {
						var data;
						if (data = zenario.nonAsyncAJAX(
							zenarioSD.ajaxURL(),
							{
								save: 1,
								data: zenarioSD.ajaxData(),
								layoutId: zenarioSD.layoutId,
								confirm: 1
							},
							true
						)) {
							zenarioSD.markAsSaved(data);
						}
					}, 50);
				});
			}
		}
	}
	
	//Show a success message after saving
	toastr.success(phrase.gridSaveSuccessMessage);
	
	
	//If this was opened from a conductor, refresh the conductor when we save
	if (windowParent
	 && windowParent.zenario_conductor
	 && windowParent.zenario_conductor.refreshAll) {
		windowParent.zenario_conductor.refreshAll();
	}
};


//Remember that we last saved at this point in the undo-history
zenarioSD.markAsSaved = function(data, useMessageBoxForSuccessMessage) {
	
	zenarioSD.savedAtPos = zenarioSD.pos;
	
	if (defined(data.layoutId)) {
		zenarioSD.layoutId = data.layoutId;
	}
	
	//Re-record the cell names
	zenarioSD.rememberNames(zenarioSD.data.cells);
	
	if (data.success) {
		if (useMessageBoxForSuccessMessage && zenarioA.floatingBox) {
			zenarioA.floatingBox(data.success, true, 'success');
		} else {
			toastr.success(data.success);
		}
	}
	
	if (windowParent
	 && windowParent.zenarioO.init) {
		switch (windowParent.zenarioO.path) {
			case 'zenario__layouts/panels/layouts':
				if (data.layoutId) {
					windowParent.zenarioO.refreshToShowItem(data.layoutId);
				}
				
				break;
		}
	}
};


zenarioSD.ajaxURL = function() {
	return URLBasePath + 'zenario/slide_designer/ajax.php?schemaId=' + (zenarioSD.data && zenarioSD.data.layout_for_id);
};


zenarioSD.ajaxSrc = function() {
	var data = zenarioSD.ajaxData(),
		src = zenarioSD.ajaxURL() + '?data=' + encodeURIComponent(data);
	
	
	return src;
};



zenarioSD.drawForm = function() {
	
	

	zenarioSD.editing = true;
	zenarioSD.drawEditor();
	
	
};

zenarioSD.cellLabel = function(cell) {
	return cell.label;
};


zenarioSD.drawEditor = function(
	thisContId, thisCellId, 
	levels,
	gCols, gColWidth, gGutter, gGutterLeftEdge, gGutterRightEdge,
	gGutterNested,
	gColWidthPercent, gGutterWidthPercent
) {
	
	if (!levels) {
        var m = {formId: formId},
            html = zenarioSD.microTemplate(zenarioSD.mtPrefix + 'top', m);

    
        $('.ui-tooltip').remove();
        get(formId).innerHTML = html;
        zenario.tooltips('#' + formId);
    
    
            $('#' + formId + ' .zenario_grid_setting_undo').click(function() {
                zenarioSD.undo();
            });
    
    
    
            $('#' + formId + ' .zenario_grid_setting_redo').click(function() {
                zenarioSD.redo();
            });
	    
	}
	if(levels){
	var elId =thisContId + '__cell' + levels.join('-');

    $('#' + elId + 's').on('click', function() {

    });
    
    }
   
	var rv = methodsOf(zenarioG).drawEditor.call(this,
		thisContId, thisCellId, 
		levels,
		gCols, gColWidth, gGutter, gGutterLeftEdge, gGutterRightEdge,
		gGutterNested,
		gColWidthPercent, gGutterWidthPercent
	);
	
	
	
	return rv;
};

//Use the Slide Designer microtemplates, not the admin microtemplates
zenarioSD.microTemplate = function(template, data, filter) {
	
	var html,
		needsTidying;
	
	needsTidying = zenario.addLibPointers(data, zenarioSD);
	
		html = zenario.microTemplate(template, data, filter, zenarioSD.microTemplates);
	
	if (needsTidying) {
		zenario.tidyLibPointers(data);
	}
	
	return html;
};






//Define a forms library for the pop-out boxes with the slot properties
var slotObjectSP,
	zenarioSP = window.zenarioSP = zenario.createZenarioLibrary('SP', zenarioFEA),
	methodsSP = methodsOf(zenarioSP);


//Handle the "Edit Properties" button for Slide Designer
zenarioSD.editProperties = function(el) {
	//Work out which element the edit properties button is for
	var forEl = $(el).data('for'),
		//Get the data for that element
		$el = $('#' + forEl),
		i = $el.data('i'),
		levels = thus.getLevels($el),
		oLevels = levels,
		data = zenarioSD.data;
	
	//Naviagte down through any recursion, and get the specific data for this slot
	if (levels) {
		levels = levels.split('-');
		foreach (levels as var l) {
			l *= 1;
			data = data.cells[1*levels[l]];
		}
	}
	data = data.cells[i];
	if (data.class_name) {
		//If a class name has been set, start off in the settings box.
		zenarioSD.openSlotSettingsBox(data, oLevels, i);
	} else {
		//If a class name hasn't been set yet, start off in the plugin/mode selector.
		zenarioSD.openSlotPluginAndModeBox(data, oLevels, i);
	}
};



//Base function for opening slot properties
zenarioSD.openSlotPropertiesBox = function(cellProperties, path, mode, moduleClassName, popoutClass, popoutContainerId, globalName, levels, i) {
	var lib,
		idVarName = undefined,
		parent = undefined,
		pages = undefined,
		noPlugin = true,
		inPopout = true,
		request = {
			slideLayoutId: zenarioSD.data.id,
			slideLayoutFor: zenarioSD.data.layout_for,
			slideLayoutForId: zenarioSD.data.layout_for_id,
			schemaId: zenarioSD.data.layout_for_id
		};
	
	//Make a new instance, if an existing one doesn't already exist
	if (!(lib = window[globalName])) {
		lib = window[globalName] = new zenarioSP();
	}
	//Remember the original slot properties and positions to help later when saving
	lib.cellProperties = JSON.stringify(cellProperties);
	lib.slotLevels = levels;
	lib.slotI = i;
	lib.showCloseButtonAtTopRight = true;
	
	//Start the FEA forms library
	lib.init(globalName, 'fea', moduleClassName, popoutContainerId, path, request, mode, pages, idVarName, noPlugin, parent, inPopout, popoutClass);
	
	return false;
};

//Open the plugin/mode selector to select what is in a slot
zenarioSD.openSlotPluginAndModeBox = function(cellProperties, levels, i) {
	var path = 'slot_plugin_and_mode',
		mode = path,
		moduleClassName = 'zenario_common_features',
		popoutClass = path,
		popoutContainerId = popoutClass,
		globalName = popoutClass;
	
	return zenarioSD.openSlotPropertiesBox(cellProperties, path, mode, moduleClassName, popoutClass, popoutContainerId, globalName, levels, i);
};

//Open the slot settings box for the plugin in a slot
zenarioSD.openSlotSettingsBox = function(cellProperties, levels, i) {
	
	
	//Get info on what plugin/mode was selected
	var moduleClassName = cellProperties.class_name,
		settings = cellProperties.settings || {},
		pluginMode = settings.mode,
		path = 'slot_settings_';
	
	//Don't allow this to run if a module hasn't been set yet
	if (!moduleClassName) {
		return false;
	}
	
	//The path that's opened depends on the plugin that's picked for the slot, and the mode that the plugin is in (if the plugin uses modes).
	path += moduleClassName;
	
	if (pluginMode) {
		path += '_' + pluginMode;
	}
	
	var mode = path,
		popoutClass = 'slot_settings',
		popoutContainerId = popoutClass,
		globalName = popoutClass;
	
	return zenarioSD.openSlotPropertiesBox(cellProperties, path, mode, moduleClassName, popoutClass, popoutContainerId, globalName, levels, i);
};


//These are always forms, never lists
methodsSP.typeOfLogic = function() {
	return 'form';
};


//When calling the fillVisitorTUIX() function, the forms library should send the current slot properties to the server
methodsSP.modifyPostOnLoad = function() {
	var gridProperties = _.extend({}, zenarioSD.data);
	delete gridProperties.cells;
	return {cellProperties: thus.cellProperties, gridProperties: gridProperties, slotLevels: thus.slotLevels, slotI: thus.slotI};
};

//When saving, merge the updates in with the existing properties
methodsSP.closeAndUpdateSlot = function(updatedProperties, updatedSettings, switchBoxes) {
	
	
	
	var cellProperties = JSON.parse(thus.cellProperties),
		ci, prop;
	
	if (updatedProperties) {
		_.extend(cellProperties, updatedProperties);
		
		foreach (cellProperties as ci => prop) {
			if (!defined(prop)) {
				delete cellProperties[ci];
			}
		}
	}
	
	cellProperties.settings = cellProperties.settings || {};
	
	if (updatedSettings) {
		_.extend(cellProperties.settings, updatedSettings);
		
		foreach (cellProperties.settings as ci => prop) {
			if (!defined(prop)) {
				delete cellProperties.settings[ci];
			}
		}
	}
	
	window.lastSlotProperties = cellProperties;

	var levels = thus.slotLevels,
		i = thus.slotI,
		data;
	
	//Have the option to switch between the SlotPluginAndModeBox and the SlotSettingsBox
	if (switchBoxes) {
		if (thus.path == 'slot_plugin_and_mode') {
			setTimeout(function() {
				zenarioSD.openSlotSettingsBox(cellProperties, levels, i);
			}, 0);
		} else {
			setTimeout(function() {
				zenarioSD.openSlotPluginAndModeBox(cellProperties, levels, i);
			}, 0);
		}
	} else {
		data = zenarioSD.data;
		
		//Navigate down through any recursion to the right slot
		if (levels) {
			levels = ('' + levels).split('-');
			foreach (levels as var l) {
				l *= 1;
				data = data.cells[1*levels[l]];
			}
		}
		
		
	
		//Set the updates back into the original data 
		data.cells[i] = cellProperties;
		
		zenarioSD.change();
	}
	
	return false;
};





if (windowParent
 && windowParent.$
 && windowParent.$.colorbox) {
	$('#' + closeButtonId).click(function() {
		windowParent.$.colorbox.close();
	});
}


},
	window.zenarioSD = new zenarioG
);