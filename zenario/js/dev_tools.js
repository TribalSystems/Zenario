/*
 * Copyright (c) 2014, Tribal Limited
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
		3. It may be wrapped togther with other files (this is to reduce the number of http requests on a page).
	
	For more information, see js_minify.shell.php for steps (1) and (2), and "inc.js.php" files for step (3).
*/


window.devTools = function() {};
window.editor = ace.edit('editor');

(function(
	URLBasePath,
	window, document, opener,
	zenario, zenarioA, devTools, editor,
	get, engToBoolean, htmlspecialchars, ifNull, jsEscape, phrase,
	$toolbar, $editor, $sidebar, $sidebarInner,
	undefined) {
		"use strict";

//devTools.editingPositions = {};
devTools.internalCMSProperties = {
	class_name: true,
	_h: true,
	_h_js: true,
	_sync: true
};

//A little hack to turn on flagging undefined additional properties in tv4
devTools.initR = function(schema) {
	if (typeof schema == 'object') {
		
		foreach (schema as var i) {
			devTools.initR(schema[i]);
		}
		
		if (schema.properties
		 && !schema.additionalProperties) {
			schema.additionalProperties = false;
		}
	}
};


devTools.init = function(mode, schemaName, schema, skMap) {
	devTools.schemaName = schemaName;
	devTools.mode = mode;
	devTools.skMap = mode == 'zenarioO' && skMap;
	devTools.rootPath = '';
	devTools.pages = {};
	
	if (schema) {
		devTools.initR(schema);
		
		//Create a few copies of the panel definition everywhere
			//I could have done this in YAML or PHP but that would cause a large page load with lots of repeated information.
			//Also, the definition is recursive, but the JSON schema parser crashes if you use infinite recursion. I'm solving
			//this problem by only using three recursions.
		if (mode == 'zenarioO') {
			var panel = schema.additionalProperties.properties.panels.additionalProperties;
			
			//Merge in a few things
			$.extend(true, panel.properties, panel.merge);
			
			var panelCopy1 = $.extend(true, {}, panel),
				panelCopy2 = $.extend(true, {}, panel),
				panelCopy3 = $.extend(true, {}, panel);
			
			panelCopy2.properties.item.properties.panel = panelCopy3;
			panelCopy1.properties.item.properties.panel = panelCopy2;
				 panel.properties.item.properties.panel = panelCopy1;
			panelCopy2.properties.trash.properties.panel = panelCopy3;
			panelCopy1.properties.trash.properties.panel = panelCopy2;
				 panel.properties.trash.properties.panel = panelCopy1;
			panelCopy2.properties.items.additionalProperties.properties.panel = panelCopy3;
			panelCopy1.properties.items.additionalProperties.properties.panel = panelCopy2;
				 panel.properties.items.additionalProperties.properties.panel = panelCopy1;
			panelCopy2.properties.item_buttons.additionalProperties.properties.panel = panelCopy3;
			panelCopy1.properties.item_buttons.additionalProperties.properties.panel = panelCopy2;
				 panel.properties.item_buttons.additionalProperties.properties.panel = panelCopy1;
			panelCopy2.properties.collection_buttons.additionalProperties.properties.panel = panelCopy3;
			panelCopy1.properties.collection_buttons.additionalProperties.properties.panel = panelCopy2;
				 panel.properties.collection_buttons.additionalProperties.properties.panel = panelCopy1;
			panelCopy2.properties.hidden_nav.additionalProperties.properties.panel = panelCopy3;
			panelCopy1.properties.hidden_nav.additionalProperties.properties.panel = panelCopy2;
				 panel.properties.hidden_nav.additionalProperties.properties.panel = panelCopy1;
			
			schema.additionalProperties.properties.nav.additionalProperties.properties.panel = panel;
		}
		
		devTools.schema = schema;
		
		if (schema.pages) {
			foreach (schema.pages as var p) {
				devTools.pages[schema.pages[p]] = true;
			}
		}
	
	} else {
		devTools.schema = false;
	}
	
	
	editor.setTheme('ace/theme/textmate');
    //editor.setTheme('ace/theme/idle_fingers');
    //editor.setTheme('ace/theme/tomorrow_night_bright');
    
    editor.commands.removeCommand('blockoutdent');
	
	editor.setBehavioursEnabled(false);
	
	
    document.getElementById('editor').style.fontSize='13px';
    //document.getElementById('editor').style.fontSize='0.95em';
	editor.setShowPrintMargin(false);
	
	
	
	var editorHeight = $editor.height(),
		menuHeight = $toolbar.height(),
		winHeight = Math.floor($(window).height()),
		options = {
			containment: 'document',
			minWidth: 75,
			minHeight: winHeight - menuHeight,
			maxHeight: winHeight - menuHeight,
			handles: 'e, s, se',
			start: devTools.handleResize,
			resize: devTools.handleResize,
			stop: devTools.handleResize
		};
	
	$editor.resizable(options);
	
	
	devTools.load();
	

	var $tooltip = $('<div class="editor_tooltip" style="position: absolute; display: none;"></div>');
	$tooltip.appendTo(document.body);
	
	
	var lastRow = -1,
		text = '';
	
	editor.on("mousemove", function(e) {
		var pos = e.getDocumentPosition();
		
		if (!pos) {
			return;
		}
		
		if (lastRow != pos.row) {
			//lastToken = e.editor.session.getTokenAt(pos.row, pos.column) 
			
			devTools.locatePosition(pos, function(path, data) {
				
				var sche = devTools.drillDownIntoSchema(path, data);
				text = '';
			
				if (sche.exact) {
					text = zenarioA.microTemplate('zenario_dev_tools_tooltip', sche);
				} else {
					var tag = path.split('/').pop();
					if (devTools.internalCMSProperties[tag]) {
						text = '<strong>Internal CMS property</strong>';
					} else {
						text = '';
					}
				}
				$tooltip.html(text);
			});
			
			lastRow = pos.row;
		}
			
		if (!text) {
			$tooltip.hide();
		
		} else {
			$tooltip.show().css('top', e.clientY + 20).css('left', e.clientX);
		}
	});
	
	$editor.on('mouseout', function() {
		$tooltip.hide();
	});
};



devTools.load = function() {
	zenarioA.nowDoingSomething('loading');
	
	//Check if there is an opener
	if (!devTools.mode
	 || !opener
	 || !opener[devTools.mode]
	 || (!devTools.skMap && !opener[devTools.mode].url)) {
		
		//It might be nice to use the dev tools to make something from scratch.
		//But for now, just stop with an error.
		alert('Error, could not load!');
		return;
	}
	
	var url;
	
	if (devTools.skMap) {
		url = URLBasePath + 'zenario/admin/ajax.php?_debug=1';
		devTools.path = '';
	} else {
		url = opener[devTools.mode].url + '&_debug=1';
		devTools.path = opener[devTools.mode].path;
	}
	
	$.get(url, function(data) {
		if (data = zenarioA.readData(data)) {
			devTools.focus = data;
			devTools.tagPath = ifNull(devTools.focus.tag_path, devTools.path, '');
			
			if (devTools.skMap) {
				devTools.filterNav(devTools.focus.tuix);
			}
			
			//Load information on each of the contributing files
			devTools.focus.files = {};
			var module,
				modules = devTools.focus.modules_files_loaded,
				paths,
				files = {},
				file,
				url;
			
			foreach (modules as module) {
				if (paths = modules[module].paths) {
					foreach (paths as file) {
						files[module + '.' + file] = false;
					}
				}
			}
			
			url = 'zenario/admin/dev_tools/ajax.php?mode=' + encodeURIComponent(devTools.mode) + '&load_tuix_files=' + encodeURIComponent(JSON.stringify(files));
			
			if (data = zenario.nonAsyncAJAX(url, false, true)) {
				foreach (modules as module) {
					if (paths = modules[module].paths) {
						foreach (paths as file) {
							if (data[module + '.' + file]) {
								data[module + '.' + file].path = paths[file];
								devTools.focus.files[module + '.' + file] = data[module + '.' + file];
							}
						}
					}
				}
			}
			
			devTools.draw();
		}
		zenarioA.nowDoingSomething(false);
	}, 'text');
};

devTools.filterNav = function(tuix, topLevel, parentKey, parentParentKey) {
	
	if (topLevel === undefined) {
		topLevel = opener[devTools.mode].currentTopLevelPath.split('/');
		topLevel = topLevel[0];
	}
	
	
	foreach (tuix as var key) {
		//Only show:
		if (key !== 'class_name' && (
			//Top level items
			(parentKey === undefined)
			//Any nav/hidden nav
		 || (parentKey === 'nav')
		 	//Properties of top level items and second level nav/hidden nav
		 || (parentParentKey === undefined || parentParentKey === 'nav')
			//The properties of links
		 || (parentKey === 'link')
			//The _path_here property of panels
		 || (parentKey === 'panel' && key === '_path_here')
		)) {
			//Don't show second levels for non-selected sections
			if (parentParentKey === undefined
			 && parentKey != topLevel
			 && (key === 'nav' || key === 'panel')) {
			 	if (key === 'panel') {
					delete tuix[key];
			 	} else {
					tuix[key] = {};
				}
				
			} else if (typeof tuix[key] == 'object') {
				devTools.filterNav(tuix[key], topLevel, key, parentKey);
			}
		} else {
			delete tuix[key];
		}
	}
};

devTools.draw = function() {
	if (!devTools.focus) {
		return false;
	}
	
	devTools.updateToolbar(true);
	devTools.updateEditor();
};

devTools.updateToolbar = function(refresh) {
	var merge = {
		files: {},
		selectedFile: devTools.skMap? 'combined' : 'current',
		storekeeper_query_ids: devTools.focus.storekeeper_query_ids,
		storekeeper_query_details: devTools.focus.storekeeper_query_details
	};
	
	var file,
		paths,
		module,
		modules = devTools.focus.modules_files_loaded,
		cursor;
	
	foreach (modules as module) {
		if (paths = modules[module].paths) {
			foreach (paths as file) {
				merge.files[module + '.' + file] = paths[file];
			}
		}
	}
	
	if (get('view')) {
		merge.selectedFile = get('view').value;
	}
	
	get('toolbar').innerHTML = zenarioA.microTemplate('zenario_dev_tools_toolbar', merge);
};


devTools.lastView = false;
devTools.updateEditor = function() {
	
	var view = get('view').value,
		format = 'yaml';
	
	
	//Set a variable to work around a bug where Most of the editor's API functions trigger the "change" event
	devTools.editorSetValueByScript = true;
	
	//if (editor.selection
	// && devTools.lastView) {
	//	devTools.editingPositions[devTools.lastView] = {
	//		top: editor.session.getScrollTop(),
	//		left: editor.session.getScrollLeft(),
	//		range: editor.getSelectionRange()
	//	};
	//}
	devTools.lastView = view;
	
	
	//Show the current TUIX
	if (view == 'current') {
		editor.setValue(devTools.toFormat(opener[devTools.mode].focus, format));
		//editor.setReadOnly(false);
		devTools.rootPath = devTools.tagPath;
	
	//Show the merged source
	} else if (view == 'combined') {
		editor.setValue(devTools.toFormat(devTools.focus.tuix, format));
		//editor.setReadOnly(true);
		devTools.rootPath = devTools.tagPath;
	
	//Show Storekeeper queries
	} else if (view == 'storekeeper_query_ids') {
		editor.setValue(devTools.focus.storekeeper_query_ids);
		//editor.setReadOnly(true);
		format = 'mysql';
	
	} else if (view == 'storekeeper_query_details') {
		editor.setValue(devTools.focus.storekeeper_query_details);
		//editor.setReadOnly(true);
		format = 'mysql';
	
	//Show an individual TUIX file
	} else {
		
		var data;
		if (data = devTools.focus.files[view]) {
			
			var ext = view.split('.');
			ext = ext[ext.length-1];
			
			if (format == ext || (format == 'json' && ext == 'js') || (format == 'yaml' && ext == 'yml')) {
				//Enable editing if we are looking at the source code
				editor.setValue(data.source);
				//editor.setReadOnly(false);
			
			} else {
				editor.setValue(devTools.toFormat(data.tags, format));
				//editor.setReadOnly(true);
			}
			
			devTools.rootPath = '';
		}
	}
	
	editor.setReadOnly(true);
	
	//if (devTools.editingPositions[view]) {
	//	editor.session.setScrollTop(devTools.editingPositions[view].top);
	//	editor.session.setScrollLeft(devTools.editingPositions[view].left);
	//	editor.selection.setSelectionRange(devTools.editingPositions[view].range);
	//} else {
	//	editor.gotoLine(1);
	//	editor.scrollToLine(1, true, false);
	//}
	
	var session = editor.getSession();
	
	//Set the format
	devTools.format = format;
	session.setMode('ace/mode/' + format);
	
	if (devTools.showPathOnLoad) {
		devTools.editorSelectFullPath(devTools.showPathOnLoad);
	} else {
		devTools.editorSelectPath(undefined, true);
	}
	delete devTools.showPathOnLoad;
	
	devTools.editorSetValueByScript = true;
	devTools.validate();
	editor.focus();
	
	setTimeout(function() {
		session.getUndoManager().reset();
	}, 1);
	
	//Clear my work-around variable
	devTools.editorSetValueByScript = false;
};

devTools.handleResize = function(event, ui) {
	var id = ui.element.attr('id'),
		width = ui.element.width(),
		winWidth = Math.floor($(window).width()),
		left = width,
		right = winWidth - width;
	
	$toolbar.width(left).css('right', right);
	$editor.width(left).css('right', right);
	$sidebar.css('left', left).width(right);
	
	editor.resize();
	devTools.sizePropertyTable();
};

devTools.toFormat = function(object, format) {
	if (format == 'yaml') {
		return devTools.toYAML(object);
	} else if (format == 'json') {
		return JSON.stringify(object, undefined, '\t');
	}
}

devTools.fromFormat = function(text, format, log) {
	try {
		if (format == 'yaml') {
			return devTools.fromYAML(text);
		
		} else if (format == 'json') {
			return JSON.parse(text);
		
		} else {
			return false;
		}
	} catch (e) {
		return false;
	}
}

devTools.toYAML = function(object) {
	return jsyaml.safeDump(object, {indent: 4, skipInvalid: true});
};

devTools.fromYAML = function(string) {
	return jsyaml.safeLoad(string.replace(/\t/g, '    '));
};

devTools.arrayToString = function(array) {
	if (_.isArray(array)) {
		array = array.join('\n');
	}
	return array;
};


//This function will return true if the object data contains a keys/a tag path of path
devTools.checkPathIsInData = function(path, data) {
	
	var i,
		tags = path.split('/');
	
	foreach (tags as i) {
		var tag = tags[i];
		
		if (tag !== '') {
			if (typeof data == 'object'
			 && data[tag] !== undefined) {
				data = data[tag];
			} else {
				return false;
			}
		}
	}
	
	return true;
};

//This will loop through all of
devTools.highlightFilesContainingSelection = function(path) {
	
	var files = {},
		localPath = path;
	
	if (devTools.rootPath) {
		localPath = path.replace(devTools.rootPath + '/', '');
	} else {
		localPath = path.replace(devTools.tagPath + '/', '');
	}
	
	var fullPath = devTools.tagPath + '/' + localPath;
	
	$('#view option').each(function(i, el) {
		var view = el.value,
			text = $(el).text(),
			contains = false;
		
		if (text.substr(0, 2) == '* ') {
			text = text.substr(2);
		}
		
		if (view == 'current') {
			contains = devTools.checkPathIsInData(localPath, opener[devTools.mode].focus);
		
		} else if (view == 'combined') {
			contains = devTools.checkPathIsInData(localPath, devTools.focus.tuix);
		
		} else if (view == 'map') {
			contains = devTools.checkPathIsInData(fullPath, opener[devTools.mode].map);

		} else if (devTools.focus.files[view] && devTools.focus.files[view].tags) {
			contains = devTools.checkPathIsInData(fullPath, devTools.focus.files[view].tags);
			
			if (contains) {
				files[view] = text;
			}
			
		} else {
			return;
		}
		
		if (contains) {
			text = '* ' + text;
		}
		
		$(el).text(text);
	});
	
	return files;
}


//Given a schema definition (and optionally an object containing the current data),
//this function will take the current path and drill down into the schema (and data if provided)
//to give the schema definition/data at that point
devTools.drillDownIntoSchema = function(localPath, data) {
	
	if (localPath === undefined) {
		localPath = '';
	}
	
	var i,
		isLocalPath,
		tag,
		tags,
		lastTag = '',
		paths = [devTools.rootPath, localPath],
		sche = {
			schema: devTools.schema,
			data: data,
			exact: false,
			parent: {
				schema: {},
				path: ''},
			object: {
				tag: '',
				data: {},
				schema: {},
				path: '',
				parent: {
					schema: {},
					path: ''},
				url: 'http://zenar.io/ref-' + devTools.schemaName + '-' + devTools.schema.top_level_page},
			path: ''
		};
	
	if (!sche.schema) {
		return sche;
	}
	
	
	foreach (paths as isLocalPath) {
		tags = paths[isLocalPath].split('/');
		
		foreach (tags as i) {
			tag = tags[i];
			
			if (tag !== '') {
				sche.parent.schema = sche.schema;
				sche.parent.path = sche.path;
				
				if (sche.schema.properties
				 && sche.schema.properties[tag]) {
					sche.path += (sche.path? '/' : '') + tag;
					sche.schema = sche.schema.properties[tag];
				
				} else if (sche.schema.additionalProperties) {
					sche.path += (sche.path? '/' : '') + tag;
					sche.schema = sche.schema.additionalProperties;
				
				} else {
					return devTools.drillDownIntoSchema2(sche);
				}
				
				if (isLocalPath > 0) {
					if (sche.data !== undefined
					 && typeof sche.data == 'object'
					 && sche.data[tag] !== undefined) {
						sche.data = sche.data[tag];
					} else {
						delete sche.data;
					}
				}
				
				if (sche.schema.properties) {
					sche.object.tag = tag;
					sche.object.lastTag = lastTag;
					sche.object.schema = sche.schema;
					sche.object.path = sche.path;
					sche.object.data = sche.data;
					sche.object.parent.schema = sche.parent.schema;
					sche.object.parent.path = sche.parent.path;
					
					if (devTools.pages[tag]) {
						sche.object.url = 'http://zenar.io/ref-' + devTools.schemaName + '-' + tag;
					} else
					if (lastTag && devTools.pages[lastTag]) {
						sche.object.url = 'http://zenar.io/ref-' + devTools.schemaName + '-' + lastTag;
					}
				}
			}
			
			lastTag = tag;
		}
	}
	
	sche.exact = true;
	return devTools.drillDownIntoSchema2(sche);
};

devTools.drillDownIntoSchema2 = function(sche) {
	var i;
	
	sche.requiredProperties = {};
	if (sche.schema && sche.schema.required) {
		if (typeof sche.schema.required == 'object') {
			foreach (sche.schema.required as i) {
				sche.requiredProperties[sche.schema.required[i]] = true;
			}
		} else {
			sche.requiredProperties[sche.schema.required] = true;
		}
	}
	
	if (sche.object) {
		sche.object = devTools.drillDownIntoSchema2(sche.object);
	}
	
	
	return sche;
}




//Validate the contents of the editor against the schema
devTools.validate = function() {
	
	var session = editor.getSession(),
		showErrors = devTools.lastView == 'current' || devTools.lastView == 'combined' || devTools.lastView == 'map',
		showWarnings = devTools.lastView != 'storekeeper_query_ids' && devTools.lastView != 'storekeeper_query_details';
	
	if (!showErrors && !showWarnings) {
		session.setAnnotations([]);
		return;
	}
	

	var schema = devTools.schema,
		sche = devTools.drillDownIntoSchema(),
		data,
		line,
		annotations = [];
	
	if (!sche.exact) {
		session.setAnnotations([]);
		return;
	}
	
	if (data = devTools.fromFormat(editor.getValue(), devTools.format)) {
		
		var result = tv4.validateMultiple(data, sche.schema);
		
		if (result.errors
		 && result.errors.length) {
			for (var e = 0; e < result.errors.length; ++e) {
				var code = result.errors[e].code,
					path = result.errors[e].dataPath,
					tag = path.split('/').pop(),
					message = result.errors[e].message,
					type = 'error';
				
				if (path.substr(0, 1) == '/') {
					path = path.substr(1);
				}
				
				//Logic for unrecognised properties
				if (code == 303) {
					
					//Ignore properties called "custom"
					if (('' + tag).substr(0, 7) == 'custom_') {
						continue;
					
					//Ignore errors for some system generated tags
					} else if (devTools.internalCMSProperties[tag]) {
						continue;
					
					} else {
						type = 'warning';
					}
				}
				
				if ((type == 'error' && !showErrors)
				 || (type == 'warning' && !showWarnings)) {
					continue;
				}
				
				if (line = devTools.editorGetLineNumberFromPath(path)) {
					annotations.push({
						row: line.number - 1,
						column: line.indent.replace(/\t/g, '    ').length,
						text: message,
						type: type
					});
				}
			}
		}
	}
	
	session.setAnnotations(annotations);
};


//Given one line, try to break it up into its indent, its key definition, and the rest of the line
devTools.splitLineByKey = function(text, format) {
	
	var match,
		line = {},
		reg;
	
	if (format === undefined) {
		format = devTools.format;
	}
	
	//Use a regular expression that should match a key definition at the start of a line
	if (format == 'yaml') {
		reg = /^[\s\t]*(\w+):/;
		line.quote = '';
	
	} else if (format == 'json') {
		reg = /^[\s\t]*"?(\w+)"?:/;
		line.quote = '"';
	
	} else if (format == 'log') {
		reg = /^[\s\t]*([\w\/]+):/;
		line.quote = '';
	
	} else {
		return false;
	}
	
	if (text !== undefined
	 && (match = text.match(reg))) {
		match = match[0];
		
		//Do some string manipulation to add a prefix of "____here____" to the start of the key
		line.indent = match.split(/["\w\/]+/),
		line.key = match.match(/[\w\/]+/);
		line.indent = line.indent[0];
		line.key = line.key[0];
		
		line.rest = text.substr(match.length);
		
		return line;
	}
	
	return false;
};
		
		



//Try to work out where the developer's cursor is and display information about that place
devTools.locatePosition = function(posIn, callbackIn) {
	var v = editor.getValue(),
		pos = posIn || editor.getCursorPosition(),
		callback = callbackIn || devTools.showSidebar,
		session = editor.session,
		lines,
		l,
		line,
		reg,
		sche = false;
	
	//Use a regular expression that should match a key definition at the start of a line
	if (devTools.format == 'yaml') {
		reg = /[\s\t]*(\w+):/g;
	
	} else if (devTools.format == 'json') {
		reg = /[\s\t]*"?(\w+)"?:/g;
		quote = '"';
	
	} else {
		return;
	}
	
	//Attempt to get the current position of the cursor, and the lines in the document
	if (v && session && pos && pos.row && (lines = session.getLines(0, session.getLength()))) {
		
		//Starting at the current line, and then moving up, try to find a key.
		//Stop on the first key we find
		for (l = pos.row; l >= 0; --l) {
			if (line = devTools.splitLineByKey(lines[l])) {
				//Do some string manipulation to add a prefix of "____here____" to the start of the key
				lines[l] = line.indent + line.quote + '____here____' + line.key + line.quote + ':' + line.rest;
				
				//Now attempt to parse the text document, with the modified key
				var data, path;
				lines = lines.join('\n');
				if (data = devTools.fromFormat(lines, devTools.format)) {
					path = devTools.locatePositionR(data, '');
					
					callback(path, data);
				}
				return;
			}
		}
	}
	
	callback('');
};

//Loop/recurse through an object, looking for a modified key and the path to that modified key
devTools.locatePositionR = function(data, path) {
	var out = '',
		key,
		actualKey;
	
	foreach (data as key) {
		if (data.hasOwnProperty(key)) {
			//If this is the key, correct it and return the path
			if (key.substr(0, 12) === '____here____') {
				actualKey = key.substr(12);
				
				//Correct the data
				data[actualKey] = data[key];
				delete data[key];
				
				//Return the path
				return path + (path? '/' : '') + actualKey;
			}
			
			//Otherwise keep looking
			if (typeof data[key] == 'object') {
				if (out = devTools.locatePositionR(data[key], path + (path? '/' : '') + key)) {
					return out;
				}
			}
		}
	}
	
};


//Returns a line number, given a specified tag path
devTools.editorGetLineNumberFromPath = function(path) {
	
	var paths = path.split('/'),
		p = -1,
		currentIndentLength = -1;
	
	//Note: code is similar to devTools.locatePosition() above
	var v = editor.getValue(),
		session = editor.session,
		lines,
		line,
		l = 0,
		sche = false;
	
	if (v && session && (lines = session.getLines(0, session.getLength()))) {
		
		whileLoop:
		while (++p < paths.length) {
			
			if (!paths[p]) {
				continue whileLoop;
			}
			
			forLoop:
			for (; l < lines.length; ++l) {
				if (line = devTools.splitLineByKey(lines[l])) {
					
					line.indent = line.indent.replace('\t', '    ');
					if (line.key == paths[p]
					 && line.indent.length > currentIndentLength) {
					 	currentIndentLength = line.indent.length;
					 	line.number = ++l;
						continue whileLoop;
					
					} else if (line.indent.length <= currentIndentLength) {
						return false;
					}
				}
			}
			
			return false;
		}
		
		return line;
	}
	
	return false;
};

//Given a path, try to select its key by going to the line number of that key in the text document
devTools.editorSelectPath = function(path, navigateUpOnFail) {
	
	path = devTools.setupPath(path, true);
	
	var line;
	if (line = devTools.editorGetLineNumberFromPath(path)) {
		devTools.editorSetValueByScript = true;
		editor.gotoLine(line.number);
		editor.scrollToLine(line.number, true, false);
		
		if (line.indent) {
			editor.navigateWordRight();
		}
		
		editor.getSelection().selectWordRight();
		editor.focus();
		
		devTools.showSidebar(path);
		devTools.editorSetValueByScript = false;
	
	} else if (navigateUpOnFail) {
		//If we couldn't find this key, try to show the key above
		var paths = path.split('/');
		paths.pop();
		
		if (paths.length) {
			path = paths.join('/');
			devTools.editorSelectPath(path, navigateUpOnFail);
		} else {
			editor.gotoLine(1);
			editor.scrollToLine(1, true, false);
			devTools.showSidebar('');
		}
	
	} else {
		//If we couldn't find this key, at least still show the sidebar for this location
		devTools.showSidebar(path);
	}
};

devTools.editorSelectFullPath = function(path) {
	
	//If we're not loaded yet, remember this path and try to display it on load
	if (devTools.rootPath === undefined) {
		devTools.showPathOnLoad = path;
		return;
	}
	
	//Convert the full path to a local path
	if (devTools.rootPath) {
		path = path.replace(devTools.rootPath + '/', '');
	}
	
	//Show the path
	devTools.editorSelectPath(path, true);
};


devTools.lastPath = '';
devTools.lastRootPath = '';
devTools.setupPath = function(path, log) {
	if (path === undefined) {
		path = devTools.lastPath;
		
		if (devTools.lastRootPath != devTools.rootPath) {
			if (devTools.lastRootPath) {
				path = devTools.lastRootPath + '/' + path;
			}
			if (devTools.rootPath) {
				path = path.replace(devTools.rootPath + '/', '');
			}
		}
		
	}
	
	devTools.lastPath = path;
	devTools.lastRootPath = devTools.rootPath;
	
	return path;
};

//Set the current location in the URL
devTools.setHash = function(path) {
	//Add the path to the hash
	document.location.hash = path;
	zenario.currentHash = document.location.hash;
};


devTools.showSidebar = function(path, data) {
	
	path = devTools.setupPath(path);
	
	if (data === undefined) {
		data = devTools.fromFormat(editor.getValue(), devTools.format);
	}
	
	var sche;
	if (path) {
		sche = devTools.drillDownIntoSchema(path, data);
	}
	
	if (!sche) {
		sche = devTools.drillDownIntoSchema();
	}
	
	//Generate the breadcrumbs in the sidebar.
	//Each breadcrumb should contain the full path up to that point in the breadcrumbs
	sche.paths = [];
	if (sche.path) {
		devTools.setHash(sche.path);
		
		if (devTools.rootPath) {
			sche.fullPath = devTools.rootPath + '/' + path;
			sche.localPath = path; //Probably wrong, but change back if I see bugs: path.replace(devTools.rootPath + '/', '');
		} else {
			sche.fullPath =
			sche.localPath = path;
		}
		
		if (devTools.mode == 'zenarioO') {
			sche.shortPath = opener[devTools.mode].shortenPath(sche.fullPath);
		}
		
		var path = '',
			paths = sche.localPath.split('/');
		
		foreach (paths as var p) {
			sche.tag = paths[p];
			path += (path? '/' : '') + paths[p];
			sche.paths.push({tag: paths[p], path: path});
		}
	}
	
	sche.object.files = devTools.highlightFilesContainingSelection(path || sche.object.path);
	
	get('sidebar_inner').innerHTML = zenarioA.microTemplate('zenario_dev_tools_sidepanel', sche);
	$sidebar.removeClass('sidebarEmpty').addClass('sidebarFull');
	devTools.sizePropertyTable();
	
	zenarioA.tooltips('#sidebar_inner a[title]');
	zenarioA.tooltips('#sidebar_inner ul[title]');
	zenarioA.tooltips('#sidebar_inner input[title]');
};

devTools.sizePropertyTable = function() {
	//I can't work out how to get this working in CSS so I'm going to cheat and do it with JavaScript =P
	if (get('properties_table')) {
		
		var padding = 20,
			titleWidth = ifNull($('#properties_table .property_name').width(), 0, 0),
			width = $sidebarInner.innerWidth() - titleWidth - padding;
		
		$('#properties_table .property_desc').width(width);
		$('#properties_table .property_desc div').width(width);
	}
};


devTools.editorOnChange = function(e) {
	if (devTools.editorSetValueByScript) {
		return;
	}
	
	//Revalidate 2 seconds after changes to the text have stopped
	zenario.actAfterDelayIfNotSuperseded('dev_tools_editor_change', function() {
		devTools.validate();
	}, 2000);
};

devTools.editorOnSelect = function(e) {
	if (devTools.editorSetValueByScript) {
		return;
	}
	
	//Clear the sidebar
	get('sidebar_inner').innerHTML = '';
	$sidebar.addClass('sidebarEmpty').removeClass('sidebarFull');
	
	//Reposition 1/2 second after changes to the text have stopped
	zenario.actAfterDelayIfNotSuperseded('dev_tools_editor_select', function() {
		devTools.locatePosition();
	}, 500);
};


editor.on('change', devTools.editorOnChange);
editor.on('changeSelection', devTools.editorOnSelect);
editor.on('changeCursor', devTools.editorOnSelect);
editor.session.setUseSoftTabs(false);


})(
	URLBasePath,
	window, document, window.opener,
	zenario, zenarioA, devTools, editor,
	zenario.get, zenario.engToBoolean, zenario.htmlspecialchars, zenario.ifNull, zenario.jsEscape, zenarioA.phrase,
	$('#toolbar'), $('#editor'), $('#sidebar'), $('#sidebar_inner'));