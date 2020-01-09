/*
 * Copyright (c) 2020, Tribal Limited
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
	
	For more information, see js_minify.shell.php for steps (1) and (2), and tuix.wrapper.js.php for step (3).
*/

zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioT, zenarioAB, zenarioAT, zenarioO,
	encodeURIComponent, defined, engToBoolean, get, htmlspecialchars, jsEscape, phrase,
	extensionOf, methodsOf, has
) {
	"use strict";
	phrase = phrase || {};


//A very lightweight function for creating HTML tags,
//by passing in their attributes in as pairs an array.
//N.b. if you want some HTML inside the element, pass that in as the last entry in the array.
var htmlBaseFun = function(tag, a, noOffset) {
	var html = '<' + tag,
		c = a.length,
		i = noOffset? 1: 2,
		key, val,
		postFieldHTML,
		selfCloses = {br:1, hr:1, img:1, link:1, input:1}[tag],
		booleans = {checked: 1, disabled: 1, multiple: 1, selected: 1};

	//Output attribute/value pairs
	for (; i < c; i += 2) {
		
		key = a[i - 1];
		val = a[i];
		
		if (booleans[key]) {
			if (val) {
				html += ' ' + key + '="' + key + '"';
			}
		} else {
			if (val !== false && defined(val)) {
				html += ' ' + key + '="' + htmlspecialchars(val) + '"';
			}
		}
	}
		
	if (selfCloses) {
		html += '/>';
	} else {
	
		//Was there an odd number of attribute/value pairs?
		//If so, the last one should be the inner HTML of the element
		if (c % 2? noOffset : !noOffset) {
			postFieldHTML = a[c-1];
		
			//Use " " as a flag to return an unfinished tag
			if (postFieldHTML === ' ') {
				return html;
			}
		
			//Use ">" as a flag to not close a tag and just return with it open
			if (postFieldHTML === '>') {
				return html + '>';
			}
		
			html += '>' + postFieldHTML;
		} else {
			html += '>';
		}
	}

	if (!selfCloses) {
		html += '</' + tag + '>';
	}

	return html;
};

//Create the following functions as shortcuts to the above function:
//	zenarioT.html = funciton(tag) {};
//	zenarioT.div = funciton() {};
//	zenarioT.input = funciton() {};
//	zenarioT.select = funciton() {};
//	zenarioT.option = funciton() {};
//	zenarioT.span = funciton() {};
//	zenarioT.label = funciton() {};
//	zenarioT.p = funciton() {};
//	zenarioT.h1 = funciton() {};
//	zenarioT.ul = funciton() {};
//	zenarioT.li = funciton() {};
//	zenarioT.form = funciton() {};
_.each(['', 'div', 'input', 'select', 'option', 'span', 'label', 'p', 'h1', 'ul', 'li', 'form'], function(el) {
	zenarioT[el || 'html'] = function(tag) {
		return htmlBaseFun(el || tag, arguments, el);
	}
});

//Create shortcut variables to the above functions and some common parameters,
//so the code ends up smaller when minified
zenarioT.lib = function(fun) {
	fun(
		zenarioT.html,
		zenarioT.div,
		zenarioT.input,
		zenarioT.select,
		zenarioT.option,
		zenarioT.span,
		zenarioT.label,
		zenarioT.p,
		zenarioT.h1,
		zenarioT.ul,
		zenarioT.li
	);
};


//Use these shortcut variables for the rest of the function definitions here
zenarioT.lib(function(
	_$html,
	_$div,
	_$input
) {







zenarioT.prop = function(a, arg2, arg3) {
	
	var start = 0,
		stop, k, key, keys;
	
	if (defined(arg3)) {
		start = 1;
		keys = arguments;
	
	} else if (typeof arg2 == 'string') {
		if (arg2.indexOf('.') === -1) {
			return a[arg2];
		} else {
			keys = arg2.split('.');
		}
	
	} else {
		keys = arg2;
	}

	stop = keys.length;
	
	for (k = start; k < stop; ++k) {
		key = keys[k];
		
		if (typeof a == 'object'
		 && defined(a[key])) {
			a = a[key];
		} else {
			return undefined;
		}
	}
	
	return a;
};


zenarioT.microTemplate = function(template, data, filter) {
	return zenario.microTemplate(template, data, filter, zenarioT.microTemplates);
};




zenarioT.onbeforeunload = function() {
	var message;
	
	//If any Admin Boxes are open, and look like they might have been changed, set a warning message for if an admin tries to leave the page 
	if (window.zenarioAB && zenarioAB.isOpen && zenarioAB.editModeOnBox() && (zenarioAB.changes() || zenarioAB.callFunctionOnEditors('isDirty'))) {
		return phrase.leaveAdminBoxWarning;
	
	//Same for the skin editor
	} else if (window.zenarioSE && zenarioSE.isOpen && zenarioSE.editModeOnBox() && (zenarioSE.changes() || zenarioSE.callFunctionOnEditors('isDirty'))) {
		return phrase.leaveAdminBoxWarning;
	
	//Same for the conductor & any FEA plugins
	} else if (undefined !== (message = zenario_conductor.confirmOnCloseMessage())) {
		return message;
	
	//Set a warning if any slots are being edited
	} else if (zenarioA.checkSlotsBeingEdited && zenarioA.checkSlotsBeingEdited()) {
		return phrase.leavePageWarning;
	
	} else {
		return undefined;
	}
};







//Functions for enabling HTML 5 file-uploads in WYSIWYG Editors and in Organizer Panels

zenarioT.uploading = false;
zenarioT.canDoHTML5Upload = function() {
	return window.FileReader;
};

zenarioT.setHTML5UploadFromDragDrop = function(path, request, preCall, callBack, el) {
	
	if (!zenarioT.canDoHTML5Upload()) {
		return false;
	
	} else {
		$(el || document.body).on(
			'drop',
			function(e) {
				
				$(e.target).removeClass('dragover');
				
				e = e.originalEvent;
				
				if (preCall) {
					preCall();
				}
				
				zenarioT.stopFileDragDrop(e);
				zenarioT.doHTML5Upload(
					e.target.files || e.dataTransfer.files,
					path,
					request,
					callBack);
			});
		
		return true;
	}
};

zenarioT.doHTML5Upload = function(files, path, request, callBack) {
	if (!path || !request || zenarioT.uploading) {
		return;
	}
	
	zenarioT.uploadPath = path;
	zenarioT.uploadRequest = request;
	zenarioT.uploadCallBack = callBack;
	zenarioT.uploadResponses = [];
	
	zenarioT.uploadFile = -1;
	zenarioT.uploadFiles = files;
	zenarioT.doNextUpload();
};

zenarioT.doNextUpload = function() {
	
	var $zenario_progress_wrap = $('#zenario_progress_wrap'),
		$zenario_progress_name = $('#zenario_progress_name'),
		$zenario_progress_stop = $('#zenario_progress_stop');
	
	$zenario_progress_name.text('');
	$zenario_progress_stop.unbind('click');
	
	zenarioT.uploading = !!zenarioT.uploadFiles[++zenarioT.uploadFile];
	
	if (zenarioO.init) {
		zenarioO.setWrapperClass('uploading', zenarioT.uploading);
	}
	
	if (zenarioT.uploading) {
		
		$zenario_progress_wrap.show().removeClass('zenario_progress_cancelled'); 
		
		get('zenario_progressbar').style.width = '0%';
		
		if (!zenarioT.uploadFiles[zenarioT.uploadFile].size || !zenarioT.uploadFiles[zenarioT.uploadFile].name) {
			zenarioT.doNextUpload();
		
		} else if (zenarioA.maxUpload && zenarioA.maxUpload < zenarioT.uploadFiles[zenarioT.uploadFile].size) {
			zenarioA.showMessage(phrase.uploadTooLarge.replace('[[maxUploadF]]', zenarioA.maxUploadF), true, 'error');
			zenarioT.doNextUpload();
		
		} else {
			$zenario_progress_name.text(zenarioT.uploadFiles[zenarioT.uploadFile].name);
			
			zenarioT.uploader = new XMLHttpRequest();  
			zenarioT.uploader.open('POST', zenarioT.uploadPath, true);
			
			try {
				//Try to add a header for the filename
				zenarioT.uploader.setRequestHeader('X_FILENAME', zenarioT.uploadFiles[zenarioT.uploadFile].name);
			} catch (e) {
				//Don't worry if it coudln't be added, it's optional
			}
			
			var data = new FormData();
			foreach (zenarioT.uploadRequest as var k) {
				data.append(k, '' + zenarioT.uploadRequest[k]);
			}
			
			data.append('Filedata', zenarioT.uploadFiles[zenarioT.uploadFile]);
			
			zenarioT.uploader.upload.addEventListener('progress', zenarioT.uploadProgress, false);
			
			zenarioT.uploader.addEventListener('load', zenarioT.uploadDone, false);
			zenarioT.uploader.addEventListener('error', zenarioT.uploadDone, false);
			zenarioT.uploader.addEventListener('abort', zenarioT.uploadDone, false);
			
			$zenario_progress_stop.click(function() {
				$zenario_progress_wrap.show().removeClass('zenario_progress_cancelled');
				zenarioT.uploader.abort();
			});
			
			zenarioT.uploader.send(data);
		}
	
	} else {
		$zenario_progress_wrap.hide();
		
		if (zenarioT.uploadCallBack) {
			zenarioT.uploadCallBack(zenarioT.uploadResponses);
		}
	}
};

zenarioT.uploadProgress = function(e) {
	var completion = 0;
	if (e.lengthComputable) {  
		completion = 100 * e.loaded / e.total;  
	}
	
	get('zenario_progress_wrap').style.display = 'block';
	get('zenario_progressbar').style.width = completion + '%';
};

zenarioT.uploadDone = function(e) {
	
	if (zenarioT.uploader.responseText && zenarioT.uploader.responseText != 1) {
		try {
			data = JSON.parse(zenarioT.uploader.responseText);
		
			if (typeof data != 'object') {
				throw 0;
			}
			
			zenarioT.uploadResponses.push(data);
		
		} catch (e) {
			if (zenarioA.showMessage) {
				zenarioA.showMessage(zenarioT.uploader.responseText, true, 'error', false, true);
			} else {
				alert(zenarioT.uploader.responseText);
			}
		}
	}
	
	zenarioT.doNextUpload();
};


zenarioT.stopDefault = function(e) {
	e = (e || event);
	
	if (e && e.stopPropagation) {
		e.stopPropagation();
	}
	if (e && e.preventDefault) {
		e.preventDefault();
	}
	
	return false;
};


//Try to disable the ability to navigate away from the page by dragging a file or an image into the browser from the filesystem
zenarioT.stopFileDragDrop = function(e) {
	var fileUpload = false;
	if (e.dataTransfer && e.dataTransfer.types) {
		if (typeof e.dataTransfer.types.contains == 'function') {
			fileUpload = e.dataTransfer.types.contains('Files');
		
		} else {
			fileUpload = (('' + e.dataTransfer.types) == 'Files') || (e.dataTransfer.types[0] && ('' + e.dataTransfer.types[0]) == 'Files');
		}
	}
	
	if (fileUpload) {
		e.stopPropagation();
		e.preventDefault();
	}
};

zenarioT.disableFileDragDrop = function(el) {
	if (zenarioT.canDoHTML5Upload()) {
		el.addEventListener('drop', zenarioT.stopDefault, false);
		el.addEventListener('dragenter', zenarioT.stopDefault, false);
		el.addEventListener('dragover', zenarioT.stopDefault, false);
		el.addEventListener('dragexit', zenarioT.stopDefault, false);
	}
};


//Given an image size and a target size, resize the image (maintaining aspect ratio).
//This is a copy of the resizeImage() function in cms.inc.php, to ensure consistent logic
//when generating a thumbnail in JavaScript
zenarioT.resizeImage = function(image_width, image_height, constraint_width, constraint_height, out, allowUpscale) {
	out.width = image_width;
	out.height = image_height;
	image_width = 1*image_width;
	image_height = 1*image_height;
	
	if (image_width == constraint_width && image_height == constraint_height) {
		return;
	}
	
	if (!allowUpscale && (image_width <= constraint_width) && (image_height <= constraint_height)) {
		return;
	}

	if ((constraint_width / image_width) < (constraint_height / image_height)) {
		out.width = constraint_width;
		out.height = Math.floor(image_height * constraint_width / image_width);
	} else {
		out.height = constraint_height;
		out.width = Math.floor(image_width * constraint_height / image_height);
	}

	return;
};







zenarioT.checkActionUnique = function(object) {
	var actions = [],
		test,
		tests = [
			'admin_box',
			'ajax',
			'combine_items',
			'navigation_path',
			'frontend_link',
			'help',
			'link',
			'onclick',
			'panel',
			'pick_items',
			'popout',
			'organizer_quick',
			'upload'];
	
	foreach (tests as test) {
		if (object[tests[test]]) {
			actions.push(tests[test]);
		}
	}
	
	switch (actions.length) {
		case 1:
			return true;
		
		case 0:
			return false;
		
		default:
			console.log(object);
			alert('This navigation or button has multiple actions associated with it:\n\n' + actions + '\n\n(See the console log for the faulty definition.)');
			return false;
	}
};

//Handle what happens when an admin clicks on something that will cause an action; e.g. a button on the admin toolbar or a button on a Organizer Panel's Toolbar
zenarioT.action = function(zenarioCallingLibrary, object, itemLevel, branch, link, extraRequests, specificItemRequested, AJAXURL) {
	if (zenarioCallingLibrary.uploading) {
		return false;
	}
	
	//Check to see if there is a unique action on this button
		//(But skip this if we're overriding the functionality with a link)
	if (!link) {
		if (!zenarioT.checkActionUnique(object)) {
			return false;
		}
	}
	
	var ajaxMethodCall;
	switch (zenarioCallingLibrary.globalName) {
		case 'zenarioO':
			ajaxMethodCall = 'handleOrganizerPanelAJAX';
			break;
		case 'zenarioAT':
			ajaxMethodCall = 'handleAdminToolbarAJAX';
			break;
		case 'zenarioW':
			ajaxMethodCall = 'handleWizardAJAX';
			break;
		case 'zenarioAB':
		case 'zenarioSE':
			ajaxMethodCall = 'handleAdminBoxAJAX';
			break;
		default:
			ajaxMethodCall = 'handlePluginAJAX';
	}
	
	if (!link && object.link) {
		link = object.link;
	
	} else if (!link && object.panel && object.panel._path_here) {
		link = {path: object.panel._path_here};
	}
	
	//In select mode, don't let an admin navigate past a certain point using double-click actions on items
	if (link && zenarioO.pathNotAllowed(link)) {
		return;
	}
	
	//Clear the yourWorkInProgressLastUpdated flag so that the list will be immediately updated after any change
	zenarioO.yourWorkInProgressLastUpdated = 0;
	
	zenarioCallingLibrary.pickItemsItemLevel = itemLevel;
	zenarioCallingLibrary.postPickItemsObject = false;
	
	
	//Uploads and AJAX requests need a path and a requests object
	var url, requests, thing;
	if ((thing = object.upload)
	 || (thing = object.ajax)) {
		
		if (AJAXURL) {
			url = AJAXURL;
			requests = zenarioCallingLibrary.getKey(itemLevel);
		
		//If an AJAX button requests all of the ids that are currently matched in Organizer,
		//we'll need to get the details of the last Organizer panel accessed (the requests needed
		//should be stored in zenarioO.lastRequests) and fire up the Organizer Panel to get the list of
		//ids.
		} else
		if (!itemLevel
		 && object.ajax
		 && object.ajax.pass_matched_ids
		 && zenarioCallingLibrary.globalName == 'zenarioO') {
			url =
				URLBasePath + 'zenario/admin/organizer.ajax.php?' +
					'__pluginClassName__=' + thing.class_name +
					'&path=' + zenarioO.path +
					'&_get_matched_ids=1' +
					'&method_call=' + ajaxMethodCall +
					zenario.urlRequest(zenarioO.lastRequests);
			requests = {};
		
		//If not then we don't need to use the whole the Organizer Panel logic, we can just use
		//the normal ajax file.
		} else {
			url =
				URLBasePath + 'zenario/ajax.php?' +
					'__pluginClassName__=' + thing.class_name +
					'&__path__=' + zenarioCallingLibrary.path +
					'&method_call=' + ajaxMethodCall;
		
			requests = zenarioCallingLibrary.getKey(itemLevel);
		}
		
		if (thing.request) {
			$.extend(requests, thing.request);
		}
		
		if (extraRequests) {
			$.extend(requests, extraRequests);
		}
	}
	
	//Handle uploads first, as they need converting to ajax pop-ups for browsers without html5
	if (object.upload) {
		var fallback = !zenarioT.canDoHTML5Upload(),
			html = '<input type="file" name="Filedata"',
			e, extension, extensions, split;
		
		if (extensions = object.upload.accept || object.upload.extensions) {
			extensions = zenarioT.tuixToArray(extensions);
			
			//Loop through each extension and check it
			foreach (extensions as e => extension) {
				
				switch (extension) {
					//Catch the case where someone has used the dropbox-style types and convert them
					case 'text':
						extensions[e] = 'text/*';
						break;
					case 'images':
						extensions[e] = 'image/*';
						break;
					case 'video':
						extensions[e] = 'video/*';
						break;
					case 'audio':
						extensions[e] = 'audio/*';
						break;
					
					default:
						//Look for file extensions without a "." in front of them, and automatically add the "."
						if (extension.indexOf('/') == -1
						 && extension.substr(0, 1) != '.') {
							extensions[e] = '.' + extension;
						}
				}
			}
			
			html += ' accept="' + htmlspecialchars(extensions.join(',')) + '"';
		
		//Backwards compatibility with old versions
		} else if (object.upload.fileDesc == 'Images') {
			html += ' accept="image/*"';
		}
		
		if (fallback) {
			html += ' id="zenario_fallback_fileupload"';
		}
		
		if (!fallback && engToBoolean(object.upload.multi)) {
			html += ' multiple';
		}
		html += '/>';
		
		if (!fallback) {
			var $input = $(html);
			
			//This seemingly useless line of code (that I only added for debugging purposes) actually
			//fixes a glitch where the user's selection is ignored.
			//I think adding this line stops the garbage collector from removing the field before
			//the change() function is called.
			window._zenario_open_file_picker = $input[0];
			
			$input.change(function() {
				
				if (zenarioCallingLibrary.uploadStart) {
					zenarioCallingLibrary.uploadStart();
				}
				
				zenarioT.doHTML5Upload(this.files, url, requests, function(responses) {
					zenarioCallingLibrary.uploadComplete(responses);
				});
				
			});
			$input.click();
			return;
			
		} else {
			//For backwards compatability for browsers without flash, attempt to convert file upload tags into ajax->confirm->form tags
			object = zenario.clone(object);
			requests._html5_backwards_compatibility_hack = 1;
			
			object.ajax = {
				class_name: object.upload.class_name,
				confirm: {
					message: html,
					html: true,
					button_message: phrase.upload,
					cancel_button_message: phrase.cancel,
					form: true
				},
				request: requests
			};
		}
	}
	
	
	if (link) {
		//If a link is set, go to the link
		if (engToBoolean(link.unselect_items)) {
			zenarioO.selectedItems = {};
			zenarioO.saveSelection();
		}
		
		zenarioO.go(
			link.path,
			branch,
			link.refiner? {'id': itemLevel? zenarioO.getKeyId() : '', 'name': link.refiner} : undefined,
			undefined, undefined, undefined, undefined, specificItemRequested);
	
	} else if (object.frontend_link) {
		var id, item,
			frontend_link = object.frontend_link,
			sameWindow = false;
		
		if (itemLevel && (id = zenarioCallingLibrary.getKeyId(true)) && (item = zenarioCallingLibrary.tuix.items[id]) && item.frontend_link) {
			frontend_link = item.frontend_link;
		}
		
		//For Organizer, attempt to insert the return link,
		//and then open it in the same window if we successfully inserted it
		if (zenarioCallingLibrary.globalName == 'zenarioO') {
			frontend_link = zenarioO.parseReturnLink(frontend_link);
			
			if (frontend_link.indexOf('&zenario_sk_return=') != -1) {
				sameWindow = true;
			}
		}
		
		if (sameWindow || frontend_link.substr(0, 25) == 'admin.php') {
			zenario.goToURL(zenario.addBasePath(frontend_link));
		
		//If there is a prototal (e.g. http://) in the URL, open it in a new window, unless it is a link
		//to the current site
		} else if (frontend_link.indexOf('://') !== -1 && frontend_link.indexOf(URLBasePath) !== 0) {
			window.open(frontend_link);
		
		} else if (zenarioCallingLibrary.globalName == 'zenarioAT') {
			zenario.goToURL(zenario.addBasePath(frontend_link));
		
		} else if (windowOpener && !windowOpener.zenarioO) {
			window.opener.location = zenario.addBasePath(frontend_link);
		
		} else if (window.storekeeperChildWindow && !window.storekeeperChildWindow.closed) {
			window.storekeeperChildWindow.location = zenario.addBasePath(frontend_link);
		
		} else {
			window.storekeeperChildWindow = window.open(zenario.addBasePath(frontend_link));
		}
	
	} else if (object.navigation_path) {
		var id, item, pos,
			navigation_path = object.navigation_path;
		
		if (itemLevel && (id = zenarioCallingLibrary.getKeyId(true)) && (item = zenarioCallingLibrary.tuix.items[id]) && item.navigation_path) {
			navigation_path = item.navigation_path;
		}
		
		if (zenarioCallingLibrary.globalName == 'zenarioO') {
			zenarioO.go(navigation_path, -1);
		} else {
			zenario.goToURL(zenario.addBasePath(
				(window.zenarioATLinks && window.zenarioATLinks.organizer || 'zenario/admin/organizer.php') +
				'#' +
				navigation_path
			));
		}
	
	} else if (object.admin_box) {
		zenarioA.nowDoingSomething('loading');
		var key = zenarioCallingLibrary.getKey(itemLevel) || {};
		
		if (object.admin_box.key) {
			foreach (object.admin_box.key as var r) {
				key[r] = object.admin_box.key[r];
			}
		}
		
		zenarioAB.open(
			object.admin_box.path,
			key,
			object.admin_box.tab,
			object.admin_box.values,
			undefined,
			engToBoolean(object.admin_box.create_another)? object.admin_box : false,
			undefined,
			engToBoolean(object.admin_box.pass_matched_ids));
	
	} else if (object.popout) {
		var id, item, title, usage,
			filename, match,
			popout = zenario.clone(object.popout);
		
		if (itemLevel && (id = zenarioCallingLibrary.getKeyId(true)) && (item = zenarioCallingLibrary.tuix.items[id])) {
			if (item.popout) {
				popout = $.extend(popout, item.popout);
			}
			
			if (!popout.title && zenarioCallingLibrary.popoutLabelFormat) {
				popout.title = zenarioCallingLibrary.applyMergeFields(zenarioCallingLibrary.popoutLabelFormat, false, id);
			}
		}
			
		if (item && item.href && !defined(popout.href)) {
			popout.href = item.href;
		
		} else if (item && item.frontend_link && !defined(popout.href)) {
			popout.href = zenarioO.parseReturnLink(item.frontend_link, '_show_page_preview=1');
		
		} else if (item && popout.href) {
			popout.href += popout.href.indexOf('?') === -1? '?' : '&';
			
			if (item.checksum) {
				popout.href += 'c=' + encodeURIComponent(item.checksum);
				
				if (id == 1*id) {
					popout.href += '&id=' + id;
				}
			
			} else if (id == 1*id) {
				popout.href += 'id=' + id;
			
			} else {
				popout.href += 'c=' + encodeURIComponent(id);
			}
			
			if (usage = zenarioCallingLibrary.getKey().usage || item.usage) {
				popout.href += '&usage=' + encodeURIComponent(usage);
			}
		}
		
		if (popout.href) {
			if (filename = (item && item.filename) || (popout.options && popout.options.filename) || popout.filename) {
				
				//If the short checksum has been added to the end of the filename, we need to strip it off
				//as colorbox uses the filename to detect the mimetype of the object
				if (match = filename.match(/(.*) \[.*\]/)) {
					filename = match[1];
				}
				
				popout.href += '&filename=' + encodeURIComponent(filename);
			}
			
			popout.href = zenario.addBasePath(popout.href);
		}
		
		if (popout.css_class) {
			var cssClasses = ('' + popout.css_class).split(' ');
			popout.onOpen = function() { zenario.addClassesToColorbox(cssClasses); };
			popout.onClosed = function() { zenario.removeClassesToColorbox(cssClasses); };
		}
		
		if (popout.iframe && !defined(popout.width)) {
			popout.width = '93%';
		}
		if (popout.iframe && !defined(popout.height)) {
			popout.height = '90%';
		}
		
		if (item && item.width) {
			popout.initialWidth = item.width;
		}
		if (item && item.height) {
			popout.initialHeight = item.height;
		}
		
		if (!defined(popout.preloading)) {
			popout.preloading = false;
		}
		
		$.colorbox(popout);
	
	} else if (object.organizer_quick) {
		var id,
			path = object.organizer_quick.path;
		
		if (itemLevel && (id = zenarioCallingLibrary.getKeyId(true))) {
			path = path.replace(/\[\[id\]\]/g, id);
		}
		
		//zenarioA.organizerQuick = function(path, targetPath, minPath, maxPath, disallowRefinersLoopingOnMinPath, slotName, instanceId, reloadOnChanges, wrapperCSSClass)
		zenarioA.organizerQuick(
			path,
			
			//We don't need the target path variable when opening like this, but as a little hack I'll
			//fill it with something anyway.
			object.organizer_quick.min_path || object.organizer_quick.max_path || object.organizer_quick.path,
			
			object.organizer_quick.min_path, object.organizer_quick.max_path,
			engToBoolean(object.organizer_quick.disallow_refiners_looping_on_min_path),
			undefined, undefined, zenarioCallingLibrary.globalName);
	
	} else if (object.pick_items && !itemLevel) {
		
		if (object.pick_items.ajax) {
			zenarioCallingLibrary.postPickItemsObject = object.pick_items;
		}
		
		//Use Organizer in select mode to combine two items
		zenarioCallingLibrary.actionTarget =
			URLBasePath + 'zenario/ajax.php?' +
				'__pluginClassName__=' + object.pick_items.class_name +
				'&__path__=' + zenarioCallingLibrary.path +
				'&method_call=' + ajaxMethodCall;
		zenarioCallingLibrary.actionRequests = zenarioCallingLibrary.getKey(itemLevel);
		
		if (object.pick_items.request) {
			$.extend(zenarioCallingLibrary.actionRequests, object.pick_items.request);
		}
		
		zenarioA.organizerSelect(
			zenarioCallingLibrary.globalName, 'pickItems',
			object.pick_items.multiple_select,
			object.pick_items.path,
			object.pick_items.target_path,
			object.pick_items.min_path,
			object.pick_items.max_path,
			object.pick_items.disallow_refiners_looping_on_min_path,
			true,
			object.pick_items.one_to_one_choose_phrase,
			object.pick_items.one_to_many_choose_phrase,
			undefined, undefined, undefined, undefined, undefined,
			zenarioCallingLibrary.getLastKeyId(true),
			object.pick_items.allow_no_selection,
			object.pick_items.one_to_no_selection_choose_phrase,
			undefined, undefined,
			object.pick_items);
	
	} else if (object.combine_items && itemLevel) {
		
		if (object.combine_items.ajax) {
			zenarioCallingLibrary.postPickItemsObject = object.combine_items;
		}
		
		//Use Organizer in select mode to combine two items
		zenarioCallingLibrary.actionTarget =
			URLBasePath + 'zenario/ajax.php?' +
				'__pluginClassName__=' + object.combine_items.class_name +
				'&__path__=' + zenarioCallingLibrary.path +
				'&method_call=' + ajaxMethodCall;
		zenarioCallingLibrary.actionRequests = zenarioCallingLibrary.getKey(itemLevel);
		
		if (object.combine_items.request) {
			$.extend(zenarioCallingLibrary.actionRequests, object.combine_items.request);
		}
		
		zenarioA.organizerSelect(
			zenarioCallingLibrary.globalName, 'pickItems',
			object.combine_items.multiple_select,
			object.combine_items.path,
			object.combine_items.target_path,
			object.combine_items.min_path,
			object.combine_items.max_path,
			object.combine_items.disallow_refiners_looping_on_min_path,
			true,
			zenarioCallingLibrary.itemsSelected > 1 && object.combine_items.many_to_one_choose_phrase?
				object.combine_items.many_to_one_choose_phrase
			  : object.combine_items.one_to_one_choose_phrase,
			zenarioCallingLibrary.itemsSelected > 1 && object.combine_items.many_to_many_choose_phrase?
				object.combine_items.many_to_many_choose_phrase
			  : object.combine_items.one_to_many_choose_phrase,
			undefined, undefined, undefined, undefined, undefined,
			zenarioCallingLibrary.getKeyId(false),
			object.combine_items.allow_no_selection,
			zenarioCallingLibrary.itemsSelected > 1 && object.combine_items.many_to_no_selection_choose_phrase?
				object.combine_items.many_to_no_selection_choose_phrase
			  : object.combine_items.one_to_no_selection_choose_phrase,
			undefined, undefined,
			object.combine_items);
	
	} else if (object.ajax) {
		
		//Run an AJAX function
		zenarioCallingLibrary.actionTarget = url;
		zenarioCallingLibrary.actionRequests = requests;
		
		//If a confirm is set, set up a pop-up floating box box to ask the admin before carrying out the action
		if (object.ajax.confirm) {
			
			var isHTML = engToBoolean(object.ajax.confirm.html);
			var isDownload = engToBoolean(object.ajax.confirm.download);
			
			//Get the message/html for this box
			var message;
			
			if (object.ajax.confirm.message) {
				if (object.upload) {
					//Part of the backwards compatability hack for browsers without HTML 5 uploads above
					message = object.ajax.confirm.message;
			
				} else {
					//Otherwise apply any merge fields to the label from the calling library
					message = zenarioCallingLibrary.applyMergeFieldsToLabel(
						object.ajax.confirm.message,
						isHTML, itemLevel,
						object.ajax.confirm.multiple_select_message
					);
				}
			
			//If no message is set, try and get one using an AJAX call
			} else {
				message = zenario.nonAsyncAJAX(zenarioCallingLibrary.actionTarget + zenario.urlRequest(zenarioCallingLibrary.actionRequests), false);
			}
			
			//If this is a download, add the current search/sorting information in,
			//just in case the download should differ depending on the current view
			if (isDownload) {
				zenarioCallingLibrary.actionRequests._download = 1;
				
				if (defined(zenarioCallingLibrary.searchTerm)) {
					zenarioCallingLibrary.actionRequests._search = zenarioCallingLibrary.searchTerm;
				}
				
				if (zenarioCallingLibrary.prefs[zenarioCallingLibrary.path] && zenarioCallingLibrary.prefs[zenarioCallingLibrary.path].sortBy) {
					zenarioCallingLibrary.actionRequests._sort_col = zenarioCallingLibrary.prefs[zenarioCallingLibrary.path].sortBy;
					zenarioCallingLibrary.actionRequests._sort_desc = zenarioCallingLibrary.prefs[zenarioCallingLibrary.path].sortDesc? 1 : 0;
				} else {
					zenarioCallingLibrary.actionRequests._sort_col = zenarioCallingLibrary.labelTag;
				}
			}
			
			//Start generating the box.
			//If there is a form, the message should be surrounded by <form></form> tags.
			var html = '',
				buttonsHTML = '',
				r,
				formId = 'zenario_bc_form';
			
			if (isDownload || object.upload) {
				html += 
					_$html('form', 'id', formId, 'action', zenarioCallingLibrary.actionTarget + '&_sk_form_submission=1',
						'onsubmit', "get('preloader_circle').style.visibility = 'visible';",
						'target', 'zenario_iframe', 'method', 'post', 'enctype', object.upload && 'multipart/form-data',
					'>');
				
				foreach (zenarioCallingLibrary.actionRequests as r) {
					html += _$input('type', 'hidden', 'value', zenarioCallingLibrary.actionRequests[r], 'name', r);
				}
				
				if (!isHTML) {
					message = htmlspecialchars(message, true);
					isHTML = true;
				}
			}
			
			html += message;
			
			//If there is a form, the confirm button should submit the form...
			if (isDownload || object.upload) {
				html += '</form>';
				
				buttonsHTML =
					_$input('type', 'button', 'class', 'submit_selected', 'value', object.ajax.confirm.button_message, 'onclick', "get('" + formId + "').submit();");
			//...otherwise it should launch a syncronous AJAX request.
			} else {
				buttonsHTML =
					_$input('type', 'button', 'class', 'submit_selected', 'value', object.ajax.confirm.button_message, 'onclick', zenarioCallingLibrary.globalName + '.action2()');
			}
			
			buttonsHTML +=
				_$input('type', 'button', 'class', 'submit', 'value', object.ajax.confirm.cancel_button_message);
			
			
			zenarioA.showMessage(html, buttonsHTML, object.ajax.confirm.message_type, undefined, !isHTML);
			
			//If this was a fileupload in fallback mode, try to click the fileupload prompt straight away...
			if (object.upload) {
				//...except on IE, where this causes an error :(
				if (!zenario.browserIsIE()) {
					$('#zenario_fallback_fileupload').click();
				}
			}
		
		//If there is no confirmation then do the action straight away
		} else {
			zenarioCallingLibrary.action2();
		}
		
	
	} else if (object.help) {
		var messageType = object.help.message_type || 'question',
			htmlEscapeMessage = !engToBoolean(object.help.html);
		
		if (object.help.message) {
			zenarioA.showMessage(object.help.message, true, messageType, false, htmlEscapeMessage);
		}
	}
};



zenarioT.eval = function(condition, lib, tuixObject, item, id, button, column, field, section, tab, tuix) {
	
	var functionDetails, libName, methodName, ev;
	
	tuix = tuix || (lib && lib.tuix) || undefined;
	tuixObject = tuixObject || button || column || field || item || section || tab;
	
	//From version 7.5 onwards we're allowing arrays/objects to be passed in,
	//which should contain the name of a method and the inputs to give that method.
	//This should be a lot more efficient than calling eval
	if (typeof condition == 'object') {
		
		//Loop through each function requested, try to call each one, and
		//return false if any call fails.
		foreach (condition as functionDetails => ev) {
			
			functionDetails = functionDetails.split('.', 2);
			libName = functionDetails[0];
			methodName = functionDetails[1];
			
			if (!methodName) {
				methodName = libName;
				lib = window;
			
			} else if (libName != 'lib') {
				if (!(lib = window[libName])) {
					return false;
				}
			}
			
			if (!lib[methodName]
			 || !lib[methodName].apply(lib, zenarioT.tuixToArray(ev))) {
				return false;
			}
		}
		//If all calls were fine then return true
		return true;
	
	//Catch the case where this is alreay a function, and just run it
	} else if (typeof condition == 'function') {
		return condition();
	
	//Otherwise assume this is some code that we need to evaulate
	} else {
		try {
			ev = zenarioT.doEval(condition + '', lib, tuixObject, item, id, button, column, field, section, tab, tuix);
		} catch (e) {
			if (window.console && console.error) {
				console.error('JavaScript error in evaluated expression:', condition);
			}
			throw e;
		}
		
		//If the eval returned a function, call said function
		if (typeof ev == 'function') {
			ev = ev(tuixObject, item, id, tuix, button, column, field, section, tab);
		}
		
		return zenario.engToBoolean(ev);
	}
};




zenarioT.hidden = function(tuixObject, lib, item, id, button, column, field, section, tab, tuix) {
	tuixObject = tuixObject || button || column || field || item || section || tab;

	return !tuixObject
		|| engToBoolean(tuixObject.hidden)
		|| (tuixObject.visible_if && !zenarioT.eval(tuixObject.visible_if, lib, tuixObject, item, id, button, column, field, section, tab, tuix))
		|| (tuixObject.js_condition && !zenarioT.eval(tuixObject.js_condition, lib, tuixObject, item, id, button, column, field, section, tab, tuix));
	
	//N.b. "visible_if" used to be called "js_condition", so the js_condition line above is left for backwards compatability.
	//If you specify both on one field, then both are checked. (This is used in a couple of advanced cases where properties are merged together.)
};

zenarioT.checkFunctionExists = function(functionName, globalName) {
	if (globalName) {
		return window[globalName] && typeof window[globalName][functionName] == 'function';
	} else {
		return typeof window[functionName] == 'function';
	}
};




//Utility function for the zenarioXX.sortYYY() series of function
//Given two arrays (each of which represents an element), say which should be first
zenarioT.sortArray = function(a, b) {
	return zenarioT.sortLogic(a, b, 1);
};

zenarioT.sortArrayByOrd = function(a, b) {
	return zenarioT.sortLogic(a, b, 'ord');
};

zenarioT.sortArrayByOrdinal = function(a, b) {
	return zenarioT.sortLogic(a, b, 'ordinal');
};

zenarioT.sortArrayWithGrouping = function(a, b) {
	
	//Both fields are in the same grouping, or neither field is in a grouping.
	if (a[2] === b[2]) {
		//Check their ordinal normally
		return zenarioT.sortLogic(a, b, 1);
	
	//Field a is not in a grouping, but field b is
	} else if (!defined(a[2])) {
		//a's ordinal should be checked against the ordinal of b's grouping
		return zenarioT.sortLogic(a, b, 1, 2);
	
	//Field b is not in a grouping, but field a is
	} else if (!defined(b[2])) {
		//a's ordinal should be checked against the ordinal of b's grouping
		return zenarioT.sortLogic(a, b, 2, 1);
	
	//Both fields are in different groupings
	} else {
		//Compare the ordinal of the groupings against each other
		return zenarioT.sortLogic(a, b, 2);
	}
};

zenarioT.sortLogic = function(a, b, propA, propB) {
	
	var vA = a[propA], vB;
	
	if (!defined(propB)) {
		vB = b[propA];
	} else {
		vB = b[propB];
	}
	
	//Check to see if they're identical
	if (vA === vB) {
		return 0;
	
	} else {
		var aNumeric = vA == 1*vA,
			bNumeric = vB == 1*vB;
	
		//Try a numeric comparision
		if (aNumeric && bNumeric) {
			return 1*vA < 1*vB? -1 : 1;
	
		//Put any numeric values before strings
		} else if (aNumeric || bNumeric) {
			return aNumeric? -1 : 1;
		
		//Otherwise try a string comparision
		} else {
			return ('' + vA).toUpperCase() < ('' + vB).toUpperCase()? -1 : 1;
		}
	}
};


zenarioT.getSortedIdsOfTUIXElements = function(tuix, toSort, column, desc) {
	//Build an array to sort, containing:
		//0: The item's actual index
		//1: The value to sort by
		//2: Whether this value is numeric
	var value,
		numeric,
		i, thing,
		format = false,
		sortedArray = [];
	
	if (!column) {
		column = 'ord';
	}
	
	if (toSort == 'items'
	 && tuix.columns
	 && tuix.columns[column]) {
		format = tuix.columns[column].format;
	}
	
	if (!_.isObject(toSort)) {
		toSort = tuix[toSort];
	}
	
	if (toSort) {
		foreach (toSort as i => thing) {
			if (thing) {
				//Check if the value is a number, and if so make sure that it is numeric so it is sorted numericaly
				value = thing[column];
				
				if (format == 'true_or_false' || format == 'yes_or_no') {
					sortedArray.push([i, engToBoolean(value), true]);
				
				} else if (format != 'remove_zero_padding' && value == (numeric = 1*value)) {
					sortedArray.push([i, numeric, true]);
				
				} else if (value) {
					sortedArray.push([i, value.toLowerCase(), false]);
				
				} else {
					sortedArray.push([i, 0, true]);
				}
			}
		}
	}
	
	//Sort this array
	if (desc) {
		sortedArray.sort(zenarioT.sortArrayDesc);
	} else {
		sortedArray.sort(zenarioT.sortArrayForOrganizer);
	}
	
	//Remove fields that were just there to help sort
	foreach (sortedArray as i) {
		sortedArray[i] = sortedArray[i][0];
	}
	
	return sortedArray;
}

//Given two elements from the above function, say which order they should be in
zenarioT.sortArrayForOrganizer = function(a, b) {
	if (a[1] === b[1]) {
		//If their values are the same type and identical, say that they're identical
		return 0;
	
	} else if (a[2]? b[2] : !b[2]) {
		//If they're the same type, use a < to work out which is smallest
		return a[1] < b[1]? -1 : 1;
	
	} else {
		//Otherwise order by numeric data first, then strings
		return a[2]? -1 : 1;
	}
};

zenarioT.sortArrayDesc = function(a, b) {
	return zenarioT.sortArrayForOrganizer(b, a);
};

zenarioT.csvToObject = function(aString) {
	
	if (_.isString(aString)) {
		
		var anArrayIndex,
			anObject = {},
			anArray = aString.split(',');
		
		for (anArrayIndex in anArray) {
			if (anArray[anArrayIndex] !== '') {
				anObject[anArray[anArrayIndex]] = true;
			}
		}
		
		return anObject;
	}
	return aString;
};

//Accept either a CSV string, an object with keys to true or false,
//or an array (that might have been converted to an object),
//and convert it to a normal array.
zenarioT.tuixToArray = function(tuix) {
	
	if (_.isArray(tuix)) {
		return tuix;
	}
	
	var key, val,
		vals = [];
	
	switch (typeof tuix) {
		//Spilt strings up by commas
		case 'string':
			return tuix.split(/\s*,\s*/);
		
		//Check objects
		case 'object':
			foreach (tuix as key => val) {
				//If the keys look numeric, assume it was a numeric array in PHP that got converted to an object,
				//and note down the values.
				if (key == 1*key) {
					vals.push(val);
				
				//Otherwise assume it is an associative array and note down the keys, as long as the values were set to true
				} else if (engToBoolean(val)) {
					vals.push(key);
				}
			}
			
			break;
		
		default:
			vals = [tuix];
	}
	
	return vals;
};











zenarioT.setButtonKin = function(buttons, parentClass) {
	zenarioT.setKin(buttons, parentClass || 'organizer_button_with_children');
};

zenarioT.setKin = function(buttons, parentClass) {
	
	var bi, button, tuix,
		pi, parentId, parentButton,
		buttonsPos = {};
	
	foreach (buttons as bi => button) {
		delete button.children;
		buttonsPos[button.id] = bi;
	}
	
	//Add parent/child relationships
	foreach (buttons as bi => button) {
		
		//Accept either an array of TUIX objects, or a list of objects with pointers to TUIX objects.
		tuix = button.tuix || button;
		
		if (parentId = tuix.parent) {
			pi = buttonsPos[parentId];
			
			if (parentButton = buttons[pi]) {
				
				if (!parentButton.children) {
					parentButton.children = [];
					
					if (defined(parentClass)) {
						parentButton.css_class = parentButton.css_class? parentButton.css_class + ' ' + parentClass : parentClass;
					}
				}
				
				if (button.enabled
				 && !engToBoolean(tuix.remove_filter)) {
					parentButton.childEnabled = true;
				}
				if (button.current) {
					parentButton.childCurrent = true;
				}
				if (button.selected) {
					parentButton.childSelected = true;
				}
				
				parentButton.children.push(button);
			}
		}
	}
	
	//Remove children from the top-level buttons
	for (bi = buttons.length - 1; bi >= 0; --bi) {
		button = buttons[bi];
		tuix = button.tuix || button;
		
		if (tuix.parent || (engToBoolean(tuix.hide_when_children_are_not_visible) && !button.children)) {
			buttons.splice(bi, 1);
		}
	}
};

zenarioT.attempts = {};
zenarioT.attemptNum = 0;
zenarioT.keepTrying = function(fun, attempt) {
	if (!defined(attempt)) {
		attempt = ++zenarioT.attemptNum;
		zenarioT.attempts[attempt] = true;
	
	} else if (!zenarioT.attempts[attempt]) {
		return;
	}
	
	fun(attempt);
	
	setTimeout(function() {
		zenarioT.keepTrying(fun, attempt);
	}, 30000);
};

zenarioT.stopTrying = function(attempt) {
	if (!zenarioT.attempts[attempt]) {
		return false;
	
	} else {
		delete zenarioT.attempts[attempt];
		return true;
	}
};


//Attempt to strip out JSON encoded data from error messages
zenarioT.splitDataFromErrorMessage = function(resp) {
	
	if (_.isString(resp)) {
		resp = {responseText: resp};
	}
	
	if (!defined(resp.data)
	 && resp.responseText
	 && (end = resp.responseText.indexOf('{"')) != -1
	) {
		try {
			var data = JSON.parse(resp.responseText.substr(end));
		
			if (typeof data == 'object') {
				//If there was JSON encoded data, remove it and show the message without it
				resp.data = data;
				resp.responseText = resp.responseText.substr(0, end);
			}
		} catch (e) {
		}
	}
	
	return resp;
};

//JSON encode some TUIX, and also apply some common replacements as a simple way to try and get the size down a bit
//N.b. not currently used, so commented out to save space
//zenarioT.stringify = function(codedTUIX) {
//	return JSON.stringify(
//		codedTUIX.replace(/%/g, "%C")
//		
//		.replace(/"\:false/g, "%0")
//		.replace(/"\:true/g, "%1")
//		.replace(/"ajax"\:/g, "%a")
//		.replace(/"confirm"\:/g, "%f")
//		.replace(/"css_class"\:/g, "%c")
//		.replace(/"disabled"\:/g, "%d")
//		.replace(/"empty_value"\:/g, "%w")
//		.replace(/"enable_microtemplates_in_properties"\:/g, "%b")
//		.replace(/"grouping"\:/g, "%g")
//		.replace(/"hidden"\:/g, "%h")
//		.replace(/"hide_if_previous_value_isnt"\:/g, "%7")
//		.replace(/"hide_when_children_are_not_visible"\:/g, "%8")
//		.replace(/"hide_with_previous_field"\:/g, "%9")
//		.replace(/"label"\:/g, "%l")
//		.replace(/"last_edited"\:/g, "%e")
//		.replace(/"message"\:/g, "%m")
//		.replace(/"name"\:/g, "%n")
//		.replace(/"onclick"\:/g, "%k")
//		.replace(/"ord"\:/g, "%o")
//		.replace(/"parent"\:/g, "%p")
//		.replace(/"placeholder"\:/g, "%q")
//		.replace(/"post_field_html"\:/g, "%3")
//		.replace(/"pre_field_html"\:/g, "%2")
//		.replace(/"readonly"\:/g, "%z")
//		.replace(/"redraw_immediately_onchange"\:/g, "%4")
//		.replace(/"redraw_onchange"\:/g, "%5")
//		.replace(/"row_class"\:/g, "%r")
//		.replace(/"snippet"\:/g, "%s")
//		.replace(/"title"\:/g, "%i")
//		.replace(/"tooltip"\:/g, "%t")
//		.replace(/"tuix"\:/g, "%x")
//		.replace(/"type"\:/g, "%y")
//		.replace(/"value"\:/g, "%u")
//		.replace(/"visible_if"\:/g, "%v")
//		
//		.replace(/\~/g, "%S").replace(/"/g, "~").replace(/\+/g, "%P").replace(/\:/g, "+")
//	);
//};


//Reverse of the above
zenarioT.parse = function(codedTUIX) {
	return JSON.parse(codedTUIX
		.replace(/\~/g, '"').replace(/%S/g, '~').replace(/\+/g, ':').replace(/%P/g, '+')
		
		.replace(/%0/g, '":false')
		.replace(/%1/g, '":true')
		.replace(/%2/g, '"pre_field_html":')
		.replace(/%3/g, '"post_field_html":')
		.replace(/%4/g, '"redraw_immediately_onchange":')
		.replace(/%5/g, '"redraw_onchange":')
		.replace(/%7/g, '"hide_if_previous_value_isnt":')
		.replace(/%8/g, '"hide_when_children_are_not_visible":')
		.replace(/%9/g, '"hide_with_previous_field":')
		.replace(/%a/g, '"ajax":')
		.replace(/%b/g, '"enable_microtemplates_in_properties":')
		.replace(/%c/g, '"css_class":')
		.replace(/%d/g, '"disabled":')
		.replace(/%e/g, '"last_edited":')
		.replace(/%f/g, '"confirm":')
		.replace(/%g/g, '"grouping":')
		.replace(/%h/g, '"hidden":')
		.replace(/%i/g, '"title":')
		.replace(/%k/g, '"onclick":')
		.replace(/%l/g, '"label":')
		.replace(/%m/g, '"message":')
		.replace(/%n/g, '"name":')
		.replace(/%o/g, '"ord":')
		.replace(/%p/g, '"parent":')
		.replace(/%q/g, '"placeholder":')
		.replace(/%r/g, '"row_class":')
		.replace(/%s/g, '"snippet":')
		.replace(/%t/g, '"tooltip":')
		.replace(/%u/g, '"value":')
		.replace(/%v/g, '"visible_if":')
		.replace(/%w/g, '"empty_value":')
		.replace(/%x/g, '"tuix":')
		.replace(/%y/g, '"type":')
		.replace(/%z/g, '"readonly":')
		
		.replace(/%C/g, "%")
	);
};





//
//	Some shortcut functions aimed at reducing the amount of code in microtemplates
//

zenarioT.onChangeOrSearch = function() {
	return (zenario.browserIsIE() || zenario.browserIsEdge())? 'onchange' : 'onsearch';
};

zenarioT.showDevTools = function() {
	return (zenarioA.adminSettings || {}).show_dev_tools;
};

zenarioT.filter = function(filter) {
	
	if (filter === false) {
		return function(button) {
			return !button.location;
		};
	
	} else if (typeof filter == 'string') {
		return function(button) {
			return button.location == filter;
		};
	}
	
	return filter;
};

zenarioT.find = function(collection, filter) {
	return _.find(collection, zenarioT.filter(filter));
};


zenarioT.init = true;



//Calculate function short names, we need to do this before calling any functions!
zenario.shrtNms(zenarioT);


});
});