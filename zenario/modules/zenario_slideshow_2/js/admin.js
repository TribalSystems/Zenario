// Open the slideshow image manager
zenario_slideshow_2.openImageManager = function(el, slotName, AJAXLink) {
	el.blur();
	
	var that = this;
	this.AJAXLink = AJAXLink;
	this.slotName = slotName;
	this.errors = {};
	this.tempSlideId = 1;
	this.uploadChangeImage = false;
	
	// Get slideshow details
	zenario.ajax(AJAXLink + "&mode=get_details").after(function(data) {
		data = JSON.parse(data);
		that.data = data;
		that.slides = data.slides;
		that.tabs = data.tabs;
		that.dataset_fields = data.dataset_fields;
		that.mobile_option = data.mobile_option;
		
		if (_.isEmpty(data.slides)) {
			data.slides = {};
		}
		
		// Convert object to array for microtemplate
		var slidesArray = _.toArray(that.slides);
		slidesArray.sort(function (a, b) {
			return a.ordinal - b.ordinal;
		});
		
		
		if (that.slides.length == 0) {
			that.slides = {};
			that.current = 0;
		} else {
			that.current = slidesArray[0].id;
		}
		
		var html,
			width = 1000,
			instanceId = zenario.slots[slotName].instanceId,
			mergeFields = {
				slotName: slotName,
				instanceId: instanceId,
				slideDetails: slidesArray
				//slide: that.data.slides[that.current]
			};
		if (data.recommededSize) {
			mergeFields.recommededSize = true;
			mergeFields.recommededSizeMessage = data.recommededSizeMessage;
		}
		
		html = zenarioT.microTemplate('zenario_image_manager', mergeFields);
		
		// zenarioA.openBox function parameters
		//zenarioA.openBox = function(html, className, n, e, width, left, top, disablePageBelow, overlay, draggable, resizable, padding, maxHeight, rightCornerOfElement, bottomCornerOfElement)
		zenarioA.openBox(html, 'zenario_fbAdminImageManager', 'AdminImageManager', undefined, width, undefined, 2, true, true, '.zenario_dragMe', false, false);
		
		// Make list draggable
		that.makeListSortable();
		
		// Save slide initial order
		that.tempSaveOrder();
		
		// Load data
		that.selectSlide(that.current, true);
		
		// Set html 5 drag/drop upload going
		if (zenarioT.canDoHTML5Upload()) {
			zenarioT.setHTML5UploadFromDragDrop(
				that.AJAXLink, 
				{
					mode: 'file_upload'
				},
				false,
				that.uploadImagesCallback,
				document.getElementById('zenario_slideshow_drop_target'));
		}
		
		that.resize();
		that.size();
		
		// TODO: Improve this!
		$('input').change(function() {  
			that.somethingChanged = true; 
		});
		
	});
};

// Select a slide
zenario_slideshow_2.selectSlide = function(slideId, noSave) {
	var that = this;
	if (slideId && this.data.slides[slideId]) {
		// Save old slide
		if (!noSave) {
			this.tempSaveData(this.current);
		}
		
		this.highlightSlide(slideId);
		
		// Show new slide
		var slide = this.data.slides[slideId];
		
		if (slide.target_loc == 'internal' && !slide.content_item_tag_id) {
			slide.content_item_link = "Nothing selected";
		}
		
		// Show placeholder image when no rollover or mobile images
		if (!slide.rollover_image_id) {
			slide.rollover_image_src_thumbnail_1 = 'zenario/modules/zenario_slideshow_2/images/image-in-slot-placeholder.png';
			slide.r_alt_tag = 'No image';
		}
		if (!slide.mobile_image_id) {
			slide.mobile_image_src_thumbnail_1 = 'zenario/modules/zenario_slideshow_2/images/image-in-slot-placeholder.png';
			slide.m_alt_tag = 'No image';
		}
		
		// Make sure checkbox values are boolean
		slide.open_in_new_window = zenario.engToBoolean(slide.open_in_new_window);
		slide.link_to_translation_chain = zenario.engToBoolean(slide.link_to_translation_chain);
		slide.use_transition_code = zenario.engToBoolean(slide.use_transition_code);
		slide.hidden = zenario.engToBoolean(slide.hidden);
		
		slide.has_tabs = that.tabs
		
		var createSelectList = function(values, selected) {
			var selectList = [];
			for (key in values) {
				if (values.hasOwnProperty(key)) {
					if (key == selected) {
						values[key].selected = true;
					}
					values[key].value = key;
					selectList.push(values[key]);
				}
			}
			selectList.sort(sortByOrd);
			return selectList;
		}
		var sortByOrd = function(a, b) {
			if (+a.ord < +b.ord)
				return -1;
			if (+a.ord > +b.ord)
				return 1;
			return 0;
		}
		
		// Show select lists
		var list = {};
		list = {
			none: {
				label: "Don't link",
				ord: 1
			},
			internal: {
				label: "Internal - link to a content item",
				ord: 2
			},
			external: {
				label: "External - link to a full URL",
				ord: 3
			}
		};
		slide.target_loc_list = createSelectList(list, slide.target_loc);
		
		list = {
			everyone: {
				label: 'Everyone',
				ord: 1
			},
			logged_in: {
				label: 'Logged-in users',
				ord: 2
			},
			logged_out: {
				label: 'Anonymous visitors',
				ord: 3
			},
			logged_in_with_field: {
				label: 'Logged-in users with dataset field',
				ord: 4
			},
			logged_in_without_field: {
				label: 'Logged-in users without dataset field',
				ord: 5
			},
			call_static_method: {
				label: "Call a module's static method (advanced)",
				ord: 6
			}
		};
		slide.slide_visibility_list = createSelectList(list, slide.slide_visibility);
		
		this.dataset_fields[''] = {
			label: '-- Select --',
			ord: 0
		}
		slide.field_id_list = createSelectList(this.dataset_fields, slide.field_id);
		
		this.displaySlideDetails(slide);
		this.current = slideId;
	} else if (slideId === 0) {
		this.displaySlideDetails({id: slideId});
	}
	
	this.size();
};

// Show a slides details
zenario_slideshow_2.displaySlideDetails = function(slide) {
	var html = zenarioT.microTemplate('zenario_slide_details', slide);
	$('#zenario_slide_attributes_inner').html(html);
	this.bindEventListeners();
};

zenario_slideshow_2.highlightSlide = function(slideId) {
	if (slideId && this.data.slides[slideId]) {
		$("#zenario_sortable li#zenario_slide_image_" + this.current)
			.css("border","none")
			.removeClass("slideshow_selected_image");
		$("#zenario_sortable li#zenario_slide_image_" + slideId)
			.css("border","solid 2px red")
			.addClass("slideshow_selected_image");
	}
};

// Save data entered for current slide when viewing different slide
zenario_slideshow_2.tempSaveData = function(slideId) {
	if (slideId && this.data.slides[slideId]) {
		// Get form data
		var data = new Array;
		serial = $('#zenario_slide_settings_form').serializeArray();
		for (var i in serial) {
			if (serial.hasOwnProperty(i)) {
				data[serial[i].name] = serial[i].value;
			}
		}
		
		// Add checkboxes if missing (serializeArray does not include unchecked checkboxes)
		var checkboxFields = ['open_in_new_window', 'link_to_translation_chain', 'use_transition_code', 'hidden'];
		for (var name in checkboxFields) {
			if (checkboxFields.hasOwnProperty(name)) {
				if (data[checkboxFields[name]] === undefined) {
					data[checkboxFields[name]] = 0;
				}
			}
		}
		
		for (var name in data) {
			if (data.hasOwnProperty(name)) {
				var value = data[name];
				// Convert checkboxes to boolean
				if ($.inArray(name, checkboxFields) != -1) {
					value = zenario.engToBoolean(value);
				}
				this.data.slides[slideId][name] = value;
			}
		}
	}
};

zenario_slideshow_2.tempSaveOrder = function() {
	this.slideOrderedIds = $('#zenario_sortable').sortable('toArray', {attribute: 'data-id'});
};


zenario_slideshow_2.closeImageManager = function(save) {
	var message = 'You are currently editing this floating admin box. If you leave now you will loose any unsaved changes.';
	if (save || !this.somethingChanged || confirm(message)) {
		if (this.sizing) {
			clearTimeout(this.sizing);
		}
		
		var data = {
			mode: "close_image_manager",
			save: (save == undefined) ? 0 : 1
		};
		zenario.nonAsyncAJAX(this.AJAXLink, data);
		this.somethingChanged = false;
		zenarioA.closeBox('AdminImageManager');
	}
};

// Bind events to image changer popup box
zenario_slideshow_2.bindEventListenersImageChanger = function() {
	var that = this;
	$("#zenario_organizer_option").off().on("click", function() {
		zenarioA.closeBox('AdminImageManager_ImageChanger');
		that.organizerChangeImage();
	});
	$("#zenario_upload_option").off().on("click", function() {
		zenarioA.closeBox('AdminImageManager_ImageChanger');
		that.uploadChangeImage = true;
		that.uploadImages();
	});
	$('#zenario_close_image_changer').off().on('click', function() {
		zenarioA.closeBox('AdminImageManager_ImageChanger');
	})
};

// Bind events to transition codes popup box
zenario_slideshow_2.bindEventListenersTransitionCodes = function() {
	var that = this;
	$('#zenario_transition_code').change(function() {
		that.somethingChanged = true;
	});
	$('#zenario_transition_code').keyup(function() {
		that.somethingChanged = true;
	});
	$('#zenario_close_transition_code_box').off().on('click', function() {
		that.slides[that.current].transition_code = $('#zenario_transition_code').val();
		zenarioA.closeBox('AdminImageManager_TransitionCodes');
	});
};

// Bind events to main form
zenario_slideshow_2.bindEventListeners = function() {
	
	var that = this;
	
	// Open popup box to set transition code
	$('#zenario_show_transition_box').off().on('click', function() {
		var mergeFields = {
			code: that.slides[that.current].transition_code};
		var html = zenarioT.microTemplate('zenario_transition_code_box', mergeFields);
		zenarioA.openBox(html, 'zenario_fbAdminImageManager_TransitionCodes', 'AdminImageManager_TransitionCodes', undefined, 200, undefined, undefined, true, true, '.zenario_dragMe', false);
		that.bindEventListenersTransitionCodes();
	});
	
	// Open popup box to choose organizer or upload directly
	$("#zenario_change_image").off().on("click", function(e, image_type) {
		that.rememberImageType = image_type;
		var html = zenarioT.microTemplate('zenario_image_changer', {});
		zenarioA.openBox(html, 'zenario_fbAdminImageManager_ImageChanger', 'AdminImageManager_ImageChanger', undefined, 200, undefined, undefined, true, true, '.zenario_image_changer', false);
		that.bindEventListenersImageChanger();
	});
	$("#zenario_change_rollover_image").off().on("click", function() {
		$("#zenario_change_image").trigger("click", "rollover");
	});
	$("#zenario_change_mobile_image").off().on("click", function() {
		$("#zenario_change_image").trigger("click", "mobile");
	});
	
	// Button to remove rollover image
	$("#zenario_remove_rollover_image").off().on("click", function() {
		var current = that.slides[that.current];
		current.r_filename =
		current.r_alt_tag =
		current.r_height =
		current.r_width = 
		current.true_r_width =
		current.true_r_height =
		current.rollover_image_id = 
		current.rollover_image_src = 
		current.rollover_image_src_thumbnail_1 =
		current.rollover_overwrite_alt_tag = null;
		that.selectSlide(that.current);
		that.somethingChanged = true;
	});
	
	// Button to remove mobile image
	$("#zenario_remove_mobile_image").off().on("click", function() {
		var current = that.slides[that.current];
		current.m_filename =
		current.m_alt_tag =
		current.m_height =
		current.m_width =
		current.true_m_width =
		current.true_m_height =
		current.mobile_image_id = 
		current.mobile_image_src = 
		current.mobile_image_src_thumbnail_1 =
		current.mobile_overwrite_alt_tag = 
		current.mobile_tab_name =
		current.mobile_slide_title =
		current.mobile_slide_extra_html = null;
		that.selectSlide(that.current);
		that.somethingChanged = true;
	});
	
	// Main/Rollover/Mobile Image tabs
	$("#zenario_edit_main_image").off().on("click", function() {
		that.rememberImageType = "";
		$('div.slide_tab').removeClass('current');
		$(this).addClass('current');
		$("#zenario_main_image_details").show();
		$("#zenario_rollover_image_details").hide();
		$("#zenario_mobile_image_details").hide();
	});
	$("#zenario_edit_rollover_image").off().on("click", function() {
		that.rememberImageType = "rollover";
		$('div.slide_tab').removeClass('current');
		$(this).addClass('current');
		$("#zenario_main_image_details").hide();
		$("#zenario_rollover_image_details").show();
		$("#zenario_mobile_image_details").hide();
	});
	
	if (this.mobile_option == 'seperate_fixed') {
		$("#zenario_edit_mobile_image").off().on("click", function() {
			that.rememberImageType = "mobile";
			$('div.slide_tab').removeClass('current');
			$(this).addClass('current');
			$("#zenario_main_image_details").hide();
			$("#zenario_rollover_image_details").hide();
			$("#zenario_mobile_image_details").show();
		});
	} else {
		$("#zenario_edit_mobile_image")
			.text('Mobile Image (Disabled in properties)')
			.addClass('disabled');
	}
	
	// Slide link picker
	$("#zenario_select_link_type").off().on("change", function () {
		
		that.selectSlide(that.current);
	});
	
	// Slide visibility selector
	
	$("#zenario_select_slide_visibility").off().on("change", function() {
		
		that.selectSlide(that.current);
	});
	
	
	// Add prefix to URL box
	$("#zenario_external_link").off().on("blur", function() {
		if(this.value == 'http://') { this.value = '' };
	});
	$("#zenario_external_link").on("focus", function() {
		if(!this.value) { this.value = 'http://' };
	});
	
	var saveFunction = function(close) {
		
		// Save current slides data locally in case anything new was added
		that.tempSaveData(that.current);
		// Pass local data to server to attempt to save
		data = {
			mode: "save_slides",
			slides: JSON.stringify(that.data.slides),
			ordinals: that.slideOrderedIds.join(',')
		};
		
		zenario.ajax(zenario.addBasePath(that.AJAXLink), data).after(function(data) {
			that.errors = JSON.parse(data);
			if (_.isEmpty(that.errors)) {
				zenario.refreshPluginSlot(that.slotName, 'lookup');
				$('#zenario_slideshow_error_display').html('');
			} else {
				var html = '';
				for (key in that.errors) {
					if (that.errors.hasOwnProperty(key)) {
						that.errors[key].forEach(function(error) {
							html += '<div class="error">Slide ' + (+key + 1) + ': ' + error + '</div>';
						});
					}
				}
				$('#zenario_slideshow_error_display').html(html);
				
			}
			if (close && _.isEmpty(that.errors)) {
				that.closeImageManager("save");
			}
		});
	};
	
	// Save and continue button
	$("#zenario_save_continue").off().on("click", function() {
		saveFunction(false);
	});
	
	// Save and close button
	$("#zenario_save_close").off().on("click", function() {
		saveFunction(true);
	});
};

zenario_slideshow_2.organizerChangeImage = function() {
	var path = 'zenario__content/panels/image_library';
	zenarioA.organizerSelect(
		'zenario_slideshow_2', 'organizerChangeImageCallback', false,
		path, path, path, path, true);
};

zenario_slideshow_2.organizerChangeImageCallback = function(path, key, row) {
	var image_type = this.rememberImageType;
	var data = zenario.nonAsyncAJAX(this.AJAXLink + "&mode=change_image_from_organizer&new_image_id=" + key.id, false, true);
	var current = this.slides[this.current];
	
	switch(image_type) {
		case "rollover":
			current.r_filename = data.filename;
			current.r_alt_tag = data.alt_tag;
			
			current.rollover_image_src = data.image_src;
			current.rollover_image_src_thumbnail_1 = data.image_src_thumbnail_1;
			
			current.rollover_image_id = data.image_id;
			current.r_height = current.true_r_height = data.height;
			current.r_width = current.true_r_width = data.width;
			break;
		case "mobile":
			current.m_filename = data.filename;
			current.m_alt_tag = data.alt_tag;
			
			current.mobile_image_src = data.image_src;
			current.mobile_image_src_thumbnail_1 = data.image_src_thumbnail_1;
			
			current.mobile_image_id = data.image_id;
			current.m_height = current.true_m_height = data.height;
			current.m_width = current.true_m_width = data.width;
			break;
		default:
			current.filename = data.filename;
			current.alt_tag = data.alt_tag;
			
			current.image_src = data.image_src;
			current.image_src_thumbnail_2 = data.image_src_thumbnail_2;
			current.image_src_thumbnail_1 = data.image_src_thumbnail_1;
			
			current.image_id = data.image_id;
			current.true_height = current.height = data.height;
			current.true_width = current.width = data.width;
			$("#zenario_slide_" + this.current).attr({"src": current.image_src_thumbnail_2, "alt": current.alt});
			break;
	}
	
	this.selectSlide(this.current);
	this.rememberImageType = undefined;
};

zenario_slideshow_2.organizerPickUserCharacteristicCallback = function(path, key, row) {
	$("#zenario_user_characteristic").val(row.name);
};

zenario_slideshow_2.pickContentItemLinkFromOrganizer = function() {
	var path = 'zenario__content/panels/content';
	if ($("#zenario_content_item_id").val()) {
		path = 'zenario__content/panels/content//'+$("#zenario_content_item_id").val();
	}
	
	zenarioA.organizerSelect(
		'zenario_slideshow_2', 'organizerPickContentItemLinkCallback', false,
		path, 
		'zenario__content/panels/content',
		'zenario__content/panels/content', 
		false, 
		true);
};

zenario_slideshow_2.organizerPickContentItemLinkCallback = function(path, key, row) {
	$("#zenario_content_item_link").val(row.tag);
	$("#zenario_content_item_id").val(key.id);
};

zenario_slideshow_2.pickImagesFromOrganizer = function() {
	var path = 'zenario__content/panels/image_library';
	
	//zenarioA.organizerSelect(
	//	callbackObject, callbackFunction, enableMultipleSelect,
	//	path, targetPath, minPath, maxPath, disallowRefinersLoopingOnMinPath,
	zenarioA.organizerSelect(
		'zenario_slideshow_2', 'organizerPickImagesCallback', true,
		path, path, path, path, true);
};

zenario_slideshow_2.uploadImages = function() {
	var that = this;
	
	var requests = 
		{
			mode: "file_upload"
		};
	
	var fallback = !zenarioT.canDoHTML5Upload(),
		html = '<input type="file" name="Filedata" accept="image/*"';
	
	if (fallback) {
		requests._html5_backwards_compatibility_hack = 1;
		html += ' id="zenario_fallback_fileupload"';
	}
	
	if (!fallback) {
		html += ' multiple';
	}
	
	html += '/>';
	
	if (!fallback) {
		var $input = $(html);
		$input.change(function() {
			zenarioT.doHTML5Upload(this.files, that.AJAXLink, requests, function(responses) {
				that.uploadImagesCallback(responses);
			});
			
		});
		$input.click();
		return;
		
	} else {
		//For backwards compatability for browsers without html5, attempt to convert file upload tags into ajax->confirm->form tags
		//Start generating the box.
		//If there is a form, the message should be surrounded by <form></form> tags.
		var buttonsHTML = '';
		html = 
			'<form id="zenario_bc_form" action="' + htmlspecialchars(this.AJAXLink) + '"' +
				' onsubmit="/*...*/"' +
				' target="zenario_iframe" method="post" enctype="multipart/form-data">'
				+ html;
		
		for (var r in requests) {
			html += '<input type="hidden" value="' + htmlspecialchars(requests[r]) + '" name="' + htmlspecialchars(r) + '"/>';
		}
		
		html += '</form>';
			
		buttonsHTML =
			'<input type="button" class="submit_selected" value="' + zenarioA.phrase.upload + '" onclick="get(\'zenario_bc_form\').submit();"/>';
		
		buttonsHTML +=
			'<input type="button" class="submit" value="' + zenarioA.phrase.cancel + '"/>';
		
		
		zenarioA.showMessage(html, buttonsHTML/*, undefined, undefined, !isHTML*/);
		
		//Try to click the fileupload prompt straight away...
		//...except on IE, where this causes an error :'(
		if (!zenario.browserIsIE()) {
			$('#zenario_fallback_fileupload').click();
		}
	}
};

// Called after image is added from organizer
zenario_slideshow_2.organizerPickImagesCallback = function(path, key, row) {
	var that = this;
	this.somethingChanged = true;
	var data = zenario.nonAsyncAJAX(this.AJAXLink + "&mode=add_slides_from_organizer&ids=" + key.id, false, true),
		newIds = [];
	data.forEach(function(newSlide){
		var id = "t" + that.tempSlideId;
		newIds.push(id);
		that.data.slides[id] = newSlide;
		that.addGenericImageAttributes(id);
		
		that.addSlideToList(that.data.slides[id]);
		that.tempSlideId++;
	});
	this.addNewImagesCallback(newIds);
};

// Called after a new slide is uploaded
zenario_slideshow_2.uploadImagesCallback = function(responses) {
	var that = this;
	this.somethingChanged = true;
	// If changing an image
	if (this.uploadChangeImage) {
		var current = this.slides[this.current];
		switch(this.rememberImageType) {
			case "rollover":
				current.r_filename = responses[0].filename;
				current.r_alt_tag = responses[0].filename;
				current.rollover_image_src = responses[0].link;
				current.rollover_image_src_thumbnail_1 = responses[0].link + "&height=150&width=300";
				current.rollover_image_id = 0;
				current.r_height = current.true_r_height = responses[0].height;
				current.r_width = current.true_r_width = responses[0].width;
				current.r_cache_id = responses[0].id;
				break;
			case "mobile":
				current.m_filename = responses[0].filename;
				current.m_alt_tag = responses[0].filename;
				current.mobile_image_src = responses[0].link;
				current.mobile_image_src_thumbnail_1 = responses[0].link + "&height=150&width=300";
				current.mobile_image_id = 0;
				current.m_height = current.true_m_height = responses[0].height;
				current.m_width = current.true_m_width = responses[0].width;
				current.m_cache_id = responses[0].id;
				break;
			default:
				current.filename = responses[0].filename;
				current.alt_tag = responses[0].filename;
				current.image_src = responses[0].link;
				current.image_src_thumbnail_1 = responses[0].link + "&height=150&width=300";
				current.image_src_thumbnail_2 = responses[0].link + "&height=150&width=150";
				current.image_id = 0;
				current.true_height = current.height = responses[0].height;
				current.true_width = current.width = responses[0].width;
				current.cache_id = responses[0].id;
				
				$("#zenario_slide_" + this.current).attr({"src": current.image_src_thumbnail_1, "alt": current.alt});
				
				break;
		}
		this.selectSlide(this.current);
		
		this.uploadChangeImage = false;
		this.rememberImageType = undefined;
	
	// If creating a new image
	} else {
		var newIds = [];
		responses.forEach(function(newSlide) {
			var id = "t" + that.tempSlideId;
			newIds.push(id);
			that.data.slides[id] = {};
			var slide = that.data.slides[id];
			that.addGenericImageAttributes(id);
			
			slide.filename = newSlide.filename;
			slide.alt_tag = newSlide.filename;
			slide.width = 
			slide.true_width = newSlide.width;
			slide.height = 
			slide.true_height = newSlide.height;
			
			slide.image_src = newSlide.link;
			slide.image_src_thumbnail_1 = newSlide.link + "&height=150&width=300";
			slide.image_src_thumbnail_2 = newSlide.link + "&height=150&width=150";
			
			slide.image_id = 0;
			slide.cache_id = newSlide.id;
			that.addSlideToList(slide);
			that.tempSlideId++;
		});
		this.addNewImagesCallback(newIds);
	}
	
};

zenario_slideshow_2.addGenericImageAttributes = function(id) {
	var slide = this.data.slides[id];
	slide.id = id;
	slide.overwrite_alt_tag = "";
	slide.tab_name = "";
	slide.slide_title = "";
	slide.slide_extra_html = "";
	slide.slide_more_link_text = "";
	slide.rollover_overwrite_alt_tag = "";
	slide.mobile_overwrite_alt_tag = "";
	slide.mobile_tab_name = "";
	slide.mobile_slide_title = "";
	slide.mobile_slide_extra_html = "";
	slide.target_loc = "none";
	slide.dest_url = "";
	slide.open_in_new_window = 0;
	slide.slide_visibility = "everyone";
	slide.plugin_class = "";
	slide.method_name = "";
	slide.param_1 = "";
	slide.param_2 = "";
	slide.field_id = 0;
	slide.link_to_translation_chain = "";
	slide.transition_code = "";
	slide.use_transition_code = 0;
	slide.hidden = 0;
};

zenario_slideshow_2.addSlideToList = function(slide) {
	var html = 
		'<li id="zenario_slide_image_' + slide.id + '" data-id="' + slide.id + '"> \
			<div onclick="zenario_slideshow_2.selectSlide(\'' + slide.id + '\', true);"> \
				<img src="' + slide.image_src_thumbnail_2 + '" alt="' + slide.alt_tag + '" id="zenario_slide_' + slide.id + '"/> \
			</div> \
		</li>';
	$("#zenario_sortable").append(html);
};

zenario_slideshow_2.addNewImagesCallback = function(newIds) {
	this.tempSaveOrder();
	
	if (this.current == 0) {
		this.selectSlide(this.slideOrderedIds[0]);
	}
	
};

zenario_slideshow_2.makeListSortable = function() {
	var that = this;
	$("#zenario_sortable").sortable({
		// Save ordinals locally when order is changed
		update: function(event, ui) {
			that.tempSaveOrder();
		}
	});
};

// Delete currently selected slide
zenario_slideshow_2.deleteSlide = function() {
	// Check theres a slide to delete
	if (!_.isEmpty(this.data.slides)) {
		this.somethingChanged = true;
		var id = this.current,
			nextImageOrdinal = ((this.slideOrderedIds.indexOf(id) - 1) >= 0) ? (this.slideOrderedIds.indexOf(id) - 1) : 0;
		
		delete this.data.slides[id];
		$("li#zenario_slide_image_" + id).remove();
		this.tempSaveOrder();
		
		// If the last slide has been deleted
		if (_.isEmpty(this.data.slides)) {
			this.current = 0;
			nextSlideId = 0;
		} else {
			nextSlideId = this.slideOrderedIds[nextImageOrdinal];
		}
		
		this.selectSlide(nextSlideId);
	}
};


// Select last image
zenario_slideshow_2.selectLastImage = function() {
	if (!_.isEmpty(this.data.slides)) {
		var length = this.slideOrderedIds.length - 1;
		this.selectSlide(this.slideOrderedIds[length]);
	}
};
// Select first image
zenario_slideshow_2.selectFirstImage = function() {
	if (!_.isEmpty(this.slides)) {
		this.selectSlide(this.slideOrderedIds[0]);
	}
};
// Move the selected image one place up
zenario_slideshow_2.nudgeImageUp = function() {
	if (!_.isEmpty(this.data.slides)) {
		var current = $(".slideshow_selected_image");
		current.prev().before(current);
		this.tempSaveOrder();
	}
};
// Move the selected image one place down
zenario_slideshow_2.nudgeImageDown = function() {
	if (!_.isEmpty(this.data.slides)) {
		var current = $(".slideshow_selected_image");
		current.next().after(current);
		this.tempSaveOrder();
	}
};

zenario_slideshow_2.resize = function() {
	maxHeight = 800;
	if (this.sizing) {
		clearTimeout(this.sizing);
	}
	
	if (get('zenario_fbAdminInner')) {
		var height = Math.floor($(window).height() * 0.96 - 150);
		if (height > maxHeight) {
			height = maxHeight;
		}
		
		if ((height = (1*height)) > 0) {
			get('zenario_fbAdminInner').style.height = height + 'px';
			get('zenario_slides').style.height = get('zenario_slide_attributes').style.height = (height - 60) + 'px';
			
		}
	}
	this.sizing = setTimeout(zenario_slideshow_2.resize, 250);
};

zenario_slideshow_2.size = function() {
	var $det = $('#zenario_main_image_details'),
		$rol = $('#zenario_rollover_image_details'),
		$mob = $('#zenario_mobile_image_details'),
		$sett = $('#zenario_slide_settings'),
		maxHeight;
		
	$det.css('height', 'auto');
	$rol.css('height', 'auto');
	$mob.css('height', 'auto');
	$sett.css('height', 'auto');
	maxHeight = Math.max($det.height(), $rol.height(), $mob.height(), $sett.height());
	
	$det.height(maxHeight);
	$rol.height(maxHeight);
	$mob.height(maxHeight);
	$sett.height(maxHeight);
	
};