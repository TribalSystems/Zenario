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
	zenario, zenarioA, zenarioAB, zenarioAT, zenarioO,
	get, engToBoolean, htmlspecialchars, ifNull, jsEscape, phrase,
	extensionOf, methodsOf
) {
	"use strict";
	
	

	/**
	  * This section lists important JavaScript functions from the core CMS in Admin Mode
	  * Other functions are tucked away in the /js folder
	 */
	//Wrapper to the underscore.js function's template library
	zenarioA.microTemplate = function(template, data, i) {
		if (template === undefined || !data) {
			return '';
	
		} else if (_.isArray(data)) {
			var l = data.length,
				html = '';
			for (var j = 0; j < l; ++j) {
				html += zenarioA.microTemplate(template, data[j], j);
			}
			return html;
		}
	
		if (data.i === undefined && i !== undefined) {
			data.i = 1*i;
		}
	
		if (template.length < 255 && zenarioA.microTemplates[template]) {
			//Named templates from one of the js/microtemplate directories
			//The template name is taken from the filename
			if (typeof zenarioA.microTemplates[template] == 'string') {
				try {
					var tmp = $.extend({}, _.templateSettings, true);
					//_.templateSettings = {variable: 'm', escape: /\[\[(.+?)\]\]/g, interpolate: /\{\{(.+?)\}\}/g, evaluate: /<%([\s\S]+?)%>/g};
					_.templateSettings = {variable: 'm', escape: false, interpolate: /\{\{(.+?)\}\}/g, evaluate: /[<\{]%([\s\S]+?)%[>\}]/g, twigStyleSyntax: true};
			
						zenarioA.microTemplates[template] = _.template(zenarioA.microTemplates[template]);
			
					_.templateSettings = tmp;
				} catch (e) {
					console.log('Error in template ' + template + ': \n\n' + zenarioA.microTemplates[template]);
					throw e;
				}
			}
		
			return zenarioA.microTemplates[template](data);
	
		} else {
			//Custom/one-off templates
			var checksum = 'microtemplate_' + hex_md5(template);
			
			if (zenarioA.microTemplates[checksum] === undefined) {
				zenarioA.microTemplates[checksum] = template;
			}
			
			return zenarioA.microTemplate(checksum, data, i);
		}
	};


	zenarioA.showAJAXLoader = function() {
		$(document.body).addClass('zenario_adminAJAXLoaderOpen');
		zenarioA.openBox('<div></div>', 'zenario_fbAdminAJAXLoader', 'AdminAJAXLoader', false, false, 50, 1, true, true, false, false);
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

	zenarioA.showMessage = function(message, buttonsHTML, messageType, modal, htmlEscapeMessage) {
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
	
		//Reload Storekeeper
		if (message.substr(0, 25) == '<!--Reload_Storekeeper-->' && zenarioO.init && !window.zenarioOQuickMode && !window.zenarioOSelectMode) {
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
			buttonsHTML = '<input class="submit_selected" type="button" onclick="document.location.href = document.location.href; return false;" value="' + message.substr(18, end - 18) + '">';
			message = message.substr(end + 3);
	
		} else if (message.substr(0, 17) == '<!--Logged_Out-->') {
			
			//If the admin has been logged out, check to see whether this window is in an iframe, and show the login window in the iframe if possible.
			if (zenarioA.loggedOutIframeCheck(message, messageType)) {
				return;
			}
			
			if (zenarioO.init
			 && zenarioO.path
			 && zenarioA.isFullOrganizerWindow) {
				buttonsHTML =
					'<input type="button" value="' + phrase.login + '" class="submit_selected" onclick="zenarioO.reloadPage(undefined, true);">' +
					'<input type="button" class="submit" value="' + phrase.cancel + '" onclick="zenario.goToURL(URLBasePath);"/>';
			} else {
				buttonsHTML = 
					'<input type="button" value="' + phrase.login + '" class="submit_selected"' +
					' onclick="zenario.goToURL(zenario.linkToItem(zenario.cID, zenario.cType, zenarioA.importantGetRequests, true));">' +
					'<input type="button" class="submit" value="' + phrase.cancel + '" onclick="zenario.goToURL(zenario.linkToItem(zenario.cID, zenario.cType, zenarioA.importantGetRequests));"/>';
		
			}
			
			message = message.substr(17);
			modal = true;
		}
	
		//Attempt to strip out JSON encoded data from error messages that would cause confusion
		if (messageType == 'error' && (end = message.indexOf('{"')) != -1) {
			try {
				var data = JSON.parse(message.substr(end));
			
				if (typeof data != 'object') {
					throw 0;
				}
			
				//If there was JSON encoded data, remove it and show the message without it
				message = message.substr(0, end);
			} catch (e) {
			}
		}
	
		zenarioA.floatingBox(message, buttonsHTML, messageType, modal, htmlEscapeMessage);
		return true;
	};


	zenarioA.notification = function(message) {
	
		get('zenario_notification').style.display = '';
		get('zenario_notification').innerHTML = '<div><h1>' + zenario.htmlspecialchars(message) + '</h1></div>';
	
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
	zenarioA.getSKItem = function(path, id) {
		var i,
			data,
			first = false,
			url =
				URLBasePath +
				'zenario/admin/ajax.php?_json=1&_start=0&_get_item_name=1&path=' + encodeURIComponent(path);
		
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
	
		if (data = zenario.checkSessionStorage(url, {}, true)) {
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

});