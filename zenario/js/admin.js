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

/*
	This file contains JavaScript source code.
	The code here is not the code you see in your browser. Before this file is downloaded:
	
		1. Compilation macros are applied (e.g. "foreach" is a macro for "for .. in ... hasOwnProperty").
		2. It is minified (e.g. using Google Closure Compiler).
		3. It may be wrapped togther with other files (this is to reduce the number of http requests on a page).
	
	For more information, see js_minify.shell.php for steps (1) and (2), and admin.wrapper.js.php for step (3).
*/

zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioAB, zenarioAT, zenarioO, strings,
	encodeURIComponent, get, engToBoolean, htmlspecialchars, jsEscape, phrase,
	extensionOf, methodsOf, has
) {
	"use strict";


phrase = phrase || {};

zenarioA.menuWandOn = true;
zenarioA.slotWandOn = false;
zenarioA.showGridOn = false;
zenarioA.storekeeperInitTime = 5000;
zenarioA.adminSettings = zenarioA.adminSettings || {};

zenarioA.tooltipLengthThresholds = {
	adminBoxTitle: 120,
	adminToolbarTitle: 60,
	organizerBackButton: 70,
	organizerPanelTitle: 100
};




zenarioA.microTemplate = function(template, data, filter) {
	return zenario.microTemplate(template, data, filter, zenarioA.microTemplates);
};

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
		booleans = {checked: 1, selected: 1};

	//Output attribute/value pairs
	for (; i < c; i += 2) {
		
		key = a[i - 1];
		val = a[i];
		
		if (key == 'checked' || key == 'disabled' || key == 'multiple' || key == 'selected') {
			if (val) {
				html += ' ' + key + '="' + key + '"';
			}
		} else {
			if (val !== false && val !== undefined) {
				html += ' ' + key + '="' + htmlspecialchars(val) + '"';
			}
		}
	}
		
	if (selfCloses) {
		html += '/>';
	} else {
		html += '>';
	}
	
	//Was there an odd number of attribute/value pairs?
	//If so, the last one should be the inner HTML of the element
	if (c % 2? noOffset : !noOffset) {
		postFieldHTML = a[c-1];
		
		//Use ">" as a flag to not close a tag and just return with it open
		if (postFieldHTML === '>') {
			return html;
		}
		
		html += postFieldHTML;
	}

	if (!selfCloses) {
		html += '</' + tag + '>';
	}

	return html;
};

//Create the following functions as shortcuts to the above function:
//	zenarioA.html = funciton(tag) {};
//	zenarioA.div = funciton() {};
//	zenarioA.span = funciton() {};
//	zenarioA.input = funciton() {};
//	zenarioA.select = funciton() {};
//	zenarioA.option = funciton() {};
//	zenarioA.h1 = funciton() {};
//	zenarioA.p = funciton() {};
_.each(['', 'div', 'span', 'input', 'select', 'option', 'h1', 'p'], function(el) {
	zenarioA[el || 'html'] = function(tag) {
		return htmlBaseFun(el || tag, arguments, el);
	}
});

//Create shortcut variables to the above functions and some common parameters,
//so the code ends up smaller when minified
(zenarioA.lib = function(fun) {
	fun(
		zenarioA.html,
		zenarioA.div,
		zenarioA.span,
		zenarioA.input,
		zenarioA.select,
		zenarioA.option,
		zenarioA.h1,
		zenarioA.p,
		'type',
		'button',
		'class',
		'submit',
		'value',
		'onclick',
		'style'
	);
})(function(
	_$html,
	_$div,
	_$span,
	_$input,
	_$select,
	_$option,
	_$h1,
	_$p,
	__$type,
	__$button,
	__$class,
	__$submit,
	__$value,
	__$onclick,
	__$style
) {

/*
var _$html = zenarioA.html,
	_$div = zenarioA.div,
	_$span = zenarioA.span,
	_$input = zenarioA.input,
	_$select = zenarioA.select,
	_$option = zenarioA.option,
	_$h1 = zenarioA.h1,
	_$p = zenarioA.p,
	__$type = 'type',
	__$button = 'button',
	__$class = 'class',
	__$submit = 'submit',
	__$value = 'value',
	__$onclick = 'onclick',
	__$style = 'style',
	'id' = 'id';
*/


zenarioA.showAJAXLoader = function() {
	$(document.body).addClass('zenario_adminAJAXLoaderOpen');
	zenarioA.openBox(_$div(), 'zenario_fbAdminAJAXLoader', 'AdminAJAXLoader', false, false, 50, 1, true, true, false, false);
};

zenarioA.hideAJAXLoader = function() {
	$(document.body).removeClass('zenario_adminAJAXLoaderOpen');
	zenarioA.closeBox('AdminAJAXLoader', false);
};

zenarioA.nowDoingSomething = function(something, showImmediately) {

	$('#zenario_now_loading').clearQueue().hide();
	$('#zenario_now_saving').clearQueue().hide();
	
	var $zenario_now_installing = $('#zenario_now_installing');
	if ($zenario_now_installing.length) {
		$zenario_now_installing.clearQueue().hide();
	}

	if (something) {
		if (showImmediately) {
			$('#zenario_now_' + something)
				.fadeIn(1000);
		} else {
			$('#zenario_now_' + something)
				.delay(900)
				.fadeIn(2000);
		}
	}
};



zenarioA.infoBox = function() {
	var html,
		moduleClassName = 'zenario_common_features',
		requests = {infoBox: 1},
		url = URLBasePath + 'zenario/ajax.php?__pluginClassName__=' + moduleClassName + '&method_call=handleAJAX' + zenario.urlRequest(requests);
	
	
	zenarioA.showAJAXLoader();
	zenario.ajax(url, false, true, false).after(function(data) {
		zenarioA.hideAJAXLoader();
		zenarioA.openBox(zenarioA.microTemplate('zenario_info_box', data), 'zenario_fbAdminInfoBox', 'AdminInfoBox', undefined, 405, undefined, undefined, false, true, '.zenario_infoBoxHead', false);
	});
};

zenarioA.closeInfoBox = function() {
	zenarioA.closeBox('AdminInfoBox');
};

zenarioA.showMessage = function(message, buttonsHTML, messageType, modal, htmlEscapeMessage, addCancel, cancelPhrase) {
	var end = false,
		hadCommand = false;

	if (message) {
		message = '' + message;
	} else {
		message = (zenarioA.adminSettings.show_dev_tools? phrase.errorTimedOutDev : phrase.errorTimedOut);
	}

	if (buttonsHTML === undefined) {
		buttonsHTML = true;
	}

	//Message types
	if (messageType === undefined) {
		messageType = 'none';
	}

	if		  (message.substr(0, 24) == '<!--Message_Type:None-->') {
		message = message.substr(24);
		messageType = false;
	} else if (message.substr(0, 25) == '<!--Message_Type:Error-->') {
		message = message.substr(25);
		messageType = 'error';
	} else if (message.substr(0, 27) == '<!--Message_Type:Success-->') {
		message = message.substr(27);
		messageType = 'success';
	} else if (message.substr(0, 27) == '<!--Message_Type:Warning-->') {
		message = message.substr(27);
		messageType = 'warning';
	} else if (message.substr(0, 28) == '<!--Message_Type:Question-->') {
		message = message.substr(28);
		messageType = 'question';
	}


	//Commands
		//N.b. a lot of these are deprecated and/or not used!

	//Reload Storekeeper
	if ((message.substr(0, 23) == '<!--Reload_Organizer-->' || message.substr(0, 25) == '<!--Reload_Storekeeper-->')
	 && (zenarioO.init && !window.zenarioOQuickMode && !window.zenarioOSelectMode)) {
		//Still show the Admin the contents of the message via an alert, if there was a message
		message = message.substr(25);
		if (message) {
			alert(message);
		}
		zenarioA.rememberToast();
	
		zenarioA.uploading = false;
		zenarioO.setWrapperClass('uploading', zenarioA.uploading);
	
		zenarioO.reloadPage();
	
		return false;

	//Refresh Storekeeper
	} else if (message.substr(0, 26) == '<!--Refresh_Storekeeper-->' && zenarioO.init && !window.zenarioOQuickMode && !window.zenarioOSelectMode) {
		zenarioO.reload();
		message = message.substr(26);
		hadCommand = true;

	//Go somewhere in Storekeeper
	} else if (message.substr(0, 28) == '<!--Go_To_Storekeeper_Panel:' && (end = message.indexOf('-->')) != -1) {
		zenarioO.go(message.substr(28, end - 28), -1);
		message = message.substr(end + 3);
		hadCommand = true;

	//Open an Admin Box
	} else if (message.substr(0, 19) == '<!--Open_Admin_Box:' && (end = message.indexOf('-->')) != -1) {
		zenarioAB.open(message.substr(19, end - 19));
		message = message.substr(end + 3);
		hadCommand = true;

	//Go somewhere
	} else if (message.substr(0, 14) == '<!--Go_To_URL:' && (end = message.indexOf('-->')) != -1) {
		zenarioA.rememberToast();
		zenario.goToURL(zenario.addBasePath(message.substr(14, end - 14)), true);
		message = message.substr(end + 3);
		hadCommand = true;
	}

	if (hadCommand && !message) {
		return false;
	}
	
	if (message.substr(0, 12) == '<!--Modal-->') {
		message = message.substr(12);
		modal = true;
	}

	//Set some custom buttons
	if (message.substr(0, 16) == '<!--Button_HTML:' && (end = message.indexOf('-->')) != -1) {
		buttonsHTML = message.substr(16, end - 16);
		message = message.substr(end + 3);

	} else if (message.substr(0, 18) == '<!--Reload_Button:' && (end = message.indexOf('-->')) != -1) {
		buttonsHTML = _$input(__$class, 'submit_selected', __$type, __$button, __$onclick, 'document.location.href = document.location.href; return false;', __$value, message.substr(18, end - 18));
		message = message.substr(end + 3);

	} else if (message.substr(0, 17) == '<!--Logged_Out-->') {
		
		//If the admin has been logged out, check to see whether this window is in an iframe, and show the login window in the iframe if possible.
		if (zenarioA.loggedOutIframeCheck(message, messageType)) {
			return;
		}
		
		if (zenarioO.init
		 && zenarioO.path
		 && zenarioA.isFullOrganizerWindow) {
			buttonsHTML = _$input(__$type, __$button, __$value, phrase.login, __$class, 'submit_selected', __$onclick, 'zenarioO.reloadPage(undefined, true);');
			
			addCancel = "zenario.goToURL(URLBasePath);";
		
		} else {
			buttonsHTML = 
				_$input(__$type, __$button, __$value, phrase.login, __$class, 'submit_selected', __$onclick, 'zenario.goToURL(zenario.linkToItem(zenario.cID, zenario.cType, zenarioA.importantGetRequests, true));');
			
			addCancel = "zenario.goToURL(zenario.linkToItem(zenario.cID, zenario.cType, zenarioA.importantGetRequests));";
		}
		
		message = message.substr(17);
		modal = true;
	}
	
	if (addCancel) {
		if (addCancel === true) {
			addCancel = '';
		}
		buttonsHTML += 
			_$input(__$type, __$button, __$class, __$submit, __$value, cancelPhrase || phrase.cancel, __$onclick, addCancel);
	}

	zenarioA.floatingBox(message, buttonsHTML, messageType, modal, htmlEscapeMessage);
	return true;
};





//Attempt to strip out JSON encoded data from error messages
zenarioA.splitDataFromErrorMessage = function(resp) {
	
	if (_.isString(resp)) {
		resp = {responseText: resp};
	}
	
	if (resp.responseText && (end = resp.responseText.indexOf('{"')) != -1) {
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


zenarioA.notification = function(message) {

	get('zenario_notification').style.display = '';
	get('zenario_notification').innerHTML = _$div(_$h1(htmlspecialchars(message)));

	$('#zenario_notification div')
		.clearQueue()
		.show({effect: 'drop', duration: 500, direction: 'up'})
		.delay(2500)
		.hide({effect: 'drop', duration: 500, direction: 'up', complete: function() {
			get('zenario_notification').style.display = 'none';
		}});
};


zenarioA.showHelp = function(selector) {
	var intro = introJs(),
		$fbog = $('#zenario_fbog'),
		$els = $(selector),
		steps = [],
		data;

	//Hack to get intro.js working with Organizer
	$fbog.addClass('zenario_introjs_fixPosition');

	//For each element, convert it into the format needed by intro.js
	$els.each(function(i, el) {
		data = $(el).data();
		data.element = el;
		steps.push(data);
	});

	//Sort by step number
	steps.sort(function(a, b) {
		if (a.step == b.step) {
			return 0;
		} else  {
			return a.step < b.step? -1 : 1;
		}
	});

	//Open intro.js
	intro.setOptions({
		steps: steps,
		nextLabel: zenarioA.phrase.next,
		prevLabel: zenarioA.phrase.prev
	});
	intro.start();

	intro.onexit(function() {
		//Hack to get intro.js working with Organizer
		$fbog.removeClass('zenario_introjs_fixPosition');
	});
};


zenarioA.showTutorial = function(nav, auto) {
	if (!nav && zenarioO.currentTopLevelPath) {
		nav = zenarioO.currentTopLevelPath.substr(0, zenarioO.currentTopLevelPath.indexOf('/'));
	}
	var videos = {},
		m = {
			videos: [],
			show_help_tour_next_time: zenarioA.show_help_tour_next_time,
			auto: auto
		},
		key, topLevelItem, video;
	
	// Get all tutorial videos
	foreach (zenarioO.map as key => topLevelItem) {
		if (topLevelItem
		 && topLevelItem.youtube_video_id) {
			videos[key] = {
				id: topLevelItem.youtube_video_id,
				title: topLevelItem.youtube_thumbnail_title
			};
		}
	}
	
	// If there is a video for the current nav
	var index = 0;
	if (nav && videos[nav]) {
		
		// Put videos in array with current nav first
		m.main_video_id = videos[nav].id;
		m.main_video_title = videos[nav].title;
		
		videos[nav].index = index++;
		videos[nav].selected = true;
		
		m.videos.push(videos[nav]);
		delete(videos[nav]);
	}
	
	// Add other videos
	foreach (videos as key => video) {
		video.index = index++;
		m.videos.push(video);
	}
	
	if (auto == true && m.main_video_id == undefined) {
		return;
	}
	
	// Open tutorial
	var html = zenarioA.microTemplate('zenario_tutorial', m);
	$.colorbox({
		width: 964,
		height: 791,
		innerHeight: 696,
		maxHeight: '95%',
		html: html,
		className: 'zenario_tutorial_cbox',
		overlayClose: false,
		escKey: false,
		onComplete: function() {
			if (m.videos.length > 0) {
				// Init slideshow
				zenarioA.initTutorialSlideshow();
			}
		}
	});
};


zenarioA.initTutorialSlideshow = function() {
	
	// Init jssor slideshow
	var options = {
		$FillMode: 1,
		$SlideDuration: 300,                                //[Optional] Specifies default duration (swipe) for slide in milliseconds, default value is 500
		$MinDragOffsetToSlide: 20,                          //[Optional] Minimum drag offset to trigger slide , default value is 20
		$SlideWidth: 200,                                   //[Optional] Width of every slide in pixels, default value is width of 'slides' container
		$SlideHeight: 150,                                //[Optional] Height of every slide in pixels, default value is height of 'slides' container
		$SlideSpacing: 75, 					                //[Optional] Space between each slide in pixels, default value is 0
		$DisplayPieces: 3,                                  //[Optional] Number of pieces to display (the slideshow would be disabled if the value is set to greater than 1), the default value is 1
		$ParkingPosition: 0,                              //[Optional] The offset position to park slide (this options applys only when slideshow disabled), default value is 0.
		$UISearchMode: 1,                                   //[Optional] The way (0 parellel, 1 recursive, default value is 1) to search UI components (slides container, loading screen, navigator container, arrow navigator container, thumbnail navigator container etc).
		$PlayOrientation: 1,                                //[Optional] Orientation to play slide (for auto play, navigation), 1 horizental, 2 vertical, default value is 1
		$DragOrientation: 1                                //[Optional] Orientation to drag slide, 0 no drag, 1 horizental, 2 vertical, 3 either, default value is 1 (Note that the $DragOrientation should be the same as $PlayOrientation when $DisplayPieces is greater than 1, or parking position is not 0)
		
		//$BulletNavigatorOptions: {                                //[Optional] Options to specify and enable navigator or not
		//	$Class: $JssorBulletNavigator$,                       //[Required] Class to create navigator instance
		//	$ChanceToShow: 2,                               //[Required] 0 Never, 1 Mouse Over, 2 Always
		//	$AutoCenter: 1,                                 //[Optional] Auto center navigator in parent container, 0 None, 1 Horizontal, 2 Vertical, 3 Both, default value is 0
		//	$Steps: 1,                                      //[Optional] Steps to go for each navigation request, default value is 1
		//	$Lanes: 1,                                      //[Optional] Specify lanes to arrange items, default value is 1
		//	$SpacingX: 0,                                   //[Optional] Horizontal space between each item in pixel, default value is 0
		//	$SpacingY: 0,                                   //[Optional] Vertical space between each item in pixel, default value is 0
		//	$Orientation: 1                                 //[Optional] The orientation of the navigator, 1 horizontal, 2 vertical, default value is 1
		//},
		
		//$ArrowNavigatorOptions: {
		//	$Class: $JssorArrowNavigator$,              //[Requried] Class to create arrow navigator instance
		//	$ChanceToShow: 2,                               //[Required] 0 Never, 1 Mouse Over, 2 Always
		//	$AutoCenter: 2,                                 //[Optional] Auto center navigator in parent container, 0 None, 1 Horizontal, 2 Vertical, 3 Both, default value is 0
		//	$Steps: 1                                    //[Optional] Steps to go for each navigation request, default value is 1
		//}
		
	};
	var jssor_slider1 = new $JssorSlider$("zenario_tutorial_other_videos", options);
	
	var SliderClickEventHandler = function(slideIndex, event) {
		$video = $('#zenario_tutorial_other_video_' + slideIndex);
		zenarioA.clickOtherTutorialVideo($video.data('id'), $video.data('title'), slideIndex);
	}
	
	jssor_slider1.$On($JssorSlider$.$EVT_CLICK, SliderClickEventHandler);
};


zenarioA.clickOtherTutorialVideo = function(id, title, index) {
	// Change main video
	var m = {
			main_video_id: id
		},
		html = zenarioA.microTemplate('zenario_tutorial_main_video', m);
	$('#zenario_tutorial_video_banner').html(title);
	$('#zenario_tutorial_main_video').html(html);
	
	// Toggle selected video
	$('#zenario_tutorial_other_videos_slideshow div.video').removeClass('selected');
	$('#zenario_tutorial_other_video_' + index).addClass('selected');
};

zenarioA.toggleShowHelpTourNextTime = function() {
	var val = $('#zenario_show_help_tour_next_time').prop('checked') ? 1 : 0,
		url = URLBasePath + 'zenario/admin/quick_ajax.php?_show_help_tour_next_time=' + val;
	zenario.ajax(url);
};

//Get information on a single item from Storekeeper 
zenarioA.getSKItem =
zenarioA.getItemFromOrganizer = function(path, id, async) {
	var i,
		data,
		first = false,
		url =
			URLBasePath +
			'zenario/admin/organizer.ajax.php?_start=0&_get_item_name=1&path=' + encodeURIComponent(path);
	
	if (id !== undefined) {
		url += '&_item=';
		
		if (typeof id == 'object') {
			foreach (id as i) {
				if (first) {
					first = false;
				} else {
					url += ',';
				}
				url += encodeURIComponent(id[i]);
			}
		} else {
			url += encodeURIComponent(id) + '&_limit=1';
		}
	}
	
	if (async) {
		return zenario.ajax(url, {}, true, false);

	} else if (data = zenario.checkSessionStorage(url, {}, true)) {
		return data;
	} else {
		data = zenario.nonAsyncAJAX(url, false, true);
		zenario.setSessionStorage(data, url, {}, true);
		return data;
	}
};

//Get information on a file
zenarioA.lookupFileDetails = function(id) {
	var i,
		data,
		first = false,
		url =
			URLBasePath +
			'zenario/admin/quick_ajax.php?lookupFileDetails=' + encodeURIComponent(id);

	if (data = zenario.checkSessionStorage(url, '', true)) {
		return data;
	} else {
		data = zenario.nonAsyncAJAX(url, false, true);
		zenario.setSessionStorage(data, url, '', true);
		return data;
	}
};


zenarioA.toggleSlotWand = function() {
	if (zenarioA.checkForEdits()) {
		zenarioA.slotWandOn = !zenarioA.slotWandOn;
		zenarioAT.clickTab(zenarioA.toolbar);
	}
};


zenarioA.toggleShowGrid = function() {
	if (zenarioA.checkForEdits()) {
		zenarioA.showGridOn = !zenarioA.showGridOn;
		zenarioAT.clickTab(zenarioA.toolbar);
	}
};

zenarioA.checkSpecificPerms = function(id) {
	
	if (!zenarioA.adminHasSpecificPerms) {
		return true;
	}
	
	var i, ids = id.split(',');

	foreach (ids as i => id) {
		if (!zenarioO.tuix
		 || !zenarioO.tuix.items
		 || !zenarioO.tuix.items[id]
		 || !zenarioO.tuix.items[id]._specific_perms) {
			return false;
		}
	}
	
	return true;
};

zenarioA.checkSpecificPermsOnThisPage = function() {
	return !zenarioA.adminHasSpecificPerms || zenarioA.adminHasSpecificPermsOnThisPage;
};














//Code to add the "zenario_slotParent" class to the elements just above slots
	//When you hover over a slot, the control box for that slot, or the zenario_slotControlsWrap for that slot,
	//the CSS class zenario_slotParent should be added just above that slot.
	//When you move your mouse to another slot, the CSS class should be immediately removed from that slot, and its Plugin Options dropdown should be immediately closed.
	//When you move your mouse away from the slot, but not over another slot, it should be removed after a short delay.
var slotParentMouseOverLastId = false,
	slotControlHide = false,
	slotControlHoverInterval = 1500,
	slotControlCloseInterval = 100,
	openSlotControlsBox = false,
	wasFromAdminToolbar = false,
	slotControlClose;


zenarioA.slotParentMouseOver = function(event) {
	if (slotControlHide) {
		clearTimeout(slotControlHide);
	}
	
	$('.zenario_slotParent').removeClass('zenario_slotParent');
	
	var id;
	if (event) {
		if (this.className != 'zenario_slotControlsWrap') {
			id = $(this).attr('id').replace('-control_box', '');
			$(this).parent().addClass('zenario_slotParent');
		} else {
			id = 'plgslt_' + (this.id + '').replace('zenario_fbAdminPluginOptionsWrap-', '');
			$('#' + id).parent().addClass('zenario_slotParent');
		}
		
		if (slotParentMouseOverLastId && slotParentMouseOverLastId != id) {
			zenarioA.closeSlotControls();
		}
		slotParentMouseOverLastId = id;
	} else {
		zenarioA.closeSlotControls();
		slotParentMouseOverLastId = false;
	}
};

zenarioA.slotParentMouseOut = function(a) {
	if (slotControlHide) {
		clearTimeout(slotControlHide);
	}
	
	if (!wasFromAdminToolbar) {
		slotControlHide = setTimeout(zenarioA.slotParentMouseOver, slotControlHoverInterval);
	}
};

zenarioA.setSlotParents = function() {
	$('.zenario_slotAdminControlBox').parent().children()
		.mouseenter(zenarioA.slotParentMouseOver)
		.mouseleave(zenarioA.slotParentMouseOut);
	$('#zenario_slotControls .zenario_slotControlsWrap')
		.mouseenter(zenarioA.slotParentMouseOver)
		.mouseleave(zenarioA.slotParentMouseOut);
};


zenarioA.getGridSlotDetails = function(slotName) {
	//Get the grid span from the slot name
	var $gridspan = $('.' + slotName + '.span.slot'),
		grid = {
			container: false,
			cssClass: false,
			columns: false,
			width: false,
			widthInfo: false
		};
	
	if ($gridspan.length) {
		//Attempt to get the CSS class names of the wrapper of the slot
		//(it's easier to look this up using JavaScript than it is to work it out in fillAllAdminSlotControls() in php).
		grid.cssClass = $gridspan.attr(__$class),
		
		//Strip out "alpha" and "omega" from the class names
		grid.cssClass = grid.cssClass.replace(' alpha ', ' ').replace(' omega ', ' ');
		
		//Get the actual width of the slot
		var fluidWidth = false,
			widthInfo = '',
			pxWidth = $gridspan.width(),
			container,
		
			//Try and read the number of columns from the css class names, e.g. "span3"
			css = $gridspan.attr(__$class) || '',
			columns = css.match(/\bspan\d+\b/);
	
		if (columns) {
			columns = 1 * columns[0].match(/\d+/);
		}
	
		try {
			//Loop through each stylesheet/rule, checking to see if there is a grid and a "span" rule that matches this span
			//Adapted from http://stackoverflow.com/questions/324486/how-do-you-read-css-rule-values-with-javascript
			outerLoop:
			foreach (document.styleSheets as var i => var s) {
				var rules = s.rules || s.cssRules;
		
				innerLoop:
				foreach (rules as var j => var rule) {
					if (rule.selectorText
					 && rule.style.width
					 && ('' + rule.selectorText).match(/\.span/)
					 && $gridspan.is(rule.selectorText)) {
						widthInfo = rule.style.width;
						break outerLoop;
					}
				}
			}
		} catch (e) {
			widthInfo = '';
		}
	
		if (!widthInfo) {
			widthInfo = pxWidth + 'px';
		} else if (!widthInfo.match(/\d+px/)) {
			fluidWidth = true;
		}
	
		if (fluidWidth) {
			widthInfo += ' (' + pxWidth + ' px ' + phrase.atCurrentSize + ')';
		}
	
		if (columns) {
			if (columns == 1) {
				widthInfo = '1 column, ' + widthInfo;
			} else {
				widthInfo = columns + ' columns, ' + widthInfo;
			}
		}
		
		grid.widthInfo = widthInfo;
		grid.pxWidth = pxWidth;
		grid.columns = columns;
		
		
		//Work out the size of the container
		if ((container = $gridspan.closest('div.container'))
		 && (container = container.attr(__$class))
		 && (container = container.match(/container_(\d+)/))
		 && (container[1])) {
			grid.container = 1*container[1];
		}
	}
	
	return grid;
};




/*  Functions for managing plugin slots  */

zenarioA.openSlotControls = function(el, e, slotName, isFromAdminToolbar) {
	
	var closeAsIsAlreadyOpen = !isFromAdminToolbar && $('#zenario_fbAdminSlotControls-' + slotName).is(':visible'),
		left,
		top,
		thisSlotControlsBox = 'AdminSlotControls-' + slotName;
	
	el.blur();
	
	if (!isFromAdminToolbar
	  || openSlotControlsBox != thisSlotControlsBox) {
		zenarioA.closeSlotControls();
	}
	
	if (!closeAsIsAlreadyOpen && zenarioA.checkForEdits()) {
		
		//If this was opened from the admin toolbar, keep the drop-down menu open for now
		if (wasFromAdminToolbar = isFromAdminToolbar) {
			$('#zenario_at_toolbars .zenario_at_slot_controls ul ul').css('display', 'block');
			slotParentMouseOverLastId = false;
		}
		
		
		var width,
			section,
			sections = {info: false, notes: false, actions: false, overridden_info: false, overridden_actions: false},
			instanceId = zenario.slots[slotName].instanceId,
			grid = zenarioA.getGridSlotDetails(slotName);
		
		if (get('zenario_fbAdminSlotControls-' + slotName).innerHTML.indexOf('zenario_long_option') == -1) {
			width = 255;
		} else {
			width = 280;
		}
		
		if (isFromAdminToolbar) {
			left = 200;
			top = 0;
		} else {
			left = -width + 34;
			top = 32;
		}
		
		zenarioA.openBox(
			undefined,
			get('plgslt_' + slotName + '-wrap').className + ' zenario_fbAdminSlotControls',
			openSlotControlsBox = thisSlotControlsBox,
			e, width, left, top, false, false, false, false);
		
		//Check that each section has at least one label or button in it. If not, hide that section
		foreach (sections as section) {
			$('#zenario_fbAdminSlotControls-' + slotName + ' .zenario_slotControlsWrap_' + section).show();
			
			$('#zenario_fbAdminSlotControls-' + slotName + ' .zenario_slotControlsWrap_' + section + ' .zenario_sc:visible').each(function(i, el) {
				sections[section] = true;
			});
		
			if (!sections[section]) {
				$('#zenario_fbAdminSlotControls-' + slotName + ' .zenario_slotControlsWrap_' + section).hide();
			}
		}
		
		//Set the CSS class that the grid is using
		if (grid.cssClass) {
			//Strip out some technical class-names that make the grid work but designers don't need to see
			grid.cssClass = grid.cssClass.replace(/\bspan\d*_?\d*\s/g, '');
			
			$('#zenario_slot_control__' + slotName + '__info__' + 'grid_css_class').show();
			$('#zenario_slot_control__' + slotName + '__info__' + 'grid_css_class > span').text(grid.cssClass);
		} else {
			$('#zenario_slot_control__' + slotName + '__info__' + 'grid_css_class').hide();
		}
		
		//Set the width of this slot
		if (grid.widthInfo) {
			$('#zenario_slot_control__' + slotName + '__info__' + 'grid_width').show();
			$('#zenario_slot_control__' + slotName + '__info__' + 'grid_width > span').text(grid.widthInfo);
		} else {
			$('#zenario_slot_control__' + slotName + '__info__' + 'grid_width').hide();
		}
		
		//Don't show the "copy embed link" option if this browser doesn't support copy and paste
		if (!zenario.canCopy()) {
			$('#zenario_slot_control__' + slotName + '__info__' + 'embed').hide();
		}
		
		$('#plgslt_' + slotName + '-control_box').addClass('zenario_adminSlotControlsOpen');
	}
	
	return false;
};

zenarioA.copyEmbedLink = function(link) {
	zenarioA.copy(link);
	zenarioA.closeSlotControls();
};

zenarioA.copy = function(text) {
	if (zenario.copy(text)) {
		zenarioA.toast({
			message_type: 'success',
			message: phrase.copied
		});
	}
};

zenarioA.copyEmbedHTML = function(link, slotName) {
	var $slot = $('#plgslt_' + slotName);
	
	zenarioA.copy(_$html('iframe', 'width', $slot.outerWidth(true), 'height', $slot.outerHeight(true), 'src', link, 'frameborder', 0));
	zenarioA.closeSlotControls();
};

zenarioA.dontCloseSlotControls = function() {
	if (slotControlClose) {
		clearTimeout(slotControlClose);
	}
};

zenarioA.closeSlotControlsAfterDelay = function() {
	zenarioA.dontCloseSlotControls();
	
	if (!wasFromAdminToolbar) {
		slotControlClose = setTimeout(zenarioA.closeSlotControls, slotControlCloseInterval);
	}
};

zenarioA.closeSlotControls = function() {
	zenarioA.dontCloseSlotControls();
	if (openSlotControlsBox) {
		zenarioA.closeBox(openSlotControlsBox, true, {effect: 'fade', duration: 200});
		$('.zenario_slotAdminControlBox').removeClass('zenario_adminSlotControlsOpen');
		
		//Allow the slot controls on the admin toolbar to be closed once again
		$('#zenario_at_toolbars .zenario_at_slot_controls ul ul').css('display', '');
	}
};




zenarioA.pickNewPluginSlotName = false;
zenarioA.pickNewPlugin = function(el, slotName, level, showADifferentPlugin) {
	el.blur();
	
	zenarioA.pickNewPluginSlotName = slotName;
	zenarioA.pickNewPluginLevel = level;
	
	var path = 'zenario__modules/panels/modules/refiners/slotable_only////';
	
	//Select the existing module and plugin if possible
	if (!showADifferentPlugin
	 && zenario.slots[slotName]
	 && zenario.slots[slotName].moduleId
	 && zenario.slots[slotName].instanceId) {
		path += 'item//' + zenario.slots[slotName].moduleId + '//' + zenario.slots[slotName].instanceId;
	}
	
	zenarioA.organizerSelect('zenarioA', 'addNewReusablePlugin', false, path, 'zenario__modules/panels/plugins', 'zenario__modules/panels/modules', 'zenario__modules/panels/plugins', true, true, phrase.insertReusablePlugin);
	
	return false;
};

zenarioA.addNewReusablePlugin = function(path, key, row) {
	var instanceId = key.id, slotName = zenarioA.pickNewPluginSlotName, level = zenarioA.pickNewPluginLevel;
	
	var error = 
		zenario.moduleNonAsyncAJAX('zenario_common_features', {addPluginInstance: instanceId, slotName: slotName, level: level, cID: zenario.cID, cType: zenario.cType, cVersion: zenario.cVersion}, true);
	
	if (error) {
		zenarioA.showMessage(error);
	} else {
		zenario.refreshPluginSlot(slotName, '');
	}
};


zenarioA.addNewWireframePlugin = function(el, slotName, moduleId) {
	el.blur();
	
	var req = {
			addPlugin: moduleId,
			slotName: slotName,
			level: 2,
			cID: zenario.cID,
			cType: zenario.cType,
			cVersion: zenario.cVersion
		},
		html = zenario.moduleNonAsyncAJAX('zenario_common_features', req, false);
	
	if (zenarioA.loggedOut(html)) {
		return;
	}
	
	zenarioA.floatingBox(html, $(el).text(), 'warning', false, false, function() {
	
		var error = zenario.moduleNonAsyncAJAX('zenario_common_features', req, true);
	
		if (error) {
			zenarioA.showMessage(error);
		} else {
			zenario.refreshPluginSlot(slotName, '');
		}
	});
	
	return false;
};


//Show the thickbox for editing the instance in the slot, if there is one
zenarioA.pluginSlotEditSettings = function(el, slotName, fabPath) {
	el.blur();
	var instanceId = zenario.slots[slotName].instanceId;
	
	if (!get('zenario_theme_name_' + slotName + '__0') && instanceId) {
		zenarioAB.open(fabPath || 'plugin_settings', {cID: zenario.cID, cType: zenario.cType, cVersion: zenario.cVersion, slotName: slotName, instanceId: instanceId, frontEnd: 1});
	}
	
	return false;
};

//Moving modules
zenarioA.movePlugin = function(el, slotName) {
	el.blur();
	
	zenarioA.floatingBox(phrase.movePluginDesc, true, 'question', true, true, function() {
		zenarioA.moveSource = slotName;
		$('.zenario_slotAdminControlBox').addClass('zenario_moveDestination');
		$('#plgslt_' + slotName + '-control_box').removeClass('zenario_moveDestination').addClass('zenario_moveSource');
	});
	
	return false;
};

zenarioA.doMovePlugin = function(el, moveDestination) {
	el.blur();
	
	var moveSource = zenarioA.moveSource;
	zenarioA.cancelMovePlugin(el);
	
	if (moveSource && moveDestination) {
		if (zenarioA.toolbar == 'edit') {
			zenarioA.doMovePlugin2(moveSource, moveDestination, 1);
		} else if (zenarioA.toolbar == 'template') {
			var html = zenario.moduleNonAsyncAJAX('zenario_common_features', {movePlugin: 1, level: 2, cID: zenario.cID, cType: zenario.cType, cVersion: zenario.cVersion}, false);
			
			if (zenarioA.loggedOut(html)) {
				return;
			}
			
			zenarioA.floatingBox(html, phrase.movePlugin, 'warning', false, false, function() {
				zenarioA.doMovePlugin2(moveSource, moveDestination, 2);
			});
		}
	}
	
	return false;
};

zenarioA.doMovePlugin2 = function(moveSource, moveDestination, level) {
	var error = 
		zenario.moduleNonAsyncAJAX('zenario_common_features', {movePlugin: 1, level: level, slotNameSource: moveSource, slotNameDestination: moveDestination, cID: zenario.cID, cType: zenario.cType, cVersion: zenario.cVersion}, true);
	
	if (error) {
		zenarioA.showMessage(error);
	} else {
		zenario.refreshPluginSlot(moveSource, '', zenarioA.importantGetRequests);
		zenario.refreshPluginSlot(moveDestination, '', zenarioA.importantGetRequests);
	}
};

zenarioA.cancelMovePlugin = function(el) {
	if (el) el.blur();
	
	delete zenarioA.moveSource;
	$('.zenario_slotAdminControlBox').removeClass('zenario_moveDestination').removeClass('zenario_moveSource');
	
	return false;
};




zenarioA.refreshAllSlotsWithCutCopyPaste = function(allowedModules) {
	
	//Try to get a list of every type of plugin affected
	var slotName,
		module,
		modules = zenarioA.csvToObject(allowedModules);
	
	modules.zenario_banner = true;
	modules.zenario_html_snippet = true;
	modules.zenario_wysiwyg_editor = true;
	
	//Reload the contents of every slot these plugins are in (version controlled plugins only)
	foreach (modules as module) {
		if (window[module]
		 && window[module].slots) {
			foreach (window[module].slots as slotName) {
				if (zenario.slots[slotName]
				 && zenario.slots[slotName].isVersionControlled) {
					window[module].refreshPluginSlot(slotName, false, false, false);
				}
			}	
		}
	};
};
	
zenarioA.copyContents = function(el, slotName, allowedModules) {
	el.blur();
	
	var error = 
		zenario.moduleNonAsyncAJAX('zenario_common_features', {copyContents: 1, allowedModules: allowedModules, slotName: slotName, cID: zenario.cID, cType: zenario.cType, cVersion: zenario.cVersion}, true);
	
	if (error) {
		zenarioA.showMessage(error);
	} else {
		zenarioA.refreshAllSlotsWithCutCopyPaste(allowedModules);
	}
};

zenarioA.cutContents = function(el, slotName, allowedModules) {
	el.blur();
	
	var error = 
		zenario.moduleNonAsyncAJAX('zenario_common_features', {cutContents: 1, allowedModules: allowedModules, slotName: slotName, cID: zenario.cID, cType: zenario.cType, cVersion: zenario.cVersion}, true);
	
	if (error) {
		zenarioA.showMessage(error);
	} else {
		zenarioA.refreshAllSlotsWithCutCopyPaste(allowedModules);
	}
};

zenarioA.pasteContents = function(el, slotName) {
	el.blur();
	
	var error = 
		zenario.moduleNonAsyncAJAX('zenario_common_features', {pasteContents: 1, slotName: slotName, cID: zenario.cID, cType: zenario.cType, cVersion: zenario.cVersion}, true);
	
	if (error) {
		zenarioA.showMessage(error);
	} else {
		zenario.refreshPluginSlot(slotName, '', undefined, false, false, false, false);
	}
};

zenarioA.overwriteContents = function(el, slotName) {
	el.blur();
	
	zenarioA.floatingBox(phrase.overwriteContentsConfirm, $(el).text(), 'warning', false, false, function() {
		var error = 
			zenario.moduleNonAsyncAJAX('zenario_common_features', {overwriteContents: 1, slotName: slotName, cID: zenario.cID, cType: zenario.cType, cVersion: zenario.cVersion}, true);
	
		if (error) {
			zenarioA.showMessage(error);
		} else {
			zenario.refreshPluginSlot(slotName, '', undefined, false, false, false, false);
		}
	});
	
	return false;
};

zenarioA.swapContents = function(el, slotName) {
	el.blur();
	
	zenarioA.floatingBox(phrase.swapContentsConfirm, $(el).text(), 'warning', false, false, function() {
		var error = 
			zenario.moduleNonAsyncAJAX('zenario_common_features', {swapContents: 1, slotName: slotName, cID: zenario.cID, cType: zenario.cType, cVersion: zenario.cVersion}, true);
	
		if (error) {
			zenarioA.showMessage(error);
		} else {
			zenario.refreshPluginSlot(slotName, '', undefined, false, false, false, false);
		}
	});
	
	return false;
};




zenarioA.removePlugin = function(el, slotName, level) {
	el.blur();
	
	var req = {
			removePlugin: 1,
			level: level,
			slotName: slotName,
			cID: zenario.cID,
			cType: zenario.cType,
			cVersion: zenario.cVersion
		},
		doRemovePlugin = function() {
			var error = 
				zenario.moduleNonAsyncAJAX('zenario_common_features', req, true);
	
			if (error) {
				zenarioA.showMessage(error);
			} else {
				zenario.refreshPluginSlot(slotName, '', undefined, false, false, false, false);
			}
		};
	
	if (level > 1) {
		html = zenario.moduleNonAsyncAJAX('zenario_common_features', req, false);
		
		if (zenarioA.loggedOut(html)) {
			return;
		}
	
		zenarioA.floatingBox(html, phrase.remove, true, false, false, doRemovePlugin);
	} else {
		doRemovePlugin();
	}
	
	return false;
};

	
zenarioA.hidePlugin = function(el, slotName) {
	el.blur();
	
	var error = 
		zenario.moduleNonAsyncAJAX('zenario_common_features', {hidePlugin: 1, slotName: slotName, cID: zenario.cID, cType: zenario.cType, cVersion: zenario.cVersion}, true);
	
	if (error) {
		zenarioA.showMessage(error);
	} else {
		zenario.refreshPluginSlot(slotName, '', undefined, false, false, false, false);
	}
};

	
zenarioA.showPlugin = function(el, slotName) {
	el.blur();
	
	var error = 
		zenario.moduleNonAsyncAJAX('zenario_common_features', {showPlugin: 1, slotName: slotName, cID: zenario.cID, cType: zenario.cType, cVersion: zenario.cVersion}, true);
	
	if (error) {
		zenarioA.showMessage(error);
	} else {
		zenario.refreshPluginSlot(slotName, '', undefined, false, false, false, false);
	}
};



/*  Reloading Slots  */

//Callback function for refreshPluginSlot()
zenarioA.replacePluginSlot = function(slotName, instanceId, level, tabId, resp, scriptsToRunBefore) {
	
	var flags = resp.flags,
		moduleId = 1*flags.MODULE_ID,
		isVersionControlled = flags.WIREFRAME,
		beingEdited = flags.IN_EDIT_MODE,
		className = flags.NAMESPACE,
		slotControls = flags.SLOT_CONTROLS,
		slotControlsCSSClass = flags.SLOT_CONTROLS_CSS_CLASS;
	
	if (moduleId && (!window[className] || _.isEmpty(window[className].slots))) {
		zenario.addPluginJavaScript(moduleId);
	}
	
	if (!moduleId) {
		instanceId = 0;
	}
	
	//Set the current instance id
	zenario.slot([[slotName, instanceId, moduleId, level, tabId, undefined, beingEdited, isVersionControlled]]);
	
	//Add a css class around slots that are being edited using the WYSIWYG Editor
	if (beingEdited) {
		slotControlsCSSClass += ' zenario_slot_being_edited';
	}
	
	//Set the CSS class for the slot's admin wrapper/slot controls
	get('plgslt_' + slotName + '-wrap').className = slotControlsCSSClass;
	
	//If any slots are being edited, set a warning message for if an admin tries to leave the page 
	window.onbeforeunload = zenarioA.onbeforeunload;
	
	//Remember that this slot is being edited
	zenario.slots[slotName].beingEdited = beingEdited;
	
	foreach (scriptsToRunBefore as var script) {
		if (zenario.slots[slotName]) {
			zenario.callScript(scriptsToRunBefore[script], zenario.slots[slotName].moduleClassName);
		}
	}
	
	//Refresh the slot's innerHTML
	get('plgslt_' + slotName).innerHTML = resp.responseText;
	get('zenario_fbAdminSlotControlsContents-' + slotName).innerHTML = slotControls;
	
	
	//Set tooltips for the area, if we are using tooltips
	zenario.tooltips('#plgslt_' + slotName + ' a');
	zenario.tooltips('#plgslt_' + slotName + ' img');
	zenario.tooltips('#plgslt_' + slotName + ' input');
};


zenarioA.checkSlotsBeingEdited = function() {
	if (zenario.slots) {
		foreach (zenario.slots as var slotName => var slot) {
			if (slot.beingEdited) {
				$('body').addClass('zenario_being_edited');
				return true;
			}
		}
	}
	
	$('body').removeClass('zenario_being_edited');
	return false
};

zenarioA.checkForEdits = function() {
	//Don't allow something to be changed whilst in edit mode
	if (zenarioA.toolbar == 'edit' && zenarioA.checkSlotsBeingEdited()) {
		zenarioA.showMessage(phrase.editorOpen);
		return false;
	} else {
		return true;
	}
};

zenarioA.onbeforeunload = function() {
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
	} else if (zenarioA.checkSlotsBeingEdited()) {
		return phrase.leavePageWarning;
	
	} else {
		return undefined;
	}
};





//Pop up messages/boxes


zenarioA.boxesOpen = {};
zenarioA.openBox = function(html, className, n, e, width, left, top, disablePageBelow, overlay, draggable, resizable, padding, maxHeight, rightCornerOfElement, bottomCornerOfElement) {
	
	if (!n) {
		n = 1;
	}
	
	if (className === undefined) {
		className = '';
	}
	
	var $box,
		$overlay,
		zIndex;
	
	if (disablePageBelow) {
		//Stop the page behind from scrolling
		zenario.disableScrolling(n);
	}
	
	if (disablePageBelow || overlay) {
		if (!get('zenario_fb' + n + '__overlay')) {
			var s, split, plit, overlayClassName = 'zenario_overlay';
			
			if (className) {
				split = className.split(' ');
				foreach (split as s => plit) {
					if (plit) {
						overlayClassName += ' ' + plit + '__overlay';
					}
				}
			}
			
			$('body').append(_$div(__$class, overlayClassName, 'id', 'zenario_fb' + n + '__overlay', __$style, 'display: none;'));
		}
		$overlay = $('#zenario_fb' + n + '__overlay');
	}
	
	if (!get('zenario_fb' + n)) {
		$('body').append(_$div('id', 'zenario_fb' + n));
	}
	get('zenario_fb' + n).className = className;
	$box = $('#zenario_fb' + n);
	
	if (draggable === true) {
		$box.draggable({handle: '.zenario_drag', cancel: '.zenario_no_drag', containment: 'window'});
	
	} else if (typeof draggable == 'string') {
		$box.draggable({handle: draggable, cancel: '.zenario_no_drag', containment: 'window'});
	}
	
	
	zenarioA.adjustBox(n, e, width, left, top, html, padding, maxHeight, rightCornerOfElement, bottomCornerOfElement);
	
	
	if (disablePageBelow || overlay) {
		zIndex = $box.css('z-index');
		$overlay.css('z-index', (1 * zIndex - 1) || 0).show().unbind('click');
		
		if (!disablePageBelow) {
			$overlay.click(function() {
				zenarioA.closeBox(n);
			});
		}
		
		$overlay.show();
	}
	
	if (resizable) {
		if (resizable === true) {
			resizable = {};
		}
		
		if (resizable.containment === undefined) {
			resizable.containment = 'document';
		}
		if (resizable.minWidth === undefined) {
			resizable.minWidth = width;
		}
		
		$box.resizable(resizable);
	}
	
	$('body').addClass('zenario_fb' + n + '__isOpen');
	$box.show();
};

zenarioA.adjustBox = function(n, e, width, left, top, html, padding, maxHeight, rightCornerOfElement, bottomCornerOfElement) {
	
	var wrapper = get('zenario_fb' + n);
	
	
	//var fromElement = false;
	
	if (!n) {
		n = 1;
	}
	
	if (top === undefined) {
		top = 15;
	}
	
	if (left === undefined) {
		left = 50;
	}
	
	if (padding === undefined) {
		padding = 15;
	}
	
	if (!width) {
		left = 0;
		padding = 0;
		wrapper.style.left = '0';
		wrapper.style.width = '100%';
	} else {
		wrapper.style.width = width + 'px';
	}
	
	
	if (html !== false && html !== undefined) {
		wrapper.innerHTML = html;
		zenario.addJQueryElements('#zenario_fb' + n + ' ', true);
	}
	
	if (maxHeight === undefined) {
		maxHeight = $('#zenario_fb' + n).height() || 50;
	}
	
	
	//Position the floating box
	//e can be a mouse event, or an object that was clicked on
	//If e is provided, then position it relative to the mouse/object. Otherwise position it relative to the screen.
	if (e) {
		var y;
		if (e.clientY !== undefined || e.pageY !== undefined) {
			y = zenario.getMouseY(e) + top;
		} else {
			y = $(e).offset().top + top;
			
			if (bottomCornerOfElement) {
				y += $(e).height();
			}
		}
		
		//Check the box is not off the screen, and move it back if so
		var minY = 1 + zenario.scrollTop();
		if (y < minY) {
			y = minY;
		} else {
			
			var windowHeight = $(window).height();
			
			if (zenarioA.pageEl) {
				windowHeight = Math.min(windowHeight, $(zenarioA.pageEl).height());
			}
			
			var maxY = windowHeight - maxHeight - padding + zenario.scrollTop();
			if (y > maxY) {
				y = maxY;
			}
		}
		
		wrapper.style.bottom = null;
		wrapper.style.top = y + 'px';
		wrapper.style.position = 'absolute';
	
	} else {
		if (top < 1) {
			top = 0;
		} else if (top > 100) {
			top = 100;
		}
		
		if (zenario.browserIsIE(6)) {
			wrapper.style.position = 'absolute';
			
			var className = wrapper.className;
			className = className.replace(/zenario_fbIE6Hack\d+/, '');
			className += ' zenario_fbIE6Hack' + (top < 4? top : 5 * Math.round(top / 5));
			
			wrapper.className = className;
			
		} else {
			wrapper.style.position = 'fixed';
			
			if (top <= 50) {
				wrapper.style.bottom = null;
				wrapper.style.top = top + '%';
			} else {
				wrapper.style.top = null;
				wrapper.style.bottom = (100 - top) + '%';
			}
		}
	}
	
	if (width) {
		if (e) {
			var x;
			if (e.clientX !== undefined || e.pageX !== undefined) {
				x = zenario.getMouseX(e) + top;
			} else {
				x = $(e).offset().left + left;
				
				if (rightCornerOfElement) {
					x += $(e).width();
				}
			}
			
			//Check the box is not off the screen, and move it back if so
			var minX = 1 + zenario.scrollLeft();
			if (x < minX) {
				x = minX;
			} else {
				var maxX = $(window).width() - width - padding + zenario.scrollLeft();
				if (x > maxX) {
					x = maxX;
				}
			}
			
			wrapper.style.right = null;
			wrapper.style.marginRight = 0;
			wrapper.style.marginLeft = 0;
			wrapper.style.left = x + 'px';
		} else {
			if (left < 1) {
				left = 0;
			} else if (left > 100) {
				left = 100;
			}
			
			var space = Math.max(0, $(window).width() - width);
			
			wrapper.style.right = null;
			wrapper.style.left = Math.round(space * left / 100) + 'px';
		}
	}
};

zenarioA.checkIfBoxIsOpen = function(n) {
	return $('#zenario_fb' + n).is(':visible');
};

zenarioA.closeBox = function(n, keepHTML, options) {
	if (!n) {
		n = 1;
	}
	
	if (keepHTML) {
		$('#zenario_fb' + n).hide(options);
	} else {
		$('#zenario_fb' + n).remove();
	}
	
	$('#zenario_fb' + n + '__overlay').remove();
	$('body').removeClass('zenario_fb' + n + '__isOpen');
	
	zenario.enableScrolling(n);
	
	if (zenarioA.checkIfBoxIsOpen(n)) {
		document.body.focus();
	}
	
	return false;
};

zenarioA.closeBoxHandler = function(box) {
	if (box.w) {
		delete zenarioA.boxesOpen[box.w.attr('id')];
		box.w.hide();
	}
	
	if (box.o) {
		box.o.remove();
	}
	
	foreach (zenarioA.boxesOpen as var n) {
		return;
	}
	
	setTimeout(
		function() {
			zenarioA.floatingBoxOpen = false;
			foreach (zenarioA.boxesOpen as var n) {
				zenarioA.floatingBoxOpen = true;
			}
		}, 100);
};

//Fix a bug where the directory path does not wrap in Webkit/IE
zenarioA.forcePathWrap = function(html, pattern, replacement) {
	
	if (zenario.browserIsFirefox() && pattern === undefined) {
		return html;
	
	} else {
		if (pattern === undefined) {
			pattern = /\//g;
		}
		if (replacement === undefined) {
			replacement = '/';
		}
		
		return html.replace(pattern, replacement + _$html('span', 'style', 'font-size: 1px;', ' '));
	}
};






zenarioA.generateRandomString = function(length) {
	var chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
		charsLength = chars.length,
		string = '',
		i = 0;
	
	for (i = 0; i < length; ++i) { 
		string += chars.charAt(Math.floor(Math.random() * charsLength));
	}
	
	return string;
};


zenarioA.loggedOut = function(message) {
	if (message.substr(0, 17) == '<!--Logged_Out-->') {
		zenarioA.showMessage(message);
		return true;
	} else {
		return false;
	}
};

//If the admin has been logged out, check to see whether this window is in an iframe, and show the login window in the iframe if possible.
zenarioA.loggedOutIframeCheck = function(message, messageType) {
	var parent = (self && self != self.parent && self.parent);
	
	try {
		if (parent
		 && parent.zenarioA
		 && parent.zenarioA.showMessage) {
			parent.zenarioA.closeBox('AdminOrganizer');
			parent.zenarioA.showMessage(message, true, messageType);
			
			return true;
		}
	} catch (e) {
		return false;
	}
	
	return false;
};

zenarioA.floatingBox = function(message, buttonsHTML, messageType, modal, htmlEscapeMessage, onOkay) {
	var defaultModalValue = false,
		html,
		m;
	
	
	if (htmlEscapeMessage) {
		message = htmlspecialchars(message, true);
	}
	
	if (buttonsHTML === true) {
		buttonsHTML = _$input(__$type, __$button, __$class, __$submit, __$value, phrase.OK);
	
	} else if (buttonsHTML && buttonsHTML.indexOf('<input ') === -1) {
		buttonsHTML =
			_$input(__$class, 'submit_selected', __$type, __$button, __$value, buttonsHTML) +
			_$input(__$type, __$button, __$class, __$submit, __$value, phrase.cancel);
	}
	
	if (messageType == 'success' || messageType == 4) {
		messageType = 'zenario_fbSuccess';
	
	} else if (messageType == 'question' || messageType == 3) {
		messageType = 'zenario_fbQuestion';
	
	} else if (messageType == 'error' || messageType == 2) {
		messageType = 'zenario_fbError';
	
	} else if (messageType && messageType != 'none') {
		messageType = 'zenario_fbWarning';
		defaultModalValue = true;
	
	} else {
		messageType = '';
	}
	
	if (modal === undefined) {
		modal = defaultModalValue;
	}
	
	
	m = {
		message: message,
		messageType: messageType,
		buttonsHTML: buttonsHTML
	};
	
	html = zenarioA.microTemplate('zenario_popout_message', m);
	
	zenarioA.onCloseFloatingBox = false;
	zenarioA.openBox(html, 'zenario_fbAdmin zenario_prompt', 'AdminMessage', undefined, 550, 50, 17, modal, true, false, false);
	
	zenario.addJQueryElements('#zenario_fbAdminMessage ', true);
	
	
	if (onOkay) {
		var $button = $('#zenario_fbMessageButtons .submit_selected');
		
		if (!$button.length) {
			$button = $('#zenario_fbMessageButtons .submit');
		}
		
		$button.click(
			function() {
				setTimeout(onOkay, 1);
			}
		);
	}
};

zenarioA.onCloseFloatingBox = false;
zenarioA.currentlyClosingFloatingBox = false;
zenarioA.closeFloatingBox = function(stopBoxClosingTwice) {
	if (zenarioA.currentlyClosingFloatingBox) {
		zenarioA.currentlyClosingFloatingBox = false;
	} else {
		if (zenarioA.checkIfBoxIsOpen('AdminMessage')) {
			zenarioA.closeBox('AdminMessage');
			
			if (zenarioA.onCloseFloatingBox) {
				zenarioA.onCloseFloatingBox();
				zenarioA.onCloseFloatingBox = false;
			}
			
			if (stopBoxClosingTwice) {
				zenarioA.currentlyClosingFloatingBox = true;
			}
		}
	}
};



//Add jQuery elements automatically by class name
zenarioA.addJQueryElements = function(path) {
	
	
	//jQuery datepickers (Admin mode version)
	$(path + 'input.zenario_datepicker').each(function(i, el) {
		if (el.id && zenarioA.siteSettings && get('_value_for__' + el.id)) {
			var changeMonthAndYear = $(el).hasClass('zenario_datepicker_change_month_and_year');
			$(el).datepicker({
				changeMonth: changeMonthAndYear,
				changeYear: changeMonthAndYear,
				dateFormat: zenarioA.siteSettings.organizer_date_format,
				altField: '#_value_for__' + el.id,
				altFormat: 'yy-mm-dd',
				showOn: 'focus',
				onSelect: function(dateText, inst) {
					$(el).change();
					//zenarioAB.fieldChange(this.name);
				}
			});
		}
	});
	
	//Admin mode tooltips
	zenarioA.tooltips(path + 'span[title]');
	zenarioA.tooltips(path + '.pluginAdminMenuButton[title]', {position: {my: 'left top+2', at: 'left bottom', collision: 'flipfit'}});
};

zenarioA.tooltips = function(target, options) {
	if (!options) {
		options = {};
	}
	
	if (options.tooltipClass === undefined) {
		options.tooltipClass = 'zenario_admin_tooltip';
		//N.b. this is deprecated and will need to be changed to
			//options.classes = {"ui-tooltip": "zenario_admin_tooltip"};
		//at some point!
	}
	
	//Disable speach-assistance for admin mode tooltips.
	if (options.disableAriaLiveRegions === undefined) {
		options.disableAriaLiveRegions = true;
	}
	
	zenario.tooltips(target, options);
};

zenarioA.setTooltipIfTooLarge = function(target, title, sizeThreshold) {
	
	$(target).each(function(i, el) {
		var tooltip;
		
		if (title === undefined) {
			tooltip = el.innerHTML;
		} else {
			tooltip = title;
		}
		
		try {
			$(el).jQueryTooltip('destroy');
		} catch (e) {
		}
		
		if (tooltip.replace(/\&\w+;/g, '-').length > sizeThreshold) {
			zenarioA.tooltips(el, {content: tooltip, items: '*'});
		} else {
			$(el).attr('title', '');
		}
	});
};



//Functions for TinyMCE


zenarioA.tinyMCEPasteRreprocess = function(pl, o) {
	o.content = o.content.replace(
		/<\/?font\b[^>]*?>/gi, '').replace(
		/<b\b[^>]*?>/gi, '<strong>').replace(
		/<\/b>/gi, '</strong>').replace(
		/<i\b[^>]*?>/gi, '<em>').replace(
		/<\/i>/gi, '</em>').replace(
		/<u\b[^>]*?>/gi, '<u>').replace(
		/<\/u>/gi, '</u>').replace(
		/<u>\s*<p\b[^>]*?>/gi, '<p><u>').replace(
		/<\/p>\s*<\/u>/gi, '</u></p>').replace(
		/<em>\s*<p\b[^>]*?>/gi, '<p><em>').replace(
		/<\/p>\s*<\/em>/gi, '</em></p>').replace(
		/<strong>\s*<p\b[^>]*?>/gi, '<p><strong>').replace(
		/<\/p>\s*<\/strong>/gi, '</strong></p>').replace(
		/<u\b[^>]*?>/gi, _$span(__$style, 'text-decoration: underline;', '>')).replace(
		/<\/u>/gi, '</span>');
};

//Enable an Admin to upload an image or an animation by draging and dropping it onto the WYSIWYG Editor
//The file will be uploaded using a call to the handleOrganizerPanelAJAX() function of the Common Features Module
zenarioA.enableDragDropUploadInTinyMCE = function(enableImages, prefix, el) {
	
	if (typeof el == 'string') {
		el = get(el);
	}
	
	if (el) {
		zenarioA.disableFileDragDrop(el);
		
		if (enableImages && zenarioA.canDoHTML5Upload()) {
			var url = URLBasePath + 'zenario/ajax.php',
				request = {
					method_call: 'handleOrganizerPanelAJAX',
					__pluginClassName__: 'zenario_common_features',
					__path__: 'editor_temp_file',
					upload: 1};
			
			zenarioA.setHTML5UploadFromDragDrop(
				url,
				request,
				false,
				function() {
					zenarioA.addMediaToTinyMCE(prefix);
				},
				el);
		}
	}
};

//Add a file or files uploaded above into the editor
zenarioA.addMediaToTinyMCE = function(prefix) {
	var files,
		html = '';
	
	if (files = zenario.nonAsyncAJAX(URLBasePath + 'zenario/ajax.php?method_call=getNewEditorTempFiles', true, true)) {
		foreach (files as var f => var file) {
			if (file && file.checksum && file.filename) {
				
				var url = prefix + 'zenario/file.php?c=' + (file.short_checksum || file.checksum) + '&filename=' + encodeURIComponent(file.filename);
				
				html += '\n' + _$html('img', 'src', url, 'alt', file.filename, 'width', file.width, 'height', file.height);
			}
		}
	}
	tinyMCE.execCommand('mceInsertContent', false, html);
};


//This function will open Organizer if the user clicks on one of the "file browser" buttons in tinyMCE
zenarioA.fileBrowser = function(field_name, url, type, win) {
	
	//If this is a field in a FAB, try to load the definition of the field
	var editorId =
			window.tinyMCE
		 && tinyMCE.activeEditor
		 && tinyMCE.activeEditor.id,
		fabField =
			editorId
		 && zenarioAB.tuix
		 && zenarioAB.tuix.tab
		 && zenarioAB.tuix.tabs
		 && zenarioAB.tuix.tabs[zenarioAB.tuix.tab]
		 && zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields
		 && zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[editorId];
	
	//Remember the open window, the name of the file browser's URL field (this will be something like "mceu_48-inp),
	//and whether we found the FAB field above or not.
	zenarioA.tinyMCE_win = win;
	zenarioA.tinyMCE_field = field_name;
	zenarioA.tinyMCE_fromFAB = !!fabField;
	
	//Links to content items. Open the zenario__content/panels/content panel by default,
	//but if this is a field in a FAB then allow this to be overridden
	if (type == 'file') {
		
		if (fabField
		 && fabField.insert_link_button
		 && fabField.insert_link_button.pick_items) {
			pick_items = fabField.insert_link_button.pick_items;
		} else {
			pick_items = {
				path: 'zenario__content/panels/content',
				target_path: 'zenario__content/panels/content',
				min_path: 'zenario__content/panels/content',
				max_path: 'zenario__content/panels/content',
				disallow_refiners_looping_on_min_path: false};
		}

		zenarioA.organizerSelect('zenarioA', 'setLinkURL', false,
						pick_items.path,
						pick_items.target_path,
						pick_items.min_path,
						pick_items.max_path,
						pick_items.disallow_refiners_looping_on_min_path,
						undefined,
						pick_items.one_to_one_choose_phrase,
						undefined,
						true);
	
	//Insert an image.
	//As with links, allow FAB fields to override the destination.
	//Otherwise, if this if for a WYSIWYG Editor, how the content item's images.
	//Otherwise, show the image library by default.
	} else if (type == 'image') {
		
		if (fabField
		 && fabField.insert_link_button
		 && fabField.insert_link_button.pick_items) {
			pick_items = fabField.insert_link_button.pick_items;
		
		} else
		if (!fabField
		 && zenario.cID
		 && zenario.cType
		 && window.zenario_wysiwyg_editor
		 && zenario_wysiwyg_editor.poking) {
			pick_items = {
				path: 'zenario__content/panels/content/item_buttons/images//' + zenario.cType + '_' + zenario.cID + '//',
				target_path: 'zenario__content/panels/inline_images_for_content',
				min_path: 'zenario__content/panels/inline_images_for_content',
				max_path: 'zenario__content/panels/inline_images_for_content',
				disallow_refiners_looping_on_min_path: false};
		
		} else {
			pick_items = {
				path: 'zenario__content/panels/image_library',
				target_path: 'zenario__content/panels/image_library',
				min_path: 'zenario__content/panels/image_library',
				max_path: false,
				disallow_refiners_looping_on_min_path: false};
		}

		zenarioA.organizerSelect('zenarioA', 'setImageURL', false,
						pick_items.path,
						pick_items.target_path,
						pick_items.min_path,
						pick_items.max_path,
						pick_items.disallow_refiners_looping_on_min_path,
						undefined,
						pick_items.one_to_one_choose_phrase,
						undefined,
						true);
	
	//Link to a document (currently the link must be to a public document).
	} else if (type == 'zenario_document') {
		zenarioA.organizerSelect('zenarioA', 'setDocumentURL', false,
						'zenario__content/panels/documents',
						'zenario__content/panels/documents',
						'zenario__content/panels/documents',
						'zenario__content/panels/documents',
						false, undefined, undefined, undefined, true,
						undefined, undefined, undefined, undefined, undefined, undefined, undefined, undefined, undefined,
						{disabled_if: 'item && item.privacy != "public" && item.privacy != "Public"'});
	}
};


//By default there is only one file browser button. But for links, we want two;
//the first should be to content items and the second should be to documents.
//This function hacks about and replaces the single button with two buttons.
zenarioA.setLinkPickerOnTinyMCE = function() {
	var $urlField = $('.mce-zenario_link_picker input.mce-textbox'),
		$picker = $('.mce-zenario_link_picker .mce-open'),
		$newPicker = $(zenarioA.microTemplate('zenario_tinymce_link_picker', {urlFieldId: $urlField.attr('id')})),
		urlFieldWidth = $urlField.width(),
		pickerWidth = $picker.width(),
		newPickerWidth;
	
	$picker.replaceWith($newPicker);
	
	newPickerWidth = $newPicker.width();
	
	$urlField.width(urlFieldWidth + pickerWidth - newPickerWidth);
	
	zenarioA.tooltips(
		$newPicker.find(__$button),
		{tooltipClass: 'zenario_admin_tooltip zenario_admin_tooltip_over_tinymce'}
	);
};


//This function sets the value of one of the fields in the TinyMCE forms.
//It's used after picking something from the file browser
zenarioA.lastFieldValue = '';
zenarioA.setEditorField = function(value, el, onlyIfEmpty) {
	if (el === undefined) {
		el = (zenarioA.tinyMCE_win || window).document.getElementById(zenarioA.tinyMCE_field);
	}
	
	if (onlyIfEmpty) {
		if (el.value !== '' && el.value != zenarioA.lastFieldValue) {
			return;
		}
		zenarioA.lastFieldValue = value;
	}
	
	el.value = value;
	zenario.fireChangeEvent(el);
};

//This handles the return results of the file browser for a link to a content item
zenarioA.setLinkURL = function(path, key, row) {
	
	//Get the URL via an AJAX program
	key.getItemURL = 1;
	var URL = zenario.moduleNonAsyncAJAX('zenario_common_features', key, true);
	
	if (zenarioA.loggedOut(URL)) {
		return;
	}
	
	//For admin boxes, make sure the full URL is used as a workaround for any relative path problems
	//The stripAbsURLsFromAdminBoxField() function can be used later to strip these off if this is not desirable.
	if (zenarioA.tinyMCE_fromFAB
	 && URL.indexOf('://') === -1) {
		URL = URLBasePath + URL;
	}
	
	zenarioA.setEditorField(row.title, $('.mce-panel input.mce-link_text_to_display')[0], true);
	zenarioA.setEditorField(URL);
};

//This handles the return results of the file browser for a link to a public document
zenarioA.setDocumentURL = function(path, key, row) {
	var documentURL = row.frontend_link;
	
	if (zenarioA.tinyMCE_fromFAB) {
		documentURL = URLBasePath + documentURL;
	}
	
	zenarioA.setEditorField(row.name, $('.mce-panel input.mce-link_text_to_display')[0]);
	zenarioA.setEditorField(documentURL);
}

//This handles the return results of the file browser for an image
zenarioA.setImageURL = function(path, key, row) {

	var imageURL = 'zenario/file.php?c=' + (row.short_checksum || row.checksum);
	
	if (key.usage && key.usage != 'image') {
		imageURL += '&usage=' + encodeURIComponent(key.usage);
	}
	
	imageURL += '&filename=' + encodeURIComponent(row.filename);
	
	if (zenarioA.tinyMCE_fromFAB) {
		imageURL = URLBasePath + imageURL;
	}
	
	zenarioA.setEditorField(row.alt_tag, $('.mce-panel input.mce-image_alt')[0]);
	zenarioA.setEditorField(imageURL);
};









zenarioA.skinDesc = undefined;
zenarioA.getSkinDesc = function() {
	if (zenarioA.skinDesc === undefined) {
		zenarioA.skinDesc = {};
		
		var desc;
		if (zenario.skinId
		 && (desc = zenario.moduleNonAsyncAJAX('zenario_common_features', {skinId: zenario.skinId}, false, true))
		 && (typeof desc == 'object')) {
			zenarioA.skinDesc = desc;
		}
	}
	
	return zenarioA.skinDesc;
};


zenarioA.formatFilesizeNicely = function(size, precision) {
	
	//Return 0 without formating if the size is 0.
	if (size <= 0) {
		return '0';
	}
	
	//Define labels to use
	var labels = ['_BYTES', '_KBYTES', '_MBYTES', '_GBYTES', '_TBYTES'];
	
	//Work out which of the labels to use, based on how many powers of 1024 go into the size, and
	//how many labels we have
	var order = Math.min(4, 
					Math.floor(
						Math.log(size) / Math.log(1024)
					));
	
	precision = Math.pow(10, precision);
	
	return (Math.round(precision * size / Math.pow(1024, order)) / precision) + phrase[labels[order]];

};



zenarioA.makeTimeFromParts = function(hours,minutes,seconds) {
	var outputTime;
	
	if (seconds == undefined) {
		seconds = "00";
	}
	
	outputTime = hours + ":" + minutes + ":" + seconds;
	
	return outputTime;
};



zenarioA.showPagePreview = function(width, height, description, id) {
	var id,
		item,
		title = '';
	
	if (zenarioO.tuix
	 && (id = zenarioO.getKeyId(true))
	 && (item = zenarioO.tuix.items[id])) {
		switch (item.admin_version_status) {
			case 'first_draft':
			case 'published_with_draft':
			case 'hidden_with_draft':
			case 'trashed_with_draft':
				adminVersionStatus = 'Draft';
				break;
			case 'published':
				adminVersionStatus = 'Published';
				break;
			case 'hidden':
				adminVersionStatus = 'Hidden';
				break;
			case 'trashed':
				adminVersionStatus = 'Trashed';
				break;
		}
		title = item.tag + ' Version ' + item.version + ' [' + adminVersionStatus + '] '+width+' x '+height+' ('+description+')';
	} else {
		title = description;
		
		if (zenario.cID
		 && zenario.cType) {
			id = zenario.cType + '_' + zenario.cID;
		}
	}
	
	$.colorbox({
		innerWidth: width+'px',
		innerHeight: height+'px',
		maxWidth: false,
		maxHeight: false,
		iframe: true,
		preloading: false,
		open: true,
		title: title,
		href: URLBasePath + 'index.php?cID=' + id + '&_show_page_preview=1',
		className: 'zenario_page_preview_colorbox'
	});
};







/* Organizer launch functions */

//Format the name of an item from Organizer appropriately
zenarioA.formatOrganizerItemName = function(panel, i) {
	var string = undefined,
		string2,
		value;
	
	if (panel.items
	 && panel.items[i]) {
	
		if (string = string2 = panel.label_format_for_picked_items || panel.label_format_for_grid_view) {
			foreach (panel.items[i] as var c) {
				if (string.indexOf('[[' + c + ']]') != -1) {
					value = panel.items[i][c];
					
					if (panel.columns
					 && panel.columns[c]
					 && (panel.columns[c].format || panel.columns[c].empty_value)) {
						value = zenarioA.formatSKItemField(value, panel.columns[c]);
					}
					
					while (string != (string2 = string.replace('[[' + c + ']]', value))) {
						string = string2;
					}
				}
			}
		
		} else {
			string = panel.items[i][panel.default_sort_column || 'name'];
		}
	}
	
	if (string === undefined) {
		return i;
	} else {
		return string.replace(/\s+/g, ' ');
	}
};

zenarioA.formatSKItemField = function(value, column) {

	if (column && column.format) {
		var format = column.format,
			//Most formats allow additional text seperated by a space
			extra = '';
		
		if (value && (format != 'date' && format != 'datetime' && format != 'datetime_with_seconds' && format != 'remove_zero_padding')) {
			var pos = value.indexOf(' ');
			if (pos != -1) {
				extra = value.substr(pos);
				value = value.substr(0, pos);
			}
		}
		
		if (format == 'true_or_false') {
			if (engToBoolean(value)) {
				value = column.true_phrase || phrase.tru;
			} else {
				value = column.false_phrase || phrase.fal;
			}
			
		} else if (format == 'yes_or_no') {
			if (engToBoolean(value)) {
				value = column.yes_phrase || phrase.yes;
			} else {
				value = column.no_phrase || phrase.no;
			}
			
		} else if (format == 'remove_zero_padding') {
			value = value.replace(/\b0+/g, '');
			
		} else if (format == 'enum' && column.values && column.values[value] !== undefined) {
			
			if (typeof column.values[value] == 'object') {
				if (column.values[value].label !== undefined) {
					value = column.values[value].label;
				}
			} else {
				value = column.values[value];
			}
			
		} else if ((format == 'module_name' || format == 'module_class_name') && zenarioO.init) {
			if (!value) {
				value = phrase.core;
			} else if (zenarioA.pluginNames[value]) {
				value = zenarioA.pluginNames[value];
			}
			
		} else if (format == 'filesize' && value == 1*value) {
			value = zenarioA.formatFilesizeNicely(value, 1);
			
		} else if ((format == 'language_english_name' || format == 'language_local_name') && zenarioA.lang[value]) {
			value = zenarioA.lang[value].name;
			
		} else if ((format == 'language_english_name_with_id' || format == 'language_local_name_with_id') && zenarioA.lang[value]) {
			value = zenarioA.lang[value].name + ' (' + value + ')';
			
		} else if (format == 'date' || format == 'datetime' || format == 'datetime_with_seconds') {
			value = zenario.formatDate(value, format == 'date'? false : format);
		}
		
		value += extra;
	}
	
	if ((!value || value === '0') && column && column.empty_value) {
		value = column.empty_value;
	}
	
	return value;
};

//Open Organizer in quick mode
zenarioA.organizerQuick = function(path, targetPath, minPath, maxPath, disallowRefinersLoopingOnMinPath, slotName, instanceId, reloadOnChanges, wrapperCSSClass) {
	
	zenarioA.organizerSelect(
		false, false, false,
		path, false, minPath, maxPath, disallowRefinersLoopingOnMinPath,
		false, false, false,
		false, false,
		true, targetPath, instanceId,
		undefined,
		undefined, undefined,
		reloadOnChanges, wrapperCSSClass);
};

//Get the correct CSS class name to put around Organizer
zenarioA.getSKBodyClass = function(win, panelType) {
	if (win === undefined) {
		win = window;
	}
	
	return 'zenario_og ' + (
		win.zenarioONotFull || win.zenarioA.openedInIframe?
			(
				(win.zenarioOQuickMode? 'zenario_organizer_quick' : 'zenario_organizer_select') +
				' ' + 
				(win.zenarioOOpenOverAdminBox? 'zenario_organizer_over_admin_box' : 'zenario_organizer_under_admin_box')
			)
		  : 'zenario_organizer_full'
	) +
	' ' +
	(win.zenarioOWrapperCSSClass || '') +
	' ' +
	(panelType? 'zenario_panel_type__' + panelType: '');
};

//Open Organizer in select mode
zenarioA.organizerSelect = function(
	callbackObject, callbackFunction, enableMultipleSelect,
	path, targetPath, minPath, maxPath, disallowRefinersLoopingOnMinPath,
	chooseButtonActiveClass, choosePhrase, chooseMultiplePhrase,
	tinyMCE,
	openOverAdminBox,
	skQuick, openingPath, openingInstance,
	combineItem,
	allowNoSelection, noSelectionChoosePhrase,
	reloadOnChanges, wrapperCSSClass,
	object
) {
	
	var win,
		useIframe = !skQuick || zenarioA.isFullOrganizerWindow || zenarioA.checkIfBoxIsOpen('og');
	
	if (!object) {
		object = {};
	}
	
	if (!useIframe) {
		win = window;
	
	} else {
		//If we've already got Organizer open, we'll need to load this one new in an iFrame
		
		//Initialise it if it's not been preloaded yet
		zenarioA.SKInit();
		
		win = get('zenario_sk_iframe').contentWindow;
		
		//The "openOverAdminBox" variable should be false if we're opening an iframe,
		//as this new Organizer will be the first on the page and won't need to open over anything.
		openOverAdminBox = false;
	}
	
	//Close the tooltip if it is open
	zenario.closeTooltip();
	
	var overlayOpacity;
	if (skQuick) {
		overlayOpacity = 0;
	} else if (tinyMCE) {
		overlayOpacity = 10;
	} else if (!zenarioA.openedInIframe) {
		overlayOpacity = 75;
	} else {
		overlayOpacity = 35;
	}

	win.zenarioOQueue = [{path: path, branch: -1, selectedItems: {}}];
	
	//minPath and targetPath should default to path if not set
	if (!minPath) {
		minPath = path;
	}
	if (!targetPath) {
		targetPath = path;
	}
	
	//Max path should default to the target path if not set
	if (maxPath === undefined) {
		if (targetPath) {
			maxPath = targetPath;
		}
		
	//The max path variable can be set to false to turn those features off.
	//Convert strings such as "No" or "False" to false.
	} else if (!engToBoolean(maxPath)) {
		maxPath = false;
	}
	
	
	win.zenarioOTargetPath = targetPath;
	win.zenarioOMinPath = minPath;
	win.zenarioOMaxPath = maxPath;
	win.zenarioOCheckPaths = true;
	win.zenarioODisallowRefinersLoopingOnMinPath = engToBoolean(disallowRefinersLoopingOnMinPath);
	
	win.zenarioOCallbackObject = callbackObject;
	win.zenarioOCallbackFunction = callbackFunction;
	win.zenarioOChoosePhrase = choosePhrase;
	win.zenarioOChooseButtonActiveClass = chooseButtonActiveClass;
	win.zenarioOChooseMultiplePhrase = chooseMultiplePhrase;
	win.zenarioOMultipleSelect = engToBoolean(enableMultipleSelect);
	win.zenarioOAllowNoSelection = engToBoolean(allowNoSelection);
	win.zenarioONoSelectionChoosePhrase = noSelectionChoosePhrase;
	
	win.zenarioONotFull = true;
	win.zenarioOQuickMode = skQuick;
	win.zenarioOSelectMode = !skQuick;
	win.zenarioOSelectObject = object;
	
	win.zenarioOOpeningPath = openingPath;
	win.zenarioOOpeningInstance = openingInstance;
	win.zenarioOCombineItem = combineItem;
	win.zenarioOReloadOnChanges = reloadOnChanges;
	win.zenarioOWrapperCSSClass = wrapperCSSClass || '';
	win.zenarioOOpenOverAdminBox = openOverAdminBox;
	win.zenarioOFirstLoad = true;
	
	if (!useIframe) {
		//If we've not currently got an existing full-Organizer instance in this frame, set Organizer up in a <div>
		
		zenarioO.open(zenarioA.getSKBodyClass(win), undefined, $(window).width() * 0.8, 50, 10, true, true, true, {minWidth: 550});
		//zenarioO.open(className, e, width, left, top, disablePageBelow, overlay, draggable, resizable, padding, maxHeight, rightCornerOfElement, bottomCornerOfElement) {
		
		zenarioO.init();
	}
	
	//If Organizer has been already pre-loaded, we can use the navigation functions to go to the correct path
	if (win.zenarioO) {
		win.zenarioO.go(path, -1);
	}
	//Otherwise store the requested path in zenarioOGoToPathWhenLoaded and wait for the it to catch up
		
	if (useIframe) {
		//Show the Organizer window
		zenarioA.openBox(false, zenarioA.getSKBodyClass(win), 'AdminOrganizer', false, false, 50, 2, true, true, false, false);
		//zenarioA.openBox(html, className, 'og', e, width, left, top, disablePageBelow, overlay, draggable, resizable, padding, maxHeight, rightCornerOfElement, bottomCornerOfElement);
		
		get('zenario_sk_iframe').style.width = zenario.browserIsIE(6)? '600px' : '96%';
		if (skQuick) {
			get('zenario_sk_iframe').style.height = '100%';
		} else {
			get('zenario_sk_iframe').style.height = '96%';
		}
		
		if (get('zenario_sk_iframe').contentWindow.zenarioO) {
			get('zenario_sk_iframe').contentWindow.zenarioO.lastSize = false;
		}
		
		get('zenario_fbAdminOrganizer').style.left = '0px';
	}
};

zenarioA.SKInitted = false;
zenarioA.SKInit = function() {
	if (zenarioA.SKInitted) {
		return;
	}
	
	get('zenario_fbAdminOrganizer').innerHTML =
		_$html('iframe', 'id', 'zenario_sk_iframe', 'src', URLBasePath + 'zenario/admin/organizer.php?openedInIframe=1&rand=' + (new Date).getTime());
	
	zenarioA.SKInitted = true;
};



zenarioA.multipleLanguagesEnabled = function() {
	var lang,
		langs = 0;
	
	if (zenarioA.lang) {
		foreach (zenarioA.lang as lang) {
			if (zenarioA.lang[lang].enabled) {
				if (langs++) {
					return true;
				}
			}
		}
	}
	
	return false;
};


//Functions for enabling HTML 5 file-uploads in WYSIWYG Editors and in Organizer Panels

zenarioA.uploading = false;
zenarioA.canDoHTML5Upload = function() {
	return window.FileReader;
};

zenarioA.clearHTML5UploadFromDragDrop = function() {
	delete zenarioA.uploadPathFromDragDrop;
	delete zenarioA.uploadRequestFromDragDrop;
	delete zenarioA.uploadCallBackFromDragDrop;
};

zenarioA.setHTML5UploadFromDragDrop = function(path, request, preCall, callBack, el) {
	if (!zenarioA.canDoHTML5Upload()) {
		return false;
	
	} else {
		zenarioA.uploadPathFromDragDrop = path;
		zenarioA.uploadRequestFromDragDrop = request;
		zenarioA.uploadCallBackFromDragDrop = callBack;
		
		(el || document.body).addEventListener(
			'drop',
			function(e) {
				if (preCall) {
					preCall();
				}
				zenarioA.doHTML5UploadFromDragDrop(e);
			},
			false);
		
		return true;
	}
};

zenarioA.doHTML5UploadFromDragDrop = function(e) {
	zenarioA.stopFileDragDrop(e);
	zenarioA.doHTML5Upload(
		e.target.files || e.dataTransfer.files,
		zenarioA.uploadPathFromDragDrop,
		zenarioA.uploadRequestFromDragDrop,
		zenarioA.uploadCallBackFromDragDrop);
};

zenarioA.doHTML5Upload = function(files, path, request, callBack) {
	if (!path || !request || zenarioA.uploading) {
		return;
	}
	
	zenarioA.uploadPath = path;
	zenarioA.uploadRequest = request;
	zenarioA.uploadCallBack = callBack;
	zenarioA.uploadResponses = [];
	
	zenarioA.uploadFile = -1;
	zenarioA.uploadFiles = files;
	zenarioA.doNextUpload();
};

zenarioA.doNextUpload = function() {
	
	var $zenario_progress_wrap = $('#zenario_progress_wrap'),
		$zenario_progress_name = $('#zenario_progress_name'),
		$zenario_progress_stop = $('#zenario_progress_stop');
	
	$zenario_progress_name.text('');
	$zenario_progress_stop.unbind('click');
	
	zenarioA.uploading = !!zenarioA.uploadFiles[++zenarioA.uploadFile];
	
	if (zenarioO.init) {
		zenarioO.setWrapperClass('uploading', zenarioA.uploading);
	}
	
	if (zenarioA.uploading) {
		
		$zenario_progress_wrap.show().removeClass('zenario_progress_cancelled');
		
		get('zenario_progressbar').style.width = '0%';
		
		if (!zenarioA.uploadFiles[zenarioA.uploadFile].size || !zenarioA.uploadFiles[zenarioA.uploadFile].name) {
			zenarioA.doNextUpload();
		
		} else if (zenarioA.uploadFiles[zenarioA.uploadFile].size > zenarioA.maxUpload) {
			zenarioA.showMessage(phrase.uploadTooLarge.replace('[[maxUploadF]]', zenarioA.maxUploadF), true, 'error');
			zenarioA.doNextUpload();
		
		} else {
			$zenario_progress_name.text(zenarioA.uploadFiles[zenarioA.uploadFile].name);
			
			zenarioA.uploader = new XMLHttpRequest();  
			zenarioA.uploader.open('POST', zenarioA.uploadPath, true);
			
			try {
				//Try to add a header for the filename
				zenarioA.uploader.setRequestHeader('X_FILENAME', zenarioA.uploadFiles[zenarioA.uploadFile].name);
			} catch (e) {
				//Don't worry if it coudln't be added, it's optional
			}
			
			var data = new FormData();
			foreach (zenarioA.uploadRequest as var k) {
				data.append(k, '' + zenarioA.uploadRequest[k]);
			}
			
			data.append('Filedata', zenarioA.uploadFiles[zenarioA.uploadFile]);
			
			zenarioA.uploader.upload.addEventListener('progress', zenarioA.uploadProgress, false);
			
			zenarioA.uploader.addEventListener('load', zenarioA.uploadDone, false);
			zenarioA.uploader.addEventListener('error', zenarioA.uploadDone, false);
			zenarioA.uploader.addEventListener('abort', zenarioA.uploadDone, false);
			
			$zenario_progress_stop.click(function() {
				$zenario_progress_wrap.show().removeClass('zenario_progress_cancelled');
				zenarioA.uploader.abort();
			});
			
			zenarioA.uploader.send(data);
		}
	
	} else {
		$zenario_progress_wrap.hide();
		
		if (zenarioA.uploadCallBack) {
			zenarioA.uploadCallBack(zenarioA.uploadResponses);
		}
	}
};

zenarioA.uploadProgress = function(e) {
	var completion = 0;
	if (e.lengthComputable) {  
		completion = 100 * e.loaded / e.total;  
	}
	
	get('zenario_progress_wrap').style.display = 'block';
	get('zenario_progressbar').style.width = completion + '%';
};

zenarioA.uploadDone = function(e) {
	
	if (zenarioA.uploader.responseText && zenarioA.uploader.responseText != 1) {
		try {
			data = JSON.parse(zenarioA.uploader.responseText);
		
			if (typeof data != 'object') {
				throw 0;
			}
			
			zenarioA.uploadResponses.push(data);
		
		} catch (e) {
			zenarioA.showMessage(zenarioA.uploader.responseText, true, 'error', false, true);
		}
	}
	
	zenarioA.doNextUpload();
};



zenarioA.debug = function(mode, orgMap) {
	window.open(URLBasePath + 'zenario/admin/dev_tools/dev_tools.php?mode=' + encodeURIComponent(mode) + (orgMap? '&orgMap=1' : '')); return false;
};

//Functionality for clicking on Menu Nodes. They should:
	//Follow their hyperlinks in preview mode
	//Open a FAB in menu mode
	//Do nothing otherwise
zenarioA.openMenuAdminBox = function(key, openSpecificBox) {
	if (!key) {
		key = {};
	}
	
	if (openSpecificBox) {
		//continue
	
	} else if (zenarioA.pageMode == 'preview') {
		return true;
	
	} else if (zenarioA.pageMode == 'menu' && zenarioA.menuWandOn) {
		openSpecificBox = 'zenario_menu_text';
	
	} else {
		return false;
	}
	
	if (zenario.cID) {
		key.cID = zenario.cID;
		key.cType = zenario.cType;
		key.languageId = zenario.langId;
	}
	
	if (openSpecificBox == 'organizer') {
		//Open an existing Menu Item in Organizer Quick
		var path = zenario.moduleNonAsyncAJAX('zenario_common_features', {getMenuItemStorekeeperDeepLink: key.id, languageId: key.languageId}, true);
		
		if (zenarioA.loggedOut(path)) {
			return;
		}
		
		var object = {
			organizer_quick: {
				path: path,
				target_path: 'zenario__menu/panels/menu_nodes',
				min_path: 'zenario__menu/panels/menu_nodes',
				max_path: 'zenario__menu/panels/menu_nodes',
				disallow_refiners_looping_on_min_path: false,
				reload_menu_slots: true,
				reload_admin_toolbar: true}};
		
		zenarioAT.action(object);
		
	} else {
		//Otherwise open an Admin Box
		zenarioAB.open(openSpecificBox, key);
	}
	
	return false;
};

zenarioA.reloadMenuPlugins = function() {
	$('.zenario_slotShownInMenuMode .zenario_slot').each(function(i, el) {
		if (el.id && el.id.substr(0, 7) == 'plgslt_') {
			var slotName = el.id.substr(7);
			
			//zenario.refreshPluginSlot = function(slotName, instanceId, additionalRequests, recordInURL, scrollToTopOfSlot, fadeOutAndIn, useCache, post) {
			zenario.refreshPluginSlot(slotName, 'lookup', zenarioA.importantGetRequests, false, false, false, false, false);
		}
	});
};



//If there is an entry (e.g. "Edit Content") in the actions dropdown that needs to be on a draft,
//this function will create a draft of a published page (after a confirm prompt),
//reload the page, then click the entry again.
zenarioA.draft = function(aId, justView, confirmMessage, confirmButtonText) {
	
	if (zenarioA.draftDoingCallback) {
		delete zenarioA.draftDoingCallback;
		return true;
	
	} else {
		delete zenarioA.draftDoingCallback;
	}
	
	var tuix, section, button, buttonId = 'start_editing',
		buttonsHTML,
		object;
	
	//Look for the "create a draft" button on the admin toolbar
	//If we see it, we know this is a published item and we need to create a draft
	if ((tuix = zenarioAT.tuix)
	 && (section = tuix.sections && tuix.sections.edit)
	 && (button = section.buttons && section.buttons[buttonId])
	 && (button.ajax
		 //zenarioA.hidden(tuixObject, lib, item, id, button, column, field, section, tab, tuix)
	 && !zenarioA.hidden(undefined, zenarioAT, undefined, buttonId, button, undefined, undefined, section))) {
		
		//Create a copy of it
		object = zenario.clone(button);
		
		delete object.ajax.request.switch_to_edit_mode;
		
		//Should we show someone a warning before creating a draft?
		if (zenarioA.checkSpecificPermsOnThisPage() && button.ajax.confirm) {
			
			//If so, show a confirmation box with up to three options:
			if (confirmMessage) {
				confirmMessage += '\n\n' + object.ajax.confirm.message__editing_published;
			} else {
				confirmMessage = object.ajax.confirm.message__editing_published;
				confirmButtonText = object.ajax.confirm.button_message;
			}
			
			//1. Create the draft, then when the draft has been created press this option again
			buttonsHTML =
				_$input(__$type, __$button, __$class, 'submit_selected', __$value, confirmButtonText, __$onclick, 'zenarioA.draftSetCallback("' + jsEscape(aId) + '"); zenarioAT.action2();');
			
			//2. Don't create a draft, press this option again and just view in read-only mode
			if (justView) {
				buttonsHTML +=
					_$input(__$type, __$button, __$class, __$submit, __$value, object.ajax.confirm.button_message__just_view, __$onclick, 'zenarioA.draftDoCallback("' + jsEscape(aId) + '");');
			}
			
			//3. Cancel
			buttonsHTML +=
				_$input(__$type, __$button, __$class, __$submit, __$value, object.ajax.confirm.cancel_button_message);
			
			object.ajax.confirm.message = '<!--Button_HTML:' + buttonsHTML + '-->' + confirmMessage;
		
		//Handle the case where we wouldn't normally show a warning before creating a draft,
		//but there was still a confirm message to show
		} else if (confirmMessage) {
			//Note down which button was clicked on.
			//This button will be automatically clicked again after the page is reloaded
			zenarioA.draftSetCallback(aId);
		
			//Remove set a confirm prompt
			object.ajax.confirm = {
				message: confirmMessage,
				button_message: confirmButtonText,
				cancel_button_message: phrase.cancel,
				message_type: 'warning'
			};
			
		
		//If not, create the draft straight away
		} else {
			//Note down which button was clicked on.
			//This button will be automatically clicked again after the page is reloaded
			zenarioA.draftSetCallback(aId);
			
			//Remove any confirm prompt on the button
			delete object.ajax.confirm;
		}
		
		//"Press" the copy of the button we just made
		zenarioA.action(zenarioAT, object);
		return false;
	
	
	//Handle the case where we're already on a draft, but there was still a confirm message to show
	} else if (confirmMessage) {
		buttonsHTML =
			_$input(__$type, __$button, __$class, 'submit_selected', __$value, confirmButtonText, __$onclick, 'zenarioA.draftDoCallback("' + jsEscape(aId) + '");');
		
		zenarioA.showMessage(confirmMessage, buttonsHTML, 'warning', undefined, undefined, true);

		return false;
	}
	
	return true;
};

zenarioA.savePageMode = function(async, data) {
	if (!data) {
		data = {};
	}
	data._save_page_mode = zenarioA.pageMode;
	data._save_page_toolbar = zenarioA.toolbar;
	data._save_page_slot_wand = zenarioA.slotWandOn? 1 : '';
	data._save_page_show_grid = zenarioA.showGridOn? 1 : '';
	
	$.ajax({
		type: 'POST',
		url: URLBasePath + 'zenario/admin/quick_ajax.php',
		data: data,
		async: async
	});
};

zenarioA.draftSetCallback = function(aId) {
	zenarioA.savePageMode(false, {_draft_set_callback: aId});
};

zenarioA.draftDoCallback = function(aId) {
	zenarioA.draftDoingCallback = true;
	$('#' + aId).click();
	delete zenarioA.draftDoingCallback;
};



//Admin Actions
zenarioA.eval = function(condition, lib, tuixObject, item, id, button, column, field, section, tab, tuix) {
	
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
			 || !lib[methodName].apply(lib, zenarioA.tuixToArray(ev))) {
				return false;
			}
		}
		//If all calls were fine then return true
		return true;
	
	//Otherwise assume this is some code that we need to evaulate
	} else {
		ev = zenarioA.doEval(condition + '', lib, tuixObject, item, id, button, column, field, section, tab, tuix);
		
		//If the eval returned a function, call said function
		if (typeof ev == 'function') {
			ev = ev(tuixObject, item, id, tuix, button, column, field, section, tab);
		}
		
		return zenario.engToBoolean(ev);
	}
};




zenarioA.hidden = function(tuixObject, lib, item, id, button, column, field, section, tab, tuix) {
	tuixObject = tuixObject || button || column || field || item || section || tab;

	return engToBoolean(tuixObject.hidden)
		|| (tuixObject.visible_if && !zenarioA.eval(tuixObject.visible_if, lib, tuixObject, item, id, button, column, field, section, tab, tuix))
		|| (tuixObject.js_condition && !zenarioA.eval(tuixObject.js_condition, lib, tuixObject, item, id, button, column, field, section, tab, tuix));
	
	//N.b. "visible_if" used to be called "js_condition", so the js_condition line above is left for backwards compatability.
	//If you specify both on one field, then both are checked. (This is used in a couple of advanced cases where properties are merged together.)
};


//A shortcut to the toastr library
zenarioA.lastToast = false;
zenarioA.clearLastToast = false;
zenarioA.toast = function(object) {
	if (object !== undefined
	 && _.isObject(object)) {
		
		//Remember this toast that we had for the next 60 seconds,
		//or until another toast comes in
		if (zenarioA.clearLastToast) {
			clearTimeout(zenarioA.clearLastToast);
		}
		zenarioA.lastToast = JSON.stringify(object);
		setTimeout(function () {
			zenarioA.lastToast = false;
		}, 60000);
		
		//Work out what type of toast this is
		var mt = object.message_type,
			toast = toastr.info;
		switch (object.message_type) {
			case 'error':
			case 'warning':
			case 'success':
				toast = toastr[mt];
		}
		
		//display the toast
		toast(object.message, object.title, object.options);
		
		//Reminder to self: the toast function returns a $jQuery element with the toaster,
		//just in case we ever wanted to do something like add a click event...
	}
};

zenarioA.rememberToast = function() {
	//Check if we just displayed a toast. If so, remember it for next time.
	if (zenarioA.lastToast) {
		zenario.nonAsyncAJAX(URLBasePath + 'zenario/admin/quick_ajax.php', {_remember_toast: zenarioA.lastToast});
	}
};




zenarioA.checkActionUnique = function(object) {
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
			__$onclick,
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
zenarioA.action = function(zenarioCallingLibrary, object, itemLevel, branch, link, extraRequests, specificItemRequested, AJAXURL) {
	if (zenarioCallingLibrary.uploading) {
		return false;
	}
	
	//Check to see if there is a unique action on this button
		//(But skip this if we're overriding the functionality with a link)
	if (!link) {
		if (!zenarioA.checkActionUnique(object)) {
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
		var fallback = !zenarioA.canDoHTML5Upload(),
			html = '<input type="file" name="Filedata"',
			e, extension, extensions, split;
		
		if (extensions = object.upload.accept || object.upload.extensions) {
			extensions = zenarioA.tuixToArray(extensions);
			
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
			$input.change(function() {
				
				if (zenarioCallingLibrary.uploadStart) {
					zenarioCallingLibrary.uploadStart();
				}
				
				zenarioA.doHTML5Upload(this.files, url, requests, function(responses) {
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
		
		if (sameWindow || frontend_link.substr(0, 25) == 'zenario/admin/welcome.php') {
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
			
		if (item && item.href && popout.href === undefined) {
			popout.href = item.href;
		
		} else if (item && item.frontend_link && popout.href === undefined) {
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
		
		if (popout.iframe && popout.width === undefined) {
			popout.width = '93%';
		}
		if (popout.iframe && popout.height === undefined) {
			popout.height = '90%';
		}
		
		if (item && item.width) {
			popout.initialWidth = item.width;
		}
		if (item && item.height) {
			popout.initialHeight = item.height;
		}
		
		if (popout.preloading === undefined) {
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
				
				if (zenarioCallingLibrary.searchTerm !== undefined) {
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
				__$formId = 'zenario_bc_form';
			
			if (isDownload || object.upload) {
				html += 
					_$html('form', 'id', __$formId, 'action', zenarioCallingLibrary.actionTarget + '&_sk_form_submission=1',
						'onsubmit', "get('preloader_circle').style.visibility = 'visible';",
						'target', 'zenario_iframe', 'method', 'post', 'enctype', object.upload && 'multipart/form-data',
					'>');
				
				foreach (zenarioCallingLibrary.actionRequests as r) {
					html += _$input(__$type, 'hidden', __$value, zenarioCallingLibrary.actionRequests[r], 'name', r);
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
					_$input(__$type, __$button, __$class, 'submit_selected', __$value, object.ajax.confirm.button_message, __$onclick, "get('" + __$formId + "').submit();");
			//...otherwise it should launch a syncronous AJAX request.
			} else {
				buttonsHTML =
					_$input(__$type, __$button, __$class, 'submit_selected', __$value, object.ajax.confirm.button_message, __$onclick, zenarioCallingLibrary.globalName + '.action2()');
			}
			
			buttonsHTML +=
				_$input(__$type, __$button, __$class, __$submit, __$value, object.ajax.confirm.cancel_button_message);
			
			
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

zenarioA.isHtaccessWorking = function() {
	return zenario.nonAsyncAJAX(URLBasePath + 'zenario/includes/test_files/is_htaccess_working.txt', true) == 'Yes';
};

zenarioA.checkFunctionExists = function(functionName, globalName) {
	if (globalName) {
		return window[globalName] && typeof window[globalName][functionName] == 'function';
	} else {
		return typeof window[functionName] == 'function';
	}
};


//Given an image size and a target size, resize the image (maintaining aspect ratio).
//This is a copy of the resizeImage() function in cms.inc.php, to ensure consistent logic
//when generating a thumbnail in JavaScript
zenarioA.resizeImage = function(image_width, image_height, constraint_width, constraint_height, out, allowUpscale) {
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




//Utility function for the zenarioXX.sortYYY() series of function
//Given two arrays (each of which represents an element), say which should be first
zenarioA.sortArray = function(a, b) {
	return zenarioA.sortLogic(a, b, 1);
};

zenarioA.sortArrayByOrd = function(a, b) {
	return zenarioA.sortLogic(a, b, 'ord');
};

zenarioA.sortArrayByOrdinal = function(a, b) {
	return zenarioA.sortLogic(a, b, 'ordinal');
};

zenarioA.sortArrayWithGrouping = function(a, b) {
	
	//Both fields are in the same grouping, or neither field is in a grouping.
	if (a[2] === b[2]) {
		//Check their ordinal normally
		return zenarioA.sortLogic(a, b, 1);
	
	//Field a is not in a grouping, but field b is
	} else if (a[2] === undefined) {
		//a's ordinal should be checked against the ordinal of b's grouping
		return zenarioA.sortLogic(a, b, 1, 2);
	
	//Field b is not in a grouping, but field a is
	} else if (b[2] === undefined) {
		//a's ordinal should be checked against the ordinal of b's grouping
		return zenarioA.sortLogic(a, b, 2, 1);
	
	//Both fields are in different groupings
	} else {
		//Compare the ordinal of the groupings against each other
		return zenarioA.sortLogic(a, b, 2);
	}
};

zenarioA.sortLogic = function(a, b, propA, propB) {
	
	var vA = a[propA], vB;
	
	if (propB === undefined) {
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


zenarioA.getSortedIdsOfTUIXElements = function(tuix, toSort, column, desc) {
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
		sortedArray.sort(zenarioA.sortArrayDesc);
	} else {
		sortedArray.sort(zenarioA.sortArrayForOrganizer);
	}
	
	//Remove fields that were just there to help sort
	foreach (sortedArray as i) {
		sortedArray[i] = sortedArray[i][0];
	}
	
	return sortedArray;
}

//Given two elements from the above function, say which order they should be in
zenarioA.sortArrayForOrganizer = function(a, b) {
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

zenarioA.sortArrayDesc = function(a, b) {
	return zenarioA.sortArrayForOrganizer(b, a);
};

zenarioA.csvToObject = function(aString) {
	
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
zenarioA.tuixToArray = function(tuix) {
	
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
	}
	
	return vals;
};











zenarioA.setButtonKin = function(buttons, parentClass) {
	zenarioA.setKin(buttons, parentClass || 'organiser_button_with_children');
};

zenarioA.setKin = function(buttons, parentClass) {
	
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
					
					if (parentClass !== undefined) {
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
};;


//Default error handler for lost AJAX requests
	//Note that there have been some issues with AJAX request submitted just before a page navigation being counted as timeouts
	//To get round this, firstly we try to detect page navigation and then don't show a message after this has happened
	//Secondly there's a slight time delay inserted on the 404 errors using setTimeout(), as a work around to try and prevent any race-conditions

zenarioA.AJAXErrorHandler = function(resp, statusType, statusText) {
	
	window.testResp = resp;
	console.log(resp, statusType, statusText);
	
	if (!zenarioA.unloaded) {
		var msg = '',
			flags = '',
			fun,
			isDev = zenarioA.adminSettings.show_dev_tools;
		
		resp = zenarioA.splitDataFromErrorMessage(resp);
		
		if (!(resp.getResponseHeader && resp.getResponseHeader('Zenario-Admin-Logged_Out'))) {
			if (statusText) {
				msg += _$h1(_$html('b', htmlspecialchars(resp.status + ' ' + statusText)));
			}
		
			if (resp.status == 404) {
				msg += _$p(isDev? phrase.error404Dev : phrase.error404);
		
			} else if (resp.status == 500) {
				msg += _$p(isDev? phrase.error500Dev : phrase.error500);
		
			} else if (resp.status == 0 || statusType == 'timeout') {
				msg += _$p(isDev? phrase.errorTimedOutDev : phrase.errorTimedOut);
			}
		
			if (resp.responseText) {
				msg += _$div(htmlspecialchars(resp.responseText));
			}
		}
		
		showErrorMessage = function() {
			
			var hasReply = resp.zenario_retry,
				hasContinueAnyway = resp.zenario_continueAnyway && resp.data,
				hasOnCancel = resp.zenario_onCancel,
				pressedCancel = true,
				buttonsHTML = '';
			
			if (hasReply || hasContinueAnyway) {
				
				if (hasContinueAnyway) {
					buttonsHTML += _$input('id', 'zenario_continueAnyway', __$class, 'submit_selected', __$type, __$button, __$value, phrase.continueAnyway);
				}
				if (hasReply) {
					buttonsHTML += _$input('id', 'zenario_retry', __$class, 'submit_selected', __$type, __$button, __$value, phrase.retry);
				}
				
				zenarioA.nowDoingSomething();
				//zenarioA.showMessage(message, buttonsHTML, messageType, modal, htmlEscapeMessage, addCancel, cancelPhrase)
				zenarioA.showMessage(msg, buttonsHTML, 'error', true, undefined, true, phrase.close);
				
				if (hasContinueAnyway) {
					$('#zenario_continueAnyway').click(function() {
						setTimeout(function() {
							resp.zenario_continueAnyway(resp.data);
						}, 1);
						pressedCancel = false;
					});
				}
				if (hasReply) {
					$('#zenario_retry').click(function() {
						setTimeout(resp.zenario_retry, 1);
						pressedCancel = false;
					});
				}
				
			} else {
				zenarioA.showMessage(msg, '', 'error');
			}
			
			if (hasOnCancel) {
				zenarioA.onCloseFloatingBox = function() {
					if (pressedCancel) {
						setTimeout(resp.zenario_onCancel, 1);
					}
				};
			}
			
			//Close the AJAX loader if it was open
			zenarioA.hideAJAXLoader();
		}
		
		if (resp.status == 0 || statusType == 'timeout') {
			setTimeout(showErrorMessage, 750);
		} else {
			showErrorMessage();
		}
	}
};
$.ajaxSetup({error: zenarioA.AJAXErrorHandler});

zenarioA.attempts = {};
zenarioA.attemptNum = 0;
zenarioA.keepTrying = function(fun, attempt) {
	if (attempt === undefined) {
		attempt = ++zenarioA.attemptNum;
		zenarioA.attempts[attempt] = true;
	
	} else if (!zenarioA.attempts[attempt]) {
		return;
	}
	
	fun(attempt);
	
	setTimeout(function() {
		zenarioA.keepTrying(fun, attempt);
	}, 30000);
};

zenarioA.stopTrying = function(attempt) {
	if (!zenarioA.attempts[attempt]) {
		return false;
	
	} else {
		delete zenarioA.attempts[attempt];
		return true;
	}
};



zenarioA.onunload = function(e) {
	if (!window.onpagehide || !e.persisted) {
		zenarioA.unloaded = true;
		zenarioA.closeFloatingBox();
	}
};

if (window.onpagehide) {
	window.addEventListener("pagehide", zenarioA.onunload, false);
} else {
	window.onunload = zenarioA.onunload;
}


zenarioA.stopDefault = function(e) {
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
zenarioA.stopFileDragDrop = function(e) {
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

zenarioA.disableFileDragDrop = function(el) {
	if (zenarioA.canDoHTML5Upload()) {
		el.addEventListener('drop', zenarioA.stopDefault, false);
		el.addEventListener('dragenter', zenarioA.stopDefault, false);
		el.addEventListener('dragover', zenarioA.stopDefault, false);
		el.addEventListener('dragexit', zenarioA.stopDefault, false);
	}
};

//Check whether cookies are enabled and able to be used.
//Because of the way PHP/browsers/cookies work, if this is the first access it might take one
//attempt to initialise, and then only on the second attempt will the result return true.
//To get round this we'll try up to two times.
zenarioA.checkCookiesEnabled = function() {
	var url = URLBasePath + 'zenario/cookies.php?check_cookies_enabled=1&no_cache=1',
		cb = new zenario.callback;
	
	zenario.ajax(url).after(function(result) {
		if (result) {
			cb.call(result);
		} else {
			zenario.ajax(url).after(function(result) {
				cb.call(result);
			});
		}
	});
	
	return cb;
};


//Check all hyperlinks on the page and add its status
zenarioA.scanHyperlinksAndDisplayStatus = function(containerId) {
    
    var url, relativePath, i, j, match, resolvedURL, requestURI, index, editor,
        post = {},
        ajaxURL = URLBasePath + 'zenario/admin/quick_ajax.php?_get_link_statuses=1',
        links = [], $links = [],
        isAbsolutePath = new RegExp('^(?:[a-z]+:)?//', 'i'),
        query = 'div' + (containerId === undefined? '' : '#' + containerId) + '.zenario_slot a[href][href!="#"]';
    
    $(query).each(function(ei, el) {
        
        var $el = $(el);
        
        if (url = $el.prop('href')) {
            relativePath = false;
            
            //Check if this link is internal and get the relative link
            if (!isAbsolutePath.test(url)) {
                relativePath = url;
            } else if (url.indexOf(URLBasePath) === 0) {
                relativePath = url.substr(URLBasePath.length - 1);
            //Links to spare domains are always highlighted
            } else if (zenarioA.spareDomains) {
                for (i = 0; i < zenarioA.spareDomains.length; ++i) {
                    if (url.indexOf(zenarioA.spareDomains[i]) === 0) {
                        $el.append(_$html('del', __$class, 'zenario_link_status zenario_link_status__spare_domain', _$html('del')));
                    }
                }
            }
            
            if (relativePath) {
                //Make sure link is to a content item (following .htaccess rules for aliases)
                requestURI = relativePath.split('?')[0].split('#')[0];
                if (!requestURI.match(/\/(admin|public|private|zenario|zenario_custom|zenario_extra_modules|purchased_downloads)\//)) {
                    if (match = requestURI.match(/^([\/,A-Za-z0-9~_-]+)(|\.htm|\.html)$/)) {
                        resolvedURL = '/?cID=' + match[1];
                    } else {
                        resolvedURL = relativePath;
                    }
                    
                    //Store this link and a reference of it's jquery object
                    if ((index = links.indexOf(resolvedURL)) === -1) {
                        links.push(resolvedURL);
                        $links.push([$el]);
                    } else {
                        $links[index].push($el);
                    }
                }
            }
        }
    });
    
    //Get statuses of content items and append status identifiers
    post.links = links;
    zenario.ajax(ajaxURL, post, true, true).after(function(statuses) {
        for (i = 0; i < statuses.length; ++i) {
            for (j = 0; j < $links[i].length; ++j) {
                $links[i][j].append(_$html('del', __$class, 'zenario_link_status zenario_link_status__' + statuses[i], _$html('del')));
            }
        }
		
		//If there are any open tinyMCE editors, remove any icons we just added to them
		if (window.tinyMCE
		 && tinyMCE.editors) {
			zenario.removeLinkStatus($('div.mce-content-body'));
		}
    });
};



//Calculate function short names, we need to do this before calling any functions!
zenario.shrtNms(zenarioA);






});
});