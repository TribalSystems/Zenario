/*
 * Copyright (c) 2022, Tribal Limited
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
	zenario, zenarioA, zenarioT, zenarioAB, zenarioAT, zenarioO,
	encodeURIComponent, defined, engToBoolean, get, htmlspecialchars, jsEscape, phrase,
	extensionOf, methodsOf, has
) {
	"use strict";

zenarioA.orgMinWidth = 550;

zenarioA.tooltipLengthThresholds = {
	adminBoxTitle: 120,
	adminToolbarTitle: 60,
	organizerBackButton: 70,
	organizerPanelTitle: 100
};


var ADMIN_MESSAGE_BOX_WIDTH = 700;





var plgslt_ = 'plgslt_';

phrase = phrase || {};
zenarioA.adminSettings = zenarioA.adminSettings || {};
zenarioA.adminPrivs = zenarioA.adminPrivs || {};
zenarioA.showGridOn = false;
zenarioA.showEmptySlotsOn = false;



zenarioT.lib(function(
	_$html,
	_$div,
	_$input,
	_$select,
	_$option,
	_$span,
	_$label,
	_$p,
	_$h1
) {


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
		zenarioA.openBox(zenarioT.microTemplate('zenario_info_box', data), 'zenario_fbAdminInfoBox', 'AdminInfoBox', undefined, 405, undefined, undefined, false, true, '.zenario_infoBoxHead', false);
	});
};

zenarioA.closeInfoBox = function() {
	zenarioA.closeBox('AdminInfoBox');
};

zenarioA.showMessage = function(resp, buttonsHTML, messageType, modal, htmlEscapeMessage, addCancel, cancelPhrase, onOkay) {
	var end = false,
		hadCommand = false,
		message,
		flags;

	if (resp) {
		resp = zenario.splitFlagsFromMessage(resp);
		message = resp.responseText;
		flags = resp.flags;
	
	} else {
		message = (zenarioA.adminSettings.show_dev_tools? phrase.errorTimedOutDev : phrase.errorTimedOut);
		flags = {};
	}

	if (!defined(buttonsHTML)) {
		buttonsHTML = true;
	}

	//Message types
	if (!defined(messageType)) {
		messageType = 'none';
	}
	
	if (flags.Message_Type) {
		if (flags.Message_Type == 'None') {
			messageType = false;
		} else {
			messageType = flags.Message_Type.toLowerCase();
		}
	}
	
	//Show a toast
	if (flags.Toast_Message) {
		zenarioA.toast({
			message: flags.Toast_Message,
			message_type: flags.Toast_Type,
			title: flags.Toast_Title
		});
	}


	//Commands
		//N.b. a lot of these are deprecated and/or not used!

	if (defined(flags.Reload_Organizer)	//Reload_Storekeeper
	 && zenarioO.init
	 && !window.zenarioOQuickMode
	 && !window.zenarioOSelectMode) {
		//Still show the Admin the contents of the message via an alert, if there was a message
		if (message) {
			alert(message);
		}
		zenarioA.toastOrNoToast(flags);
		
		zenarioT.uploading = false;
		zenarioO.setWrapperClass('uploading', zenarioT.uploading);
	
		zenarioO.reloadPage();
	
		return false;

	} else
	if (defined(flags.Refresh_Organizer)	//flags.Refresh_Storekeeper
	 && zenarioO.init
	 && !window.zenarioOQuickMode
	 && !window.zenarioOSelectMode) {
		zenarioO.reload();
		hadCommand = true;

	} else if (flags.Go_To_Organizer_Panel) {	//Go_To_Storekeeper_Panel
		zenarioO.go(flags.Go_To_Organizer_Panel, -1);
		hadCommand = true;

	//Open an Admin Box
	} else if (flags.Open_Admin_Box) {
		zenarioAB.open(flags.Open_Admin_Box);
		hadCommand = true;

	//Go somewhere
	} else if (defined(flags.Go_To_URL)) {
		zenarioA.toastOrNoToast(flags);
		zenario.goToURL(zenario.addBasePath(flags.Go_To_URL), true);
		hadCommand = true;
	}

	if (hadCommand && !message) {
		return false;
	}
	
	if (defined(flags.Modal)) {
		modal = true;
	}

	//Set some custom buttons
	if (flags.Button_HTML) {
		buttonsHTML = flags.Button_HTML;

	} else if (flags.Reload_Button) {
		buttonsHTML = _$input('class', 'submit_selected', 'type', 'button', 'onclick', 'document.location.href = document.location.href; return false;', 'value', flags.Reload_Button);

	} else if (defined(flags.Logged_Out)) {
		
		//If the admin has been logged out, check to see whether this window is in an iframe, and show the login window in the iframe if possible.
		if (zenarioA.loggedOutIframeCheck(message, messageType)) {
			return;
		}
		
		if (zenarioO.init
		 && zenarioO.path
		 && zenarioA.isFullOrganizerWindow) {
			buttonsHTML = _$input('type', 'button', 'value', phrase.login, 'class', 'submit_selected', 'onclick', 'zenarioO.reloadPage(undefined, true, undefined, true);');
			
			addCancel = "zenario.goToURL(URLBasePath);";
		
		} else {
			buttonsHTML = 
				_$input('type', 'button', 'value', phrase.login, 'class', 'submit_selected', 'onclick', 'zenario.goToURL(zenario.linkToItem(zenario.cID, zenario.cType, zenarioA.importantGetRequests, true));');
			
			addCancel = "zenario.goToURL(zenario.linkToItem(zenario.cID, zenario.cType, zenarioA.importantGetRequests));";
		}
		
		modal = true;
	
	//Don't show a blank box for no reason
	} else if (!message) {
		return true;
	}

	zenarioA.floatingBox(message, buttonsHTML, messageType, modal, htmlEscapeMessage, addCancel, cancelPhrase, onOkay);
	return true;
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

//Given a URL and (optionally) some post variables, that point to a download file, do the download.
//This is done in a hidden iframe, so the user does not see an empty blank tab appear in their browser that they then must close.
//Note that for this to work, the server must send the "Content-Disposition: attachment" header correctly to ensure the file is a download.
zenarioA.doDownload = function(url, postRequests) {
	
	var key, value,
		html = '',
		domForm = get('zenario_iframe_form');
	
	if (postRequests) {
		foreach (postRequests as key => value) {
			html += '<input type="hidden" name="' + htmlspecialchars(key) + '" value="' + htmlspecialchars(value) + '"/>';
		}
	}
	
	domForm.action = url;
	domForm.innerHTML = html;
	domForm.submit();
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
	
	if (auto == true && !defined(m.main_video_id)) {
		return;
	}
	
	// Open tutorial
	var html = zenarioT.microTemplate('zenario_tutorial', m);
	$.colorbox({
		width: 964,
		height: 791,
		innerHeight: 696,
		maxHeight: '95%',
		html: html,
		className: 'zenario_admin_cb zenario_tutorial_cbox',
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
		html = zenarioT.microTemplate('zenario_tutorial_main_video', m);
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
zenarioA.getItemFromOrganizer = function(path, id, async, request) {
	
	if (typeof path == 'string') {
		if (zenarioO.map) {
			path = zenarioO.convertNavPathToTagPath(path);
		} else {
			path = {path: path};
		}
	}
	
	var i,
		data,
		first = false,
		url =
			URLBasePath +
			'zenario/admin/organizer.ajax.php?_start=0&_get_item_name=1&path=' + encodeURIComponent(path.path);
	
	if (defined(request)) {
		url += zenario.urlRequest(request);
	}
	
	if (defined(id)) {
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
	
	if (path.refinerName) {
		url += '&refinerName=' + encodeURIComponent(path.refinerName);
	
		if (defined(path.refinerId)) {
			url += '&refinerId=' + encodeURIComponent(path.refinerId);
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


zenarioA.toggleShowGrid = function() {
	if (zenarioA.checkForEdits()) {
		zenarioA.showGridOn = !zenarioA.showGridOn;
		zenarioAT.clickTab(zenarioA.toolbar);
	}
};

zenarioA.toggleShowEmptySlots = function(alwaysShow) {
	if (zenarioA.checkForEdits()) {
		zenarioA.showEmptySlotsOn = !zenarioA.showEmptySlotsOn;

		if (alwaysShow || zenarioA.showEmptySlotsOn) {
			$(document.body).addClass('zenario_show_empty_slots_and_mobile_only_slots');
		} else {
			$(document.body).removeClass('zenario_show_empty_slots_and_mobile_only_slots');
		}

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
			id = plgslt_ + (this.id + '').replace('zenario_fbAdminPluginOptionsWrap-', '');
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
	$('.zenario_slotPluginControlBox').parent().children()
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
		},
		maxCols = zenarioGrid.cols,
		maxWidth = zenarioGrid.maxWidth;
	
	if ($gridspan.length) {
		//Attempt to get the CSS class names of the wrapper of the slot
		//(it's easier to look this up using JavaScript than it is to work it out in fillAllAdminSlotControls() in php).
		grid.cssClass = $gridspan.attr('class');
		
		//Strip out "alpha" and "omega" from the class names
		grid.cssClass = grid.cssClass.replace(' alpha ', ' ').replace(' omega ', ' ');
		
		//Get the actual width of the slot
		var fluidWidth = false,
			width, widthInfo, wasMaxWidth = false,
			pxWidth = $gridspan.width(),
			container,
			
			si, styleSheet,
			ri, rule, rules,
			mi, mule, mules,
			selectorText, match,
			
		
			//Try and read the number of columns from the css class names, e.g. "span3"
			css = $gridspan.attr('class') || '',
			columns = css.match(/\bspan\d+\b/);
	
		if (columns) {
			columns = 1 * columns[0].match(/\d+/);
		}
	
		if (columns) {
			if (columns == 1) {
				widthInfo = '1 column';
			} else {
				widthInfo = columns + ' columns';
			}
			
			selectorText = '.container_' + maxCols + ' .span' + columns;
			
			//If we're using a grid-template, try to work out the width of this slot
			//from the CSS
			if (maxCols && maxWidth) {
				//Loop through each stylesheet/rule, checking to see if there is a grid and a "span" rule that matches this span
				//Adapted from http://stackoverflow.com/questions/324486/how-do-you-read-css-rule-values-with-javascript
				outerLoop:
				foreach (document.styleSheets as si => styleSheet) {
					try {
						if (!styleSheet.href
						 || !styleSheet.href.indexOf('zenario_custom/templates/grid_templates/')) {
							continue;
						}
					
						rules = styleSheet.rules || styleSheet.cssRules;
		
						//middleLoop:
						foreach (rules as ri => rule) {
						
							if (rule.selectorText
							 && rule.selectorText == selectorText) {
								if (width = rule.style.width) {
									break outerLoop;
								
								} else if (width = rule.style['max-width']) {
									wasMaxWidth = true;
									break outerLoop;
								}
							}
						
						
							mules = rule.rules || rule.cssRules;
		
							//innerLoop:
							foreach (mules as mi => mule) {
						
								if (mule.selectorText
								 && mule.selectorText == selectorText) {
									if (width = mule.style.width) {
										break outerLoop;
								
									} else if (width = mule.style['max-width']) {
										wasMaxWidth = true;
										break outerLoop;
									}
								}
							}
						}
					} catch (e) {
						width = '';
					}
				}
			
			
				if (width) {
					
					widthInfo += ', ' + width;
					
					if ((match = width.match(/^(\d+\.?\d*)\%$/))
					 && (match = 1 * maxWidth * 0.01 * match[1])) {
						widthInfo += ' (' + Math.ceil(match) + ' px ' + phrase.atMax + ')';
					
					} else if (wasMaxWidth) {
						widthInfo += ' ' + phrase.atMax;
					}
				}
			}
		}
		
		grid.widthInfo = widthInfo;
		grid.pxWidth = pxWidth;
		grid.columns = columns;
		
		
		//Work out the size of the container
		if ((container = $gridspan.closest('div.container'))
		 && (container = container.attr('class'))
		 && (container = container.match(/container_(\d+)/))
		 && (container[1])) {
			grid.container = 1*container[1];
		}
	
	} else {
		//Fallback logic for non-Gridmaker slots
		$gridspan = $('.' + slotName + '.slot');
		
		if ($gridspan.length) {
			grid.cssClass = $gridspan.attr('class');
		}
	}
	
	return grid;
};




/*  Functions for managing plugin slots  */


//Attempt to show the drop-down menu for the slot when clicking anywhere in it
var stoppingWrapperClicks = false;
zenarioA.adminSlotWrapperClick = function(slotName, e, isEgg) {
	
	
	if (stoppingWrapperClicks) {
		return false;
	}
	
	//Don't do anything in preview/create/menu modes
	if (zenarioA.toolbar == 'preview'
	 || zenarioA.toolbar == 'create'
	 || zenarioA.toolbar.match(/^menu/)) {
		return true;
	}
	
	//Don't allow clicks on nested plugins
	if (isEgg) {
		zenario.stop(e);
		return false;
	}
	
	//Don't try to open the dropdown menu if things are being edited
	if (zenarioA.checkSlotsBeingEdited(true)) {
		return true;
	}
	
	var slotControlsBox = get(plgslt_ + slotName + '-options');
	
	//Don't do anything if we can't find the anchor for the drop-down menu
	if (!slotControlsBox) {
		return true;
	}
	
	//This line tries to open the drop-down menu near where the mouse cursor is
	zenarioA.openSlotControls(slotControlsBox, e, slotName);
	
	//This (commented out) line would try to open the drop-down menu in its usual place
	//at the top-right of the slot.
	//zenarioA.openSlotControls(slotControlsBox, slotControlsBox, slotName);
	
	return false;
};

//Allow other buttons/widgets in slots to be clicked on without invoking the slot menu
zenarioA.suspendStopWrapperClicks = function() {
	
	if (stoppingWrapperClicks) {
		clearTimeout(stoppingWrapperClicks);
	}
	
	stoppingWrapperClicks = setTimeout(function() {
		stoppingWrapperClicks = false;
	}, 1);
};




//Show the drop-down menu for the slot
zenarioA.openSlotControls = function(el, e, slotName, isFromAdminToolbar) {
	
	var closeAsIsAlreadyOpen = !isFromAdminToolbar && $('#zenario_fbAdminSlotControls-' + slotName).is(':visible'),
		left,
		top,
		thisSlotControlsBox = 'AdminSlotControls-' + slotName,
		sectionSel, infoSel, $parents;
	
	el.blur();
	
	if (!isFromAdminToolbar
	  || openSlotControlsBox != thisSlotControlsBox) {
		zenarioA.closeSlotControls();
	}
	
	if (!closeAsIsAlreadyOpen && zenarioA.checkForEdits()) {
		
		//If this was opened from the admin toolbar, keep the drop-down menu open for now
		if (wasFromAdminToolbar = isFromAdminToolbar) {
			$('#zenario_at_toolbars .zenario_at_slot_controls ul li#zenario_at_button__slot_control_dropdown ul').css('display', 'block');
			slotParentMouseOverLastId = false;
		}
		
		
		var width,
			section,
			sections = {
				info: false, notes: false, actions: false,
				re_move_place: false, overridden_info: false, overridden_actions: false,
				no_perms: false
			},
			instanceId = zenario.slots[slotName].instanceId,
			grid = zenarioA.getGridSlotDetails(slotName);
		
		if (get('zenario_fbAdminSlotControls-' + slotName).innerHTML.indexOf('zenario_long_option') == -1) {
			width = 300;
		} else {
			width = 280;
		}
		
		if (isFromAdminToolbar) {
			$('#zenario_at_button__slot_' + slotName).addClass('zenario_atSlotControlOpen');
			
			left = 200;
			top = 0;
		} else {
			left = -width + 44;
			top = 32;
		}
		
		zenarioA.openBox(
			undefined,
			get(plgslt_ + slotName + '-wrap').className + ' zenario_fbAdminSlotControls',
			openSlotControlsBox = thisSlotControlsBox,
			e, width, left, top, false, false, false, false);
		
		//Check that each section has at least one label or button in it. If not, hide that section
		foreach (sections as section) {
			sectionSel = '#zenario_fbAdminSlotControls-' + slotName + ' .zenario_slotControlsWrap_' + section;
			
			$(sectionSel).show();
			
			$(sectionSel + ' .zenario_sc:visible').each(function(i, el) {
				sections[section] = true;
			});
		
			if (!sections[section]) {
				$(sectionSel).hide();
			}
		}
		
		infoSel = '#zenario_slot_control__' + slotName + '__info__';
		
		//We've hidden the plugin and slot's CSS classes for now to reduce clutter.
		//if (grid.cssClass) {
		//	//Strip out some technical class-names that make the grid work but designers don't need to see
		//	grid.cssClass = grid.cssClass.replace(/\bspan\d*_?\d*\s/g, '');
		//	
		//	//$(infoSel + 'grid_css_class').show();
		//	$(infoSel + 'grid_css_class > span').text(grid.cssClass);
		//} else {
		//	$(infoSel + 'grid_css_class').hide();
		//}
		//
		//if (grid.widthInfo) {
		//	//$(infoSel + 'grid_width').show();
		//	$(infoSel + 'grid_width > span').text(grid.widthInfo);
		//} else {
		//	$(infoSel + 'grid_width').hide();
		//}
		
		//Don't show the "copy embed link" option if this browser doesn't support copy and paste
		if (!zenario.canCopy()) {
			$(infoSel + 'embed').hide();
		}
		
		//Hide the "only on desktop"/"only on mobile" warnings if this slot doesn't work like that
		$parents = $('#' + plgslt_ + slotName).parents();
		if (!$parents.filter('.responsive').length) {
			$(infoSel + 'desktop').hide();
		}
		if (!$parents.filter('.responsive_only').length) {
			$(infoSel + 'mobile').hide();
		}
		
		
		$('#' + plgslt_ + slotName + '-control_box').addClass('zenario_adminSlotControlsOpen');
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
	var $slot = $('#' + plgslt_ + slotName);
	
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
		$('.zenario_slotPluginControlBox').removeClass('zenario_adminSlotControlsOpen');
		$('.zenario_atSlotControl').removeClass('zenario_atSlotControlOpen');
		
		//Allow the slot controls on the admin toolbar to be closed once again
		$('#zenario_at_toolbars .zenario_at_slot_controls ul li#zenario_at_button__slot_control_dropdown ul').css('display', '');
	}
};




zenarioA.pickNewPluginSlotName = false;
zenarioA.pickNewPlugin = function(el, slotName, level, isNest, preselectCurrentChoice) {
	el.blur();
	
	zenarioA.pickNewPluginSlotName = slotName;
	zenarioA.pickNewPluginLevel = level;
	
	var slot = zenario.slots[slotName] || {},
		moduleId = slot.moduleId,
		instanceId = slot.instanceId,
		path,
		chooseButtonPhrase;
	
	if (isNest === undefined) {
		switch (slot.moduleClassName) {
			case 'zenario_plugin_nest':
				isNest = true;
				break;
			case 'zenario_slideshow':
				isNest = 'slideshow';
				break;
			default:
				isNest = false;
		}
	}
	
	if (isNest) {
		if (isNest == 'slideshow') {
			path = 'zenario__modules/panels/plugins/refiners/slideshows////';
			chooseButtonPhrase = phrase.insertSlideshow;
		} else {
			path = 'zenario__modules/panels/plugins/refiners/nests////';
			chooseButtonPhrase = phrase.insertNest;
		}
	
		//Select the existing module and plugin if possible
		if (preselectCurrentChoice && instanceId) {
			path += instanceId;
		}
	} else {
		path = 'zenario__modules/panels/modules/refiners/slotable_only////';
		chooseButtonPhrase = phrase.insertPlugin;
	
		//Select the existing module and plugin if possible
		if (preselectCurrentChoice && instanceId) {
			path += 'item//' + zenario.slots[slotName].moduleId + '//' + zenario.slots[slotName].instanceId;
		}
	}
	
	zenarioA.organizerSelect('zenarioA', 'addNewReusablePlugin', false, path, 'zenario__modules/panels/plugins', 'zenario__modules/panels/modules', 'zenario__modules/panels/plugins', true, true, chooseButtonPhrase);
	
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
	
	zenarioA.floatingBox(html, $(el).text(), 'warning', false, false, undefined, undefined, function() {
	
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
zenarioA.pluginSlotEditSettings = function(el, slotName, fabPath, requests, tab) {
	el.blur();
	var instanceId = zenario.slots[slotName].instanceId;
	
	if (!get('zenario_theme_name_' + slotName + '__0') && instanceId) {
		
		requests = _.extend(
			{
				cID: zenario.cID,
				cType: zenario.cType,
				cVersion: zenario.cVersion,
				slotName: slotName,
				instanceId: instanceId,
				frontEnd: 1
			},
			zenarioAB.getConductorVars(slotName) || {},	
			requests || {}
		);
		
		zenarioAB.open(fabPath || 'plugin_settings', requests, tab);
		
		zenarioA.suspendStopWrapperClicks();
	}
	
	return false;
};

//Moving modules
zenarioA.movePlugin = function(el, slotName) {
	el.blur();
	
	zenarioA.floatingBox(phrase.movePluginDesc, true, 'question', true, true, undefined, undefined, function() {
		zenarioA.moveSource = slotName;
		$('.zenario_slotPluginControlBox').addClass('zenario_moveDestination');
		$('#' + plgslt_ + slotName + '-control_box').removeClass('zenario_moveDestination').addClass('zenario_moveSource');
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
		} else if (zenarioA.toolbar == 'layout') {
			var html = zenario.moduleNonAsyncAJAX('zenario_common_features', {movePlugin: 1, level: 2, cID: zenario.cID, cType: zenario.cType, cVersion: zenario.cVersion}, false);
			
			if (zenarioA.loggedOut(html)) {
				return;
			}
			
			zenarioA.floatingBox(html, phrase.movePlugin, 'warning', false, false, undefined, undefined, function() {
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
	$('.zenario_slotPluginControlBox').removeClass('zenario_moveDestination').removeClass('zenario_moveSource');
	
	return false;
};




zenarioA.refreshAllSlotsWithCutCopyPaste = function(allowedModules) {
	
	//Try to get a list of every type of plugin affected
	var slotName,
		module,
		modules = zenarioT.csvToObject(allowedModules);
	
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
	
	zenarioA.floatingBox(phrase.overwriteContentsConfirm, $(el).text(), 'warning', false, false, undefined, undefined, function() {
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
	
	zenarioA.floatingBox(phrase.swapContentsConfirm, $(el).text(), 'warning', false, false, undefined, undefined, function() {
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
	zenarioA.toggleShowEmptySlots(true);

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
		var html = zenario.moduleNonAsyncAJAX('zenario_common_features', req, false);
		
		if (zenarioA.loggedOut(html)) {
			return;
		}
	
		zenarioA.floatingBox(html, phrase.remove, true, false, false, undefined, undefined, doRemovePlugin);
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
zenarioA.replacePluginSlot = function(slotName, instanceId, level, slideId, resp, scriptsToRunBefore) {
	
	var script,
		containerId = plgslt_ + slotName,
		flags = resp.flags,
		moduleId = 1*flags.MODULE_ID,
		whatThisIs = flags.WHAT_THIS_IS || '',
		isMenu = flags.IS_MENU,
		isVersionControlled = flags.WIREFRAME,
		beingEdited = flags.IN_EDIT_MODE,
		className = flags.NAMESPACE,
		layoutPreview = flags.LAYOUT_PREVIEW,
		slotControls = flags.SLOT_CONTROLS,
		slotControlsCSSClass = flags.SLOT_CONTROLS_CSS_CLASS,
		domLayoutPreview = get(containerId + '-layout_preview');
	
	if (moduleId && (!window[className] || _.isEmpty(window[className].slots))) {
		zenario.addPluginJavaScript(moduleId);
	}
	
	if (!moduleId) {
		instanceId = 0;
	}
	
	//Add a css class around slots that are being edited using the WYSIWYG Editor
	if (beingEdited) {
		slotControlsCSSClass += ' zenario_slot_being_edited';
	}
	
	if (layoutPreview) {
		if (!domLayoutPreview) {
			domLayoutPreview = $(
				_$div('id', containerId + '-layout_preview', 'class', 'zenario_slot_layout_preview zenario_slot')
			).insertAfter('#' + containerId + '-control_box')[0];
		}
		
		domLayoutPreview.innerHTML = layoutPreview;
		domLayoutPreview.style.display = '';
	} else {
		if (domLayoutPreview) {
			domLayoutPreview.innerHTML = '';
			domLayoutPreview.style.display = 'none';
		}
	}
	
	//Set the CSS class for the slot's admin wrapper/slot controls
	get(containerId + '-wrap').className = slotControlsCSSClass;
	
	//If any slots are being edited, set a warning message for if an admin tries to leave the page 
	window.onbeforeunload = zenarioT.onbeforeunload;
	
	//Remember that this slot is being edited
	zenario.slots[slotName].beingEdited = beingEdited;
	
	foreach (scriptsToRunBefore as script) {
		if (zenario.slots[slotName]) {
			zenario.callScript(scriptsToRunBefore[script], zenario.slots[slotName].moduleClassName);
		}
	}
	
	//Refresh the slot's innerHTML
	get(plgslt_ + slotName).innerHTML = resp.responseText;
	get('zenario_fbAdminSlotControlsContents-' + slotName).innerHTML = slotControls;
	
	//Set the current instance id
	zenario.slot([[slotName, instanceId, moduleId, level, slideId, undefined, beingEdited, isVersionControlled, isMenu]]);
	
	
	//Set tooltips for the area, if we are using tooltips
	zenario.tooltips('#' + containerId + ' a');
	zenario.tooltips('#' + containerId + ' img');
	zenario.tooltips('#' + containerId + ' input');
	
	zenarioA.tooltips('#' + containerId + '-wrap', {content: whatThisIs, items: '#' + containerId + '-wrap'});

};


zenarioA.checkSlotsBeingEdited = function(dontUpdateBodyClass) {
	if (zenario.slots) {
		foreach (zenario.slots as var slotName => var slot) {
			if (slot.beingEdited) {
				
				if (!dontUpdateBodyClass) {
					$('body').addClass('zenario_being_edited');
				}
				return true;
			}
		}
	}
	
	if (!dontUpdateBodyClass) {
		$('body').removeClass('zenario_being_edited');
	}
	
	return false;
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




//Pop up messages/boxes


zenarioA.boxesOpen = {};
zenarioA.openBox = function(html, className, n, e, width, left, top, disablePageBelow, overlay, draggable, resizable, padding, maxHeight, rightCornerOfElement, bottomCornerOfElement) {
	
	if (!n) {
		n = 1;
	}
	
	if (!defined(className)) {
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
			
			$('body').append(_$div('class', overlayClassName, 'id', 'zenario_fb' + n + '__overlay', 'style', 'display: none;'));
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
		
		if (!defined(resizable.containment)) {
			resizable.containment = 'document';
		}
		if (!defined(resizable.minWidth)) {
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
	
	if (!defined(top)) {
		top = 15;
	}
	
	if (!defined(left)) {
		left = 50;
	}
	
	if (!defined(padding)) {
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
	
	
	if (html !== false && defined(html)) {
		wrapper.innerHTML = html;
		zenario.addJQueryElements('#zenario_fb' + n + ' ', true);
	}
	
	if (!defined(maxHeight)) {
		maxHeight = $('#zenario_fb' + n).height() || 50;
	}
	
	
	//Position the floating box
	//e can be a mouse event, or an object that was clicked on
	//If e is provided, then position it relative to the mouse/object. Otherwise position it relative to the screen.
	if (e) {
		var y;
		if (defined(e.clientY) || defined(e.pageY)) {
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
			if (defined(e.clientX) || defined(e.pageX)) {
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

zenarioA.floatingBox = function(message, buttonsHTML, messageType, modal, htmlEscapeMessage, addCancel, cancelPhrase, onOkay) {
	var defaultModalValue = false,
		html,
		m;
	
	
	if (htmlEscapeMessage) {
		message = htmlspecialchars(message, true);
	}
	
	if (buttonsHTML === true) {
		buttonsHTML = _$input('type', 'button', 'class', 'submit', 'value', phrase.OK);
	
	} else if (buttonsHTML && buttonsHTML.indexOf('<input ') === -1) {
		buttonsHTML =
			_$input('class', 'submit_selected', 'type', 'button', 'value', buttonsHTML);
		
		if (!defined(addCancel)) {
			addCancel = true;
		}
	}
	
	if (addCancel) {
		if (addCancel === true) {
			addCancel = '';
		}
		buttonsHTML += 
			_$input('type', 'button', 'class', 'submit', 'value', cancelPhrase || phrase.cancel, 'onclick', addCancel);
	}
	
	if (messageType == 'success' || messageType == 4) {
		messageType = 'zenario_fbSuccess';
	
	} else if (messageType == 'question' || messageType == 3) {
		messageType = 'zenario_fbQuestion';
	
	} else if (messageType == 'error' || messageType == 2) {
		messageType = 'zenario_fbError';
	
	} else if (messageType == 'info') {
		messageType = 'zenario_fbInfo';
	
	} else if (messageType && messageType != 'none') {
		messageType = 'zenario_fbWarning';
		defaultModalValue = true;
	
	} else {
		messageType = '';
	}
	
	if (!defined(modal)) {
		modal = defaultModalValue;
	}
	
	
	m = {
		message: message,
		messageType: messageType,
		buttonsHTML: buttonsHTML
	};
	
	html = zenarioT.microTemplate('zenario_popout_message', m);
	
	delete zenarioA.onCancelFloatingBox;
	zenarioA.openBox(html, 'zenario_fbAdmin zenario_prompt', 'AdminMessage', undefined, ADMIN_MESSAGE_BOX_WIDTH, 50, 17, modal, true, false, false);
	
	//Add the command to close the floating box to each button in the box.
	//Note that it must come *before* any other action.
	$('#zenario_fbMessageButtons button, #zenario_fbMessageButtons input[type="button"]').each(function(i, el) {
		var $button = $(el),
			existingOnlick = $button.attr('onclick');
		
		//If this button is using an onclick attribute, add the command to close the box before it
		if (existingOnlick && !(message && message.match('<form '))) {
			$button.attr('onclick', 'zenarioA.closeFloatingBox($(this)); ' + existingOnlick);
		
		//Otherwise assume that the caller is going to be using jQuery bindings,
		//and also add the command to close the box the same way.
		//Note that it *must* be the first binding!
		} else {
			$button.click(function () {
				zenarioA.closeFloatingBox($button);
			});
		}
	});
	
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

zenarioA.closeFloatingBox = function($button) {
	if (zenarioA.checkIfBoxIsOpen('AdminMessage')) {
		zenarioA.closeBox('AdminMessage');
		
		if (zenarioA.onCancelFloatingBox && $button && !$button.hasClass('submit_selected')) {
			zenarioA.onCancelFloatingBox();
			delete zenarioA.onCancelFloatingBox;
		}
	}
};


zenarioA.tooltips = function(target, options) {
	if (!options) {
		options = {};
	}
	
	if (!defined(options.tooltipClass)) {
		options.tooltipClass = 'zenario_admin_tooltip';
		//N.b. this is deprecated and will need to be changed to
			//options.classes = {"ui-tooltip": "zenario_admin_tooltip"};
		//at some point!
	}
	
	//Disable speach-assistance for admin mode tooltips.
	if (!defined(options.disableAriaLiveRegions)) {
		options.disableAriaLiveRegions = true;
	}
	
	zenario.tooltips(target, options);
};

zenarioA.addImagePropertiesButtons = function(path) {

	//If this is the front-end, check for images and try to add the image properties buttons
	if (zenario.cID) {
		
		//Look for things with the zenario_image_properties CSS class
		$(path + '.zenario_image_properties').each(function(i, el) {
			
			//Try to work out the image id, which will be using a CSS class in the format "zenario_image_id__123__"
			var $el = $(el),
				imageId = el.className.match(/zenario_image_id__(\d+)__/),
				slotName = zenario.getSlotnameFromEl(el),
				eggId = zenario.getSlotnameFromEl(el, false, true),
				slot = slotName && zenario.slots[slotName],
				instanceId = slot && slot.instanceId,
				nodeName, $parent, $imagePropertiesButton;
			
			if (imageId) {
				imageId = 1*imageId[1];
			}
			
			if (imageId && instanceId) {
				//We want to try and attach a button just before the image.
				//If there is an image inside a link, a picture tag, or a <div class="banner_image">, try to go up one
				//level and attach the button outside the tag, rather than inside
				while ($el
					&& ($parent = $el.parent())
					&& (nodeName = $parent[0].nodeName)
					&& (nodeName = nodeName.toLowerCase())
					&& (nodeName == 'a'
					 || nodeName == 'picture'
					 || (nodeName == 'div' && $parent.hasClass('banner_image')))
				) {
					$el = $parent;
				}
			
			
			
				if (instanceId) {
					$imagePropertiesButton = $(_$span('class', 'zenario_image_properties_button'));
				
					$el.before($imagePropertiesButton);
				
					$imagePropertiesButton.on('click', function() {
						zenarioAB.open('zenario_image', {
							id: imageId,
							slotName: slotName,
							instanceId: instanceId,
							eggId: eggId
						}, 'crop_1');
						
						return false;
					});
				}
			}
		});
	}
};

zenarioA.setTooltipIfTooLarge = function(target, title, sizeThreshold) {
	
	$(target).each(function(i, el) {
		var tooltip;
		
		if (!defined(title)) {
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
		/<u\b[^>]*?>/gi, _$span('style', 'text-decoration: underline;', '>')).replace(
		/<\/u>/gi, '</span>');
};

//Enable an Admin to upload an image or an animation by draging and dropping it onto the WYSIWYG Editor
//The file will be uploaded using a call to the handleOrganizerPanelAJAX() function of the Common Features Module
zenarioA.enableDragDropUploadInTinyMCE = function(enableImages, prefix, el) {
	
	if (typeof el == 'string') {
		el = get(el);
	}
	
	if (el) {
		zenarioT.disableFileDragDrop(el);
		
		if (enableImages && zenarioT.canDoHTML5Upload()) {
			var url = URLBasePath + 'zenario/ajax.php',
				request = {
					method_call: 'handleOrganizerPanelAJAX',
					__pluginClassName__: 'zenario_common_features',
					__path__: 'zenario__content/panels/image_library',
					upload: 1};
			
			zenarioT.setHTML5UploadFromDragDrop(
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
		 && zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[editorId],
		pick_items;

	
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
		 && fabField.insert_image_button
		 && fabField.insert_image_button.pick_items) {
			pick_items = fabField.insert_image_button.pick_items;
		
		} else
		if (!fabField
		 && zenario.cID
		 && zenario.cType
		 && window.zenario_wysiwyg_editor
		 && zenario_wysiwyg_editor.poking) {
			pick_items = {
				path: 'zenario__content/panels/content/item_buttons/images//' + zenario.cType + '_' + zenario.cID + '//',
				target_path: 'zenario__content/panels/image_library',
				min_path: 'zenario__content/panels/image_library',
				max_path: 'zenario__content/panels/image_library',
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
		$newPicker = $(zenarioT.microTemplate('zenario_tinymce_link_picker', {urlFieldId: $urlField.attr('id')})),
		urlFieldWidth = $urlField.width(),
		pickerWidth = $picker.width(),
		newPickerWidth;
	
	$picker.replaceWith($newPicker);
	
	newPickerWidth = $newPicker.width();
	
	$urlField.width(urlFieldWidth + pickerWidth - newPickerWidth);
	
	zenarioA.tooltips(
		$newPicker.find('button'),
		{tooltipClass: 'zenario_admin_tooltip zenario_admin_tooltip_over_tinymce'}
	);
};


//This function sets the value of one of the fields in the TinyMCE forms.
//It's used after picking something from the file browser
zenarioA.lastFieldValue = '';
zenarioA.setEditorField = function(value, el, onlyIfEmpty) {
	if (!defined(el)) {
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
	
	zenarioA.setEditorField(row.title, $('.mce-panel input.mce-link_text_to_display')[0], true);
	zenarioA.setEditorField(URL);
};

//This handles the return results of the file browser for a link to a public document
zenarioA.setDocumentURL = function(path, key, row) {
	var documentURL = row.frontend_link;
	
	if (zenarioA.tinyMCE_fromFAB) {
		documentURL = URLBasePath + documentURL;
	}
	
	zenarioA.setEditorField(row.name, $('.mce-panel input.mce-link_text_to_display')[0], true);
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
	
	zenarioA.setEditorField(row.alt_tag, $('.mce-panel input.mce-image_alt')[0], true);
	zenarioA.setEditorField(imageURL);
};









zenarioA.skinDesc = undefined;
zenarioA.getSkinDesc = function() {
	if (!defined(zenarioA.skinDesc)) {
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
		className: 'zenario_admin_cb zenario_page_preview_colorbox'
	});
};







/* Organizer launch functions */

//Format the name of an item from Organizer appropriately
zenarioA.formatOrganizerItemName = function(panel, itemId) {
	var string = undefined,
		string2,
		value;
	
	if (panel.items
	 && panel.items[itemId]) {
	
		if (string = string2 = panel.label_format_for_picked_items || panel.label_format_for_grid_view) {
			foreach (panel.items[itemId] as var c) {
				if (string.indexOf('[[' + c + ']]') != -1) {
					value = panel.items[itemId][c];
					
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
			string = panel.items[itemId][panel.default_sort_column || 'name'];
		}
	}
	
	if (!defined(string)) {
		return itemId;
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
			var pos = (value + '').indexOf(' ');
			if (pos != -1) {
				extra = value.substr(pos);
				value = value.substr(0, pos);
			}
		}
		
		if (format == 'true_or_false') {
			if (engToBoolean(value)) {
				value = defined(column.true_phrase)? column.true_phrase : phrase.tru;
			} else {
				value = defined(column.false_phrase)? column.false_phrase : phrase.fal;
			}
			
		} else if (format == 'yes_or_no') {
			if (engToBoolean(value)) {
				value = defined(column.yes_phrase)? column.yes_phrase : phrase.yes;
			} else {
				value = defined(column.no_phrase)? column.no_phrase : phrase.no;
			}
			
		} else if (format == 'remove_zero_padding') {
			value = value.replace(/\b0+/g, '');
			
		} else if (format == 'enum' && column.values && defined(column.values[value])) {
			
			if (typeof column.values[value] == 'object') {
				if (defined(column.values[value].label)) {
					value = column.values[value].label;
				}
			} else {
				value = column.values[value];
			}
			
		} else if ((format == 'module_name' || format == 'module_class_name') && zenarioO.init) {
			if (!value) {
				value = phrase.core;
			} else if (zenarioA.module[value]) {
				value = zenarioA.module[value].display_name;
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

zenarioA.module = {};
zenarioA.running = {};

zenarioA.setModuleInfo = function(modules) {
	var m, module;
	foreach (modules as m => module) {
		zenarioA.module[module.id] = 
		zenarioA.module[module.class_name] = module;
		
		if (module.running) {
			zenarioA.running[module.id] = 
			zenarioA.running[module.class_name] = module;
		}
	}
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
	if (!defined(win)) {
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
	if (!defined(maxPath)) {
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
		
		zenarioO.open(zenarioA.getSKBodyClass(win), undefined, $(window).width() * 0.99, 50, 1, true, true, true, {minWidth: zenarioA.orgMinWidth});
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
		_$html('iframe', 'id', 'zenario_sk_iframe', 'src', URLBasePath + 'organizer.php?openedInIframe=1&rand=' + (new Date).getTime());
	
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



zenarioA.translationsEnabled = function() {
	var lang,
		langs = 0;
	
	if (zenarioA.lang) {
		foreach (zenarioA.lang as lang) {
			if (zenarioA.lang[lang].translate_phrases) {
				return true;
			}
		}
	}
	
	return false;
};



zenarioA.getDefaultLanguageName = function() {
	var defaultLanguageCode = zenarioA.siteSettings.default_language;
	return zenarioA.lang[defaultLanguageCode]['name'];
}



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
	
	} else if (zenarioA.pageMode == 'menu') {
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
		
		zenarioAT.organizerQuick(path, 'zenario__menu/panels/menu_nodes', true, undefined, true);
		
	} else {
		//Otherwise open an Admin Box
		zenarioAB.open(openSpecificBox, key);
	}
	
	return false;
};

zenarioA.reloadMenuPlugins = function() {
	$('.zenario_showSlotInMenuMode .zenario_slot').each(function(i, el) {
		if (el.id && el.id.substr(0, 7) == plgslt_) {
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
	
	var tuix, section, button, buttons, buttonId, confirm, message,
		buttonsHTML,
		object;
	
	//Look for the "create a draft" button on the admin toolbar
	//If we see it, we know this is a published item and we need to create a draft
	if ((tuix = zenarioAT.tuix)
	 && (section = tuix.sections && tuix.sections.status_button)
	 && (buttons = section.buttons)
	 && (button = buttons[buttonId = 'start_editing'] || buttons[buttonId = 'redraft'])
	 && (button.ajax
		 //zenarioT.hidden(tuixObject, lib, item, id, button, column, field, section, tab, tuix)
	 && !zenarioT.hidden(undefined, zenarioAT, undefined, buttonId, button, undefined, undefined, section))) {
		
		//Create a copy of it
		object = zenario.clone(button);
		
		delete object.ajax.request.switch_to_edit_mode;
		
		//Should we show someone a warning before creating a draft?
		if (zenarioA.checkSpecificPermsOnThisPage() && (confirm = object.ajax.confirm)) {
			
			message = confirm.message__editing_published || confirm.message;
			
			//If so, show a confirmation box with up to three options:
			if (confirmMessage) {
				confirmMessage += '\n\n' + message;
			} else {
				confirmMessage = message;
				confirmButtonText = confirm.button_message;
			}
			
			//1. Create the draft, then when the draft has been created press this option again
			buttonsHTML =
				_$input('type', 'button', 'class', 'submit_selected', 'value', confirmButtonText, 'onclick', 'zenarioA.draftSetCallback("' + jsEscape(aId) + '"); zenarioAT.action2();');
			
			//2. Don't create a draft, press this option again and just view in read-only mode
			if (justView) {
				buttonsHTML +=
					_$input('type', 'button', 'class', 'submit', 'value', object.ajax.confirm.button_message__just_view, 'onclick', 'zenarioA.draftDoCallback("' + jsEscape(aId) + '");');
			}
			
			//3. Cancel
			buttonsHTML +=
				_$input('type', 'button', 'class', 'submit', 'value', object.ajax.confirm.cancel_button_message);
			
			object.ajax.confirm.message = '<!--Button_HTML:' + zenario.hypEscape(buttonsHTML) + '-->' + confirmMessage;
		
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
		zenarioT.action(zenarioAT, object);
		return false;
	
	
	//Handle the case where we're already on a draft, but there was still a confirm message to show
	} else if (confirmMessage) {
		buttonsHTML =
			_$input('type', 'button', 'class', 'submit_selected', 'value', confirmButtonText, 'onclick', 'zenarioA.draftDoCallback("' + jsEscape(aId) + '");');
		
		if (zenarioA.draftMessage) {
			confirmMessage += '<br/><br/>' + zenarioA.draftMessage;
		}
		
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
	data._save_page_show_grid = zenarioA.showGridOn? 1 : '';
	data._save_page_show_empty_slots = zenarioA.showEmptySlotsOn? 1 : '';
	
	$.ajax({
		type: 'POST',
		url: URLBasePath + 'zenario/admin/quick_ajax.php',
		data: data,
		async: async
	});
};

zenarioA.draftSetCallback = function(aId) {
	zenarioA.savePageMode(false, {
		_draft_set_callback: aId,
		_scroll_pos: zenario.scrollTop()
	});
};

zenarioA.draftDoCallback = function(aId, scrollPos) {
	zenarioA.draftDoingCallback = true;
	
	if (scrollPos) {
		zenario.scrollTop(scrollPos, undefined, undefined, true);
	}
	
	$('#' + aId).click();;
	
	delete zenarioA.draftDoingCallback;
};




//A shortcut to the toastr library
zenarioA.lastToast = false;
zenarioA.clearLastToast = false;
zenarioA.toast = function(object) {
	if (defined(object)
	 && _.isObject(object)) {
		
		//Remember this toast that we had for the next 60 seconds,
		//or until another toast comes in
		zenarioA.clearToast();
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



zenarioA.toastOrNoToast = function(flags) {
	if (flags.Clear_Toast) {
		zenarioA.clearToast();
	} else {
		zenarioA.rememberToast();
	}
};

zenarioA.clearToast = function() {
	if (zenarioA.clearLastToast) {
		clearTimeout(zenarioA.clearLastToast);
	}
};

zenarioA.rememberToast = function() {
	//Check if we just displayed a toast. If so, remember it for next time.
	if (zenarioA.lastToast) {
		zenario.nonAsyncAJAX(URLBasePath + 'zenario/admin/quick_ajax.php', {_remember_toast: zenarioA.lastToast});
	}
};

zenarioA.longToast = function(msg, cssClass) {
	if (!$('#toast-container .' + cssClass).length) {
		toastr.warning(msg, undefined, {timeOut: 5000, extendedTimeOut: 5000, toastClass: 'toast ' + cssClass});
	}
};



zenarioA.isHtaccessWorking = function() {
	return zenario.nonAsyncAJAX(URLBasePath + 'zenario/includes/test_files/is_htaccess_working.txt', true) == 'Yes';
};


//Default error handler for lost AJAX requests
	//Note that there have been some issues with AJAX request submitted just before a page navigation being counted as timeouts
	//To get round this, firstly we try to detect page navigation and then don't show a message after this has happened
	//Secondly there's a slight time delay inserted on the 404 errors using setTimeout(), as a work around to try and prevent any race-conditions

zenarioA.AJAXErrorHandler = function(resp, statusType, statusText) {
	
	if (!zenarioA.unloaded) {
		var msg = '',
			flags = '',
			fun,
			isDev = zenarioA.adminSettings.show_dev_tools;
		
		resp = zenarioT.splitDataFromErrorMessage(resp);
		
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
			
			resp.responseText = msg;
		}
		
		var showErrorMessage = function() {
			
			var hasReply = resp.zenario_retry,
				hasContinueAnyway = resp.zenario_continueAnyway && resp.data,
				hasOnCancel = resp.zenario_onCancel,
				buttonsHTML = '';
			
			if (hasReply || hasContinueAnyway) {
				
				if (hasContinueAnyway) {
					buttonsHTML += _$input('id', 'zenario_continueAnyway', 'class', 'submit_selected', 'type', 'button', 'value', phrase.continueAnyway);
				}
				if (hasReply) {
					buttonsHTML += _$input('id', 'zenario_retry', 'class', 'submit_selected', 'type', 'button', 'value', phrase.retry);
				}
				
				zenarioA.nowDoingSomething();
				//zenarioA.showMessage(message, buttonsHTML, messageType, modal, htmlEscapeMessage, addCancel, cancelPhrase)
				zenarioA.showMessage(resp, buttonsHTML, 'error', true, undefined, true, phrase.close);
				
				if (hasContinueAnyway) {
					$('#zenario_continueAnyway').click(function() {
						setTimeout(function() {
							resp.zenario_continueAnyway(resp.data);
						}, 1);
					});
				}
				if (hasReply) {
					$('#zenario_retry').click(function() {
						setTimeout(resp.zenario_retry, 1);
					});
				}
				
			} else {
				zenarioA.showMessage(resp, '', 'error');
			}
			
			if (hasOnCancel) {
				zenarioA.onCancelFloatingBox = function() {
					setTimeout(resp.zenario_onCancel, 1);
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

//Check whether cookies are enabled and able to be used.
//Because of the way PHP/browsers/cookies work, if this is the first access it might take one
//attempt to initialise, and then only on the second attempt will the result return true.
//To get round this we'll try up to two times.
zenarioA.checkCookiesEnabled = function() {
	var url = URLBasePath + 'zenario/cookies.php?check_cookies_enabled=1&no_cache=1',
		cb = new zenario.callback;
	
	zenario.ajax(url).after(function(result) {
		if (result) {
			cb.done(result);
		} else {
			zenario.ajax(url).after(function(result) {
				cb.done(result);
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
        query = 'div' + (!defined(containerId)? '' : '#' + containerId) + '.zenario_slot a[href][href!="#"]';
    
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
                    	zenarioA.addLinkStatus($el, 'spare_domain');
                    }
                }
            }
            
            if (relativePath) {
                //Make sure link is to a content item (following .htaccess rules for aliases)
                //and not a link to something like the admin login or Organizer.
                requestURI = relativePath.split('?')[0].split('#')[0];
                
                if (!requestURI.match(/\/(admin|public|private|zenario|zenario_custom|zenario_extra_modules|purchased_downloads)\//)
                 && !requestURI.match(/\/(admin|organizer)\.php/)) {
                    if (match = requestURI.match(/^([\/,A-Za-z0-9~_-]+)(|\.htm|\.html|\.download|download=1)$/)) {
                        resolvedURL = '/?cID=' + match[1];
                    } else {
                        resolvedURL = relativePath;
                    }
                    
                    //Store this link and a reference of its jquery object
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
                zenarioA.addLinkStatus($links[i][j], statuses[i]);
            }
        }
		
		//If there are any open tinyMCE editors, remove any icons we just added to them
		if (window.tinyMCE
		 && tinyMCE.editors) {
			zenario.removeLinkStatus($('div.mce-content-body'));
		}
    });
};


var lsCount = 0;
zenarioA.addLinkStatus = function($el, status) {
    
    var code = 'link_status__' + status,
    	msg = phrase[code],
    	thisId = 'zenario_link_status__' + ++lsCount;
    
    $el.append(_$html('del', 'class', 'zenario_link_status zenario_' + code, 'id', thisId, _$html('del')));
    
    if (msg) {
    	zenarioA.tooltips('#' + thisId + ' > del', {content: msg, items: '*'});
	}
};


zenarioA.init = function(
	cVersion,
	adminId,
	
	toolbar,
	pageMode,
	showGridOn,
	siteSettings,
	adminSettings,
	adminPrivs,
	importantGetRequests,
	adminHasSpecificPerms,
	adminHasSpecificPermsOnThisPage,
	lang,
	spareDomains,
	draftMessage,
	showEmptySlotsOn
) {
	zenario.cVersion = cVersion;
	zenario.adminId = adminId;
	
	zenarioA.toolbar = toolbar;
	zenarioA.pageMode = pageMode;
	zenarioA.showGridOn = showGridOn;
	zenarioA.showEmptySlotsOn = showEmptySlotsOn;
	zenarioA.siteSettings = siteSettings;
	zenarioA.adminSettings = adminSettings;
	zenarioA.adminPrivs = adminPrivs;
	zenarioA.importantGetRequests = importantGetRequests;
	zenarioA.adminHasSpecificPerms = adminHasSpecificPerms;
	zenarioA.adminHasSpecificPermsOnThisPage = adminHasSpecificPermsOnThisPage;
	zenarioA.lang = lang;
	zenarioA.spareDomains = spareDomains;
	zenarioA.draftMessage = draftMessage;
	
	//Add CSS classes for every priv needed in JavaScript
	var priv, hasPriv;
	foreach (adminPrivs as priv => hasPriv) {
		zenarioL.set(hasPriv, priv, '_NO' + priv);
	}
	
	
	
	//If this is admin mode, or the admin login screen, prepare some of the admin mode widgets.
	//(Note that some plugins include this library file on front-end pages even when not in admin mode;
	// in this case there's no need to add the widgets.)
	if (zenario.adminId || !zenario.cID) {
		zenario.inAdminMode = true;
	
		$(document).ready(function() {
			zenarioT.disableFileDragDrop(document.body);
	
			//If this is the front-end with a cID, call the link-checker function
			if (zenario.cID) {
				zenarioA.setSlotParents();
				zenarioA.scanHyperlinksAndDisplayStatus();
			}
		});

		//Append the HTML for the floating boxes in admin mode
		$('body').append(zenarioT.microTemplate('zenario_floating_boxes', {}));
	}
};



//Calculate function short names, we need to do this before calling any functions!
zenario.shrtNms(zenarioA);


});
});