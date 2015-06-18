zenario_slideshow_2.AJAXLink = '';
// Saves data about all slides.
zenario_slideshow_2.slides = {};
// Saves the ID and ordinal of the current image
//zenario_slideshow_2.selectedImage;
//zenario_slideshow_2.slideOrderedIds;
zenario_slideshow_2.tempSlideId = 1;
zenario_slideshow_2.uploadChangeImage = false;

zenario_slideshow_2.openImageManager = function(el, slotName, AJAXLink, fieldList) {
	el.blur();
	zenario_slideshow_2.selectedImage = 0;
	
	var details = zenario.nonAsyncAJAX(AJAXLink + "&mode=get_details", false, true);
	zenario_slideshow_2.slides = details.slides;
	zenario_slideshow_2.tabs = details.tabs;
	zenario_slideshow_2.dataset_fields = details.dataset_fields;
	zenario_slideshow_2.mobile_option = details.mobile_option;
	
	if (zenario_slideshow_2.slides.length == 0) {
		zenario_slideshow_2.slides = {};
	}
	
	var slidesArray = _.toArray(zenario_slideshow_2.slides);
	slidesArray.sort(function (a, b) {
		return a.ordinal - b.ordinal;
	});
	
	var html,
		width = 1000,
		instanceId = zenario.slots[slotName].instanceId,
		mergeFields = {
			slotName: slotName,
			instanceId: instanceId,
			slideDetails: slidesArray
		};
	
	zenario_slideshow_2.AJAXLink = AJAXLink;
	zenario_slideshow_2.slotName = slotName;
	
	html = zenarioA.microTemplate('zenario_image_manager', mergeFields);
	
	// zenarioA.openBox function parameters
	//zenarioA.openBox = function(html, className, n, e, width, left, top, disablePageBelow, overlay, draggable, resizable, padding, maxHeight, rightCornerOfElement, bottomCornerOfElement)
	zenarioA.openBox(html, 'zenario_fbAdminImageManager', 'AdminImageManager', undefined, width, undefined, 2, true, true, '.zenario_dragMe', false, false);
	
	// Set select list of groups and booleans for visibility
	var output = [];
	$.each(zenario_slideshow_2.dataset_fields, function(key, field) {
		output.push('<option value="', field.id,'">', field.label,'</option>')
	});
	$('#zenario_dataset_boolean_and_groups_only').append(output.join(''));
	
	if (!_.isEmpty(zenario_slideshow_2.slides)) {
		// Make list draggable
		zenario_slideshow_2.makeListSortable();
		// Save slide initial order
		zenario_slideshow_2.tempSaveOrder();
		// Select first image
		zenario_slideshow_2.selectImage(zenario_slideshow_2.slideOrderedIds[0]);
	}
	
	//Set html 5 drag/drop upload going
	if (zenarioA.canDoHTML5Upload()) {
		zenarioA.setHTML5UploadFromDragDrop(
			zenario_slideshow_2.AJAXLink, 
			{
				mode: 'file_upload'
			},
			false,
			zenario_slideshow_2.uploadImagesCallback,
			document.getElementById('zenario_slideshow_drop_target'));
	}
	
	zenario_slideshow_2.bindEventListeners();
	$("#zenario_select_link_type").change();
	$("#zenario_select_slide_visibility").change();
	
	zenario_slideshow_2.resize();
	zenario_slideshow_2.size();
	
	$('input').change(function() {  
		zenario_slideshow_2.somethingChanged = true; 
	});
	return false;
};

zenario_slideshow_2.closeImageManager = function(save) {
	var message = 'You are currently editing this floating admin box. If you leave now you will loose any unsaved changes.';
	if (save || !zenario_slideshow_2.somethingChanged || confirm(message)) {
		if (zenario_slideshow_2.sizing) {
			clearTimeout(zenario_slideshow_2.sizing);
		}
		
		var data = {
			mode: "close_image_manager",
			save: (save == undefined) ? 0 : 1
		};
		zenario.nonAsyncAJAX(zenario_slideshow_2.AJAXLink, data);
		zenario_slideshow_2.somethingChanged = false;
		zenarioA.closeBox('AdminImageManager');
	}
};

// Bind events to image changer popup box
zenario_slideshow_2.bindEventListenersImageChanger = function() {
	//console.log("bindEventListenersImageChanger");
	$("#zenario_organizer_option").off().on("click", function() {
		zenarioA.closeBox('AdminImageManager_ImageChanger');
		zenario_slideshow_2.organizerChangeImage();
	});
	$("#zenario_upload_option").off().on("click", function() {
		zenarioA.closeBox('AdminImageManager_ImageChanger');
		zenario_slideshow_2.uploadChangeImage = true;
		zenario_slideshow_2.uploadImages();
	});
	$('#zenario_close_image_changer').off().on('click', function() {
		zenarioA.closeBox('AdminImageManager_ImageChanger');
	})
};

// Bind events to transition codes popup box
zenario_slideshow_2.bindEventListenersTransitionCodes = function() {
	$('#zenario_transition_code').change(function() {
		zenario_slideshow_2.somethingChanged = true;
	});
	$('#zenario_transition_code').keyup(function() {
		zenario_slideshow_2.somethingChanged = true;
	});
	$('#zenario_close_transition_code_box').off().on('click', function() {
		zenario_slideshow_2.slides[zenario_slideshow_2.selectedImage].transition_code = $('#zenario_transition_code').val();
		zenarioA.closeBox('AdminImageManager_TransitionCodes');
	});
};

// Bind events to main form
zenario_slideshow_2.bindEventListeners = function() {
	
	// Open popup box to set transition code
	$('#zenario_show_transition_box').off().on('click', function() {
		var mergeFields = {
			code: zenario_slideshow_2.slides[zenario_slideshow_2.selectedImage].transition_code};
		var html = zenarioA.microTemplate('zenario_transition_code_box', mergeFields);
		zenarioA.openBox(html, 'zenario_fbAdminImageManager_TransitionCodes', 'AdminImageManager_TransitionCodes', undefined, 200, undefined, undefined, true, true, '.zenario_dragMe', false);
		zenario_slideshow_2.bindEventListenersTransitionCodes();
	});
	
	// Open popup box to choose organizer or upload directly
	$("#zenario_change_image").off().on("click", function(e, image_type) {
		zenario_slideshow_2.rememberImageType = image_type;
		var html = zenarioA.microTemplate('zenario_image_changer', {});
		zenarioA.openBox(html, 'zenario_fbAdminImageManager_ImageChanger', 'AdminImageManager_ImageChanger', undefined, 200, undefined, undefined, true, true, '.zenario_image_changer', false);
		zenario_slideshow_2.bindEventListenersImageChanger();
	});
	$("#zenario_change_rollover_image").off().on("click", function() {
		$("#zenario_change_image").trigger("click", "rollover");
	});
	$("#zenario_change_mobile_image").off().on("click", function() {
		$("#zenario_change_image").trigger("click", "mobile");
	});
	
	// Button to remove rollover image
	$("#zenario_remove_rollover_image").off().on("click", function() {
		var current = zenario_slideshow_2.slides[zenario_slideshow_2.selectedImage];
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
		zenario_slideshow_2.showForm(zenario_slideshow_2.selectedImage);
		zenario_slideshow_2.somethingChanged = true;
	});
	
	// Button to remove mobile image
	$("#zenario_remove_mobile_image").off().on("click", function() {
		var current = zenario_slideshow_2.slides[zenario_slideshow_2.selectedImage];
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
		zenario_slideshow_2.showForm(zenario_slideshow_2.selectedImage);
		zenario_slideshow_2.somethingChanged = true;
	});
	
	// Main/Rollover/Mobile Image tabs
	$("#zenario_edit_main_image").off().on("click", function() {
		zenario_slideshow_2.rememberImageType = "";
		$('div.slide_tab').removeClass('current');
		$(this).addClass('current');
		$("#zenario_main_image_details").show();
		$("#zenario_rollover_image_details").hide();
		$("#zenario_mobile_image_details").hide();
	});
	$("#zenario_edit_rollover_image").off().on("click", function() {
		zenario_slideshow_2.rememberImageType = "rollover";
		$('div.slide_tab').removeClass('current');
		$(this).addClass('current');
		$("#zenario_main_image_details").hide();
		$("#zenario_rollover_image_details").show();
		$("#zenario_mobile_image_details").hide();
	});
	
	if (zenario_slideshow_2.mobile_option == 'seperate_fixed') {
		$("#zenario_edit_mobile_image").off().on("click", function() {
			zenario_slideshow_2.rememberImageType = "mobile";
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
		if ($("#zenario_select_link_type").val() == 'none') {
			$("#zenario_static_method_settings").hide();
			$("#zenario_open_in_new_window_settings").hide();
			$("#zenario_open_in_new_window").attr('checked', false);
		} else {
			$("#zenario_open_in_new_window_settings").show();
		}
		if ($("#zenario_select_link_type").val() == 'internal') {
			$("#zenario_internal_link_settings").show();
			$("#zenario_link_to_translation_chain_settings").show();
			if ($("#zenario_content_item_id").val() == '') {
				$("#zenario_content_item_link").val("Nothing selected");
			}
		} else {
			$("#zenario_internal_link_settings").hide();
			$("#zenario_content_item_link").val("");
			$("#zenario_content_item_id").val("");
			$("#zenario_link_to_translation_chain_settings").hide();
			$("#zenario_link_to_translation_chain").attr('checked', false);
		}
		if ($("#zenario_select_link_type").val() == 'external') {
			$("#zenario_external_link_settings").show();
		} else {
			$("#zenario_external_link_settings").hide();
			$("#zenario_external_link").val("");
		}
	});
	
	// Slide visibility selector
	$("#zenario_select_slide_visibility").off().on("change", function() {
		if ($("#zenario_select_slide_visibility").val() == 'call_static_method') {
			$("#zenario_static_method_settings").show();
		} else {
			$("#zenario_static_method_settings").hide();
			$("#zenario_module_class_name").val('');
			$("#zenario_static_method_name").val('');
			$("#zenario_parameter_1").val('');
			$("#zenario_parameter_2").val('');
		}
		if ($.inArray(
				$("#zenario_select_slide_visibility").val(), 
				['logged_in_with_field', 'logged_in_without_field', 'without_field']) != -1) 
		{
			$("#zenario_user_field_settings").show();
		} else {
			$("#zenario_user_field_settings").hide();
		}
	});
	
	// Add prefix to URL box
	$("#zenario_external_link").off().on("blur", function() {
		if(this.value == 'http://') { this.value = '' };
	});
	$("#zenario_external_link").on("focus", function() {
		if(!this.value) { this.value = 'http://' };
	});
	
	var saveFunction = function() {
		$('#zenario_slideshow_error_display').empty();
		// Save current slides data locally in case anything new was added
		zenario_slideshow_2.tempSaveData(zenario_slideshow_2.slides[zenario_slideshow_2.selectedImage]);
		// Pass local data to server to attempt to save
		data = {
			mode: "save_slides",
			slides: JSON.stringify(zenario_slideshow_2.slides),
			ordinals: zenario_slideshow_2.slideOrderedIds.join(',')
		};
		
		$.ajax({
			async: false,
			type: 'POST',
			url: zenario.addBasePath(zenario_slideshow_2.AJAXLink),
			data: data,
			success: function(data) {
				zenario_slideshow_2.errors = JSON.parse(data);
				if (zenario_slideshow_2.errors.length == 0) {
					zenario.refreshPluginSlot(zenario_slideshow_2.slotName, 'lookup');
				} else {
					zenario_slideshow_2.errors.forEach(function(slideErrors, index) {
						slideErrors.forEach(function(error) {
							$('#zenario_slideshow_error_display').append('<div class="error">Slide ' + (index + 1) + ': ' + error + '</div>');
						});
					});
					
				}
			},
			datatype: 'json'
		});
	};
	
	// Save and continue button
	$("#zenario_save_continue").off().on("click", saveFunction);
	
	// Save and close button
	$("#zenario_save_close").off().on("click", function() {
		saveFunction();
		if (zenario_slideshow_2.errors.length == 0) {
			zenario_slideshow_2.closeImageManager("save");
		}
	});
};

zenario_slideshow_2.reverseSerialize = function(selector, data) {
	$(selector + ' *[name]').each(function(i, el) {
		if (data[el.name] !== undefined) {
			if (el.type == 'checkbox') {
				el.checked = zenario.engToBoolean(data[el.name]);
			} else {
				$(el).val(data[el.name]);
			}
		}
	});
	
};

zenario_slideshow_2.organizerChangeImage = function() {
	var path = 'zenario__content/panels/image_library';
	zenarioA.organizerSelect(
		'zenario_slideshow_2', 'organizerChangeImageCallback', false,
		path, path, path, path, true);
};

zenario_slideshow_2.organizerChangeImageCallback = function(path, key, row) {
	var image_type = zenario_slideshow_2.rememberImageType;
	var data = zenario.nonAsyncAJAX(zenario_slideshow_2.AJAXLink + "&mode=change_image_from_organizer&new_image_id=" + key.id, false, true);
	var current = zenario_slideshow_2.slides[zenario_slideshow_2.selectedImage];
	
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
			$("#zenario_slide_" + zenario_slideshow_2.selectedImage).attr({"src": current.image_src_thumbnail_2, "alt": current.alt});
			break;
	}
	
	zenario_slideshow_2.selectImage(zenario_slideshow_2.selectedImage);
	zenario_slideshow_2.rememberImageType = undefined;
};

zenario_slideshow_2.organizerPickUserCharacteristicCallback = function(path, key, row) {
	$("#zenario_user_characteristic").val(row.name);
};

zenario_slideshow_2.pickContentItemLinkFromOrganizer = function() {
	var path = 'zenario__content/panels/content_types';
	if ($("#zenario_content_item_id").val()) {
		path = 'zenario__content/panels/content//'+$("#zenario_content_item_id").val();
	}
	
	zenarioA.organizerSelect(
		'zenario_slideshow_2', 'organizerPickContentItemLinkCallback', false,
		path, 
		'zenario__content/panels/content',
		'zenario__content/panels/content_types', 
		false, 
		true);
};

zenario_slideshow_2.organizerPickContentItemLinkCallback = function(path, key, row) {
	$("#zenario_content_item_link").val(row.tag);
	$("#zenario_content_item_id").val(key.id);
};

zenario_slideshow_2.pickImagesFromOrganizer = function() {
	//console.log("pickImagesFromOrganizer");
	var path = 'zenario__content/panels/image_library';
	
	//zenarioA.organizerSelect(
	//	callbackObject, callbackFunction, enableMultipleSelect,
	//	path, targetPath, minPath, maxPath, disallowRefinersLoopingOnMinPath,
	zenarioA.organizerSelect(
		'zenario_slideshow_2', 'organizerPickImagesCallback', true,
		path, path, path, path, true);
};

zenario_slideshow_2.uploadImages = function() {
	//console.log("uploadImages");
	
	var requests = 
		{
			mode: "file_upload"
		};
	
	var fallback = !zenarioA.canDoHTML5Upload(),
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
			zenarioA.doHTML5Upload(this.files, zenario_slideshow_2.AJAXLink, requests, function(responses) {
				zenario_slideshow_2.uploadImagesCallback(responses);
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
			'<form id="jqmodal_form" action="' + htmlspecialchars(zenario_slideshow_2.AJAXLink) + '"' +
				' onsubmit="/*...*/"' +
				' target="zenario_iframe" method="post" enctype="multipart/form-data">'
				+ html;
		
		for (var r in requests) {
			html += '<input type="hidden" value="' + htmlspecialchars(requests[r]) + '" name="' + htmlspecialchars(r) + '"/>';
		}
		
		html += '</form>';
			
		buttonsHTML =
			'<input type="button" class="submit_selected" value="' + zenarioA.phrase.upload + '" onclick="get(\'jqmodal_form\').submit();"/>';
		
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
	//console.log("organizerPickImagesCallback");
	zenario_slideshow_2.somethingChanged = true;
	var data = zenario.nonAsyncAJAX(zenario_slideshow_2.AJAXLink + "&mode=add_slides_from_organizer&ids=" + key.id, false, true);
	data.forEach(function(newSlide){
		var id = "t" + zenario_slideshow_2.tempSlideId;
		zenario_slideshow_2.slides[id] = newSlide;
		zenario_slideshow_2.addGenericImageAttributes(id);
		
		zenario_slideshow_2.addSlideToList(zenario_slideshow_2.slides[id]);
		zenario_slideshow_2.tempSlideId++;
	});
	zenario_slideshow_2.addNewImagesCallback();
};

// Called after a new slide is uploaded
zenario_slideshow_2.uploadImagesCallback = function(responses) {
	//console.log("uploadImagesCallback");
	zenario_slideshow_2.somethingChanged = true;
	// If changing an image
	if (zenario_slideshow_2.uploadChangeImage) {
		var current = zenario_slideshow_2.slides[zenario_slideshow_2.selectedImage];
		switch(zenario_slideshow_2.rememberImageType) {
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
				
				$("#zenario_slide_" + zenario_slideshow_2.selectedImage).attr({"src": current.image_src_thumbnail_1, "alt": current.alt});
				
				break;
		}
		zenario_slideshow_2.selectImage(zenario_slideshow_2.selectedImage);
		
		zenario_slideshow_2.uploadChangeImage = false;
		zenario_slideshow_2.rememberImageType = undefined;
	
	// If creating a new image
	} else {
		responses.forEach(function(newSlide) {
			var id = "t" + zenario_slideshow_2.tempSlideId;
			zenario_slideshow_2.slides[id] = {};
			zenario_slideshow_2.addGenericImageAttributes(id);
			
			zenario_slideshow_2.slides[id].filename = newSlide.filename;
			zenario_slideshow_2.slides[id].alt_tag = newSlide.filename;
			zenario_slideshow_2.slides[id].width = 
			zenario_slideshow_2.slides[id].true_width = 
				newSlide.width;
			zenario_slideshow_2.slides[id].height = 
			zenario_slideshow_2.slides[id].true_height = 
				newSlide.height;
			
			zenario_slideshow_2.slides[id].image_src = newSlide.link;
			zenario_slideshow_2.slides[id].image_src_thumbnail_1 = newSlide.link + "&height=150&width=300";
			zenario_slideshow_2.slides[id].image_src_thumbnail_2 = newSlide.link + "&height=150&width=150";
			
			zenario_slideshow_2.slides[id].image_id = 0;
			zenario_slideshow_2.slides[id].cache_id = newSlide.id;
			zenario_slideshow_2.addSlideToList(zenario_slideshow_2.slides[id]);
			zenario_slideshow_2.tempSlideId++;
		});
		zenario_slideshow_2.addNewImagesCallback();
	}	
	
};

zenario_slideshow_2.addGenericImageAttributes = function(id) {
	zenario_slideshow_2.slides[id].id = id;
	zenario_slideshow_2.slides[id].overwrite_alt_tag = "";
	zenario_slideshow_2.slides[id].tab_name = "";
	zenario_slideshow_2.slides[id].slide_title = "";
	zenario_slideshow_2.slides[id].slide_extra_html = "";
	zenario_slideshow_2.slides[id].rollover_overwrite_alt_tag = "";
	zenario_slideshow_2.slides[id].mobile_overwrite_alt_tag = "";
	zenario_slideshow_2.slides[id].mobile_tab_name = "";
	zenario_slideshow_2.slides[id].mobile_slide_title = "";
	zenario_slideshow_2.slides[id].mobile_slide_extra_html = "";
	zenario_slideshow_2.slides[id].target_loc = "none";
	zenario_slideshow_2.slides[id].dest_url = "";
	zenario_slideshow_2.slides[id].open_in_new_window = 0;
	zenario_slideshow_2.slides[id].slide_visibility = "everyone";
	zenario_slideshow_2.slides[id].plugin_class = "";
	zenario_slideshow_2.slides[id].method_name = "";
	zenario_slideshow_2.slides[id].param_1 = "";
	zenario_slideshow_2.slides[id].param_2 = "";
	zenario_slideshow_2.slides[id].field_id = 0;
	zenario_slideshow_2.slides[id].link_to_translation_chain = "";
	zenario_slideshow_2.slides[id].transition_code = "";
	zenario_slideshow_2.slides[id].use_transition_code = 0;
	zenario_slideshow_2.slides[id].hidden = 0;
};

zenario_slideshow_2.addSlideToList = function(slide) {
	var html = 
		'<li id="zenario_slide_image_' + slide.id + '" data-id="' + slide.id + '"> \
			<div id="slideshow_image_container" onclick="zenario_slideshow_2.selectImage(\'' + slide.id + '\', true);"> \
				<img src="' + slide.image_src_thumbnail_2 + '" alt="' + slide.alt_tag + '" id="zenario_slide_' + slide.id + '"/> \
			</div> \
		</li>';
	$("#zenario_sortable").append(html);
};

zenario_slideshow_2.addNewImagesCallback = function() {
	zenario_slideshow_2.tempSaveOrder();
	
	
	$("#zenario_sortable li#zenario_slide_image_" + zenario_slideshow_2.selectedImage).css("border","solid 2px red");
	if (zenario_slideshow_2.selectedImage == 0) {
		zenario_slideshow_2.selectImage(zenario_slideshow_2.slideOrderedIds[0]);
	}
	
};

zenario_slideshow_2.makeListSortable = function() {
	$("#zenario_sortable").sortable({
		// Save ordinals locally when order is changed
		update: function(event, ui) {
			
			zenario_slideshow_2.tempSaveOrder();
			
		}
	});
};

// Delete currently selected slide
zenario_slideshow_2.deleteSlide = function() {
	// Check theres a slide to delete
	if (!_.isEmpty(zenario_slideshow_2.slides)) {
		zenario_slideshow_2.somethingChanged = true;
		var id = zenario_slideshow_2.selectedImage;
		var nextImageOrdinal = ((zenario_slideshow_2.slideOrderedIds.indexOf(id) - 1) >= 0) ? (zenario_slideshow_2.slideOrderedIds.indexOf(id) - 1) : 0;
		
		delete zenario_slideshow_2.slides[id];
		$("li#zenario_slide_image_" + id).remove();
		zenario_slideshow_2.tempSaveOrder();
		
		// If the last slide has been deleted
		if (_.isEmpty(zenario_slideshow_2.slides)) {
			zenario_slideshow_2.selectedImage = 0;
			// Hide input boxes or clear and readonly?
			$("#zenario_slide_settings_form").trigger("reset");
			$("#zenario_main_image_preview").attr({"src": "zenario/modules/zenario_slideshow_2/images/image-in-slot-placeholder.png", "alt": "No image"});
			$("#zenario_main_image_dimensions").text("");
			$("#zenario_rollover_image_dimensions").text("");
			$("#zenario_mobile_image_dimensions").text("");
			
		} else {
			zenario_slideshow_2.selectImage(zenario_slideshow_2.slideOrderedIds[nextImageOrdinal]);
		}
		
	}
};

zenario_slideshow_2.showForm = function(id) {
	var current = zenario_slideshow_2.slides[id];
	
	// If no tabs, hide tab name
	if (!zenario_slideshow_2.tabs) {
		$('#zenario_main_tab_name').hide();
		$('#zenario_main_tab_name').val('');
		$('#zenario_mobile_tab_name').hide();
		$('#zenario_mobile_tab_name').val('');
		
	}
	
	$('#zenario_rollover_filename').val(current['r_filename']);
	$('#zenario_mobile_filename').val(current['m_filename']);
	
	switch(current.target_loc) {
		case "none":
			break;
		case "internal":
			current.content_item_link = current.content_item_link;
			current.content_item_tag_id = current.dest_url;
			break;
		case "external":
			current.external_link = current.dest_url;
			break;
	}
	
	zenario_slideshow_2.reverseSerialize("#zenario_slide_settings_form", current);
	
	$("#zenario_main_image_preview").attr({"src": current.image_src_thumbnail_1, "alt": current.alt_tag});
	
	if (current.rollover_image_src_thumbnail_1) {
		$("#zenario_rollover_image_preview").attr({"src": current.rollover_image_src_thumbnail_1, "alt": current.r_alt_tag});
	} else {
		$("#zenario_rollover_image_preview").attr({"src": "zenario/modules/zenario_slideshow_2/images/image-in-slot-placeholder.png", "alt": "No image"});
	}
	if (current.mobile_image_src_thumbnail_1) {
		$("#zenario_mobile_image_preview").attr({"src": current.mobile_image_src_thumbnail_1, "alt": current.m_alt_tag});
	} else {
		$("#zenario_mobile_image_preview").attr({"src": "zenario/modules/zenario_slideshow_2/images/image-in-slot-placeholder.png", "alt": "No image"});
	}
	
	$("#zenario_select_link_type").change();
	$("#zenario_select_slide_visibility").change();
	
	// Show image height and width below preview images
	zenario_slideshow_2.setImageDimensions(current);
	
	// Change buttons if no image set yet in tab
	if (current['rollover_image_id'] !== null) {
		$('#zenario_change_rollover_image').text('Change Image');
		$('#zenario_remove_rollover_image').show();
	} else {
		$('#zenario_change_rollover_image').text('Add Image');
		$('#zenario_remove_rollover_image').hide();
	}
	if (current['mobile_image_id'] !== null) {
		$('#zenario_change_mobile_image').text('Change Image');
		$('#zenario_remove_mobile_image').show();
	} else {
		$('#zenario_change_mobile_image').text('Add Image');
		$('#zenario_remove_mobile_image').hide();
	}
	
	zenario_slideshow_2.size();
};

zenario_slideshow_2.setImageDimensions = function(slide) {
	var text = '';
	if (slide.true_width && slide.true_height) {
		text = slide.true_width + " x " + slide.true_height;
	}
	$("#zenario_main_image_dimensions").text(text);
	text = '';
	if (slide.true_r_width && slide.true_r_height) {
		text = slide.true_r_width + " x " + slide.true_r_height;
	}
	$("#zenario_rollover_image_dimensions").text(text);
	text = '';
	if (slide.true_m_width && slide.true_m_height) {
		text = slide.true_m_width + " x " + slide.true_m_height;
	}
	$("#zenario_mobile_image_dimensions").text(text);
};

// Save data entered for current slide when viewing different slide
zenario_slideshow_2.tempSaveData = function(oldSlide) {
	if (zenario_slideshow_2.selectedImage != 0) {
		// Get form data
		var data = new Array;
		serial = $('#zenario_slide_settings_form').serializeArray();
		for (i in serial) {
			data[serial[i].name] = serial[i].value;
		}
		// Save form data to slide
		oldSlide.overwrite_alt_tag = data["overwrite_alt_tag"];
		oldSlide.tab_name = data["tab_name"];
		oldSlide.slide_title = data["slide_title"];
		oldSlide.slide_extra_html = data["slide_extra_html"];
		oldSlide.rollover_overwrite_alt_tag = data["rollover_overwrite_alt_tag"];
		oldSlide.mobile_overwrite_alt_tag = data["mobile_overwrite_alt_tag"];
		oldSlide.mobile_tab_name = data["mobile_tab_name"];
		oldSlide.mobile_slide_title = data["mobile_slide_title"];
		oldSlide.mobile_slide_extra_html = data["mobile_slide_extra_html"];
		oldSlide.target_loc = data["target_loc"];
		switch(oldSlide.target_loc) {
			case "internal":
				oldSlide.content_item_link = data["content_item_link"];
				oldSlide.dest_url = data["content_item_tag_id"];
				break;
			case "external":
				oldSlide.dest_url = data["external_link"];
				break;
			case "none":
				oldSlide.dest_url = "";
				break;
		}
		oldSlide.open_in_new_window = zenario.engToBoolean(data["open_in_new_window"]);
		oldSlide.slide_visibility = data["slide_visibility"];
		oldSlide.plugin_class = data["plugin_class"];
		oldSlide.method_name = data["method_name"];
		oldSlide.param_1 = data["param_1"];
		oldSlide.param_2 = data["param_2"];
		oldSlide.field_id = data['field_id'];
		oldSlide.link_to_translation_chain = zenario.engToBoolean(data["link_to_translation_chain"]);
		oldSlide.use_transition_code = zenario.engToBoolean(data["use_transition_code"]);
		oldSlide.hidden = zenario.engToBoolean(data["hidden"]);
	}
};

zenario_slideshow_2.tempSaveOrder = function() {
	if (zenario_slideshow_2.selectedImage == 0) {
		zenario_slideshow_2.makeListSortable();
	}
	zenario_slideshow_2.slideOrderedIds = $('#zenario_sortable').sortable('toArray', {attribute: 'data-id'});
};

// Unselect Currently selected image and select the image clicked on
zenario_slideshow_2.selectImage = function(id, clicked) {
	
	// Save any entered data in the local array
	if (zenario_slideshow_2.selectedImage != 0 && zenario_slideshow_2.slideOrderedIds.indexOf(zenario_slideshow_2.selectedImage) >= 0 ) {
		zenario_slideshow_2.tempSaveData(zenario_slideshow_2.slides[zenario_slideshow_2.selectedImage]);
	}
	// Reset rememberImageType if a new slide is clicked
	if (clicked) {
		//zenario_slideshow_2.rememberImageType = "";
		$("#zenario_edit_main_image").click();
	}
	if (id) {
		
		$("#zenario_sortable li#zenario_slide_image_" + zenario_slideshow_2.selectedImage).css("border","none").toggleClass("slideshow_selected_image");
		$("#zenario_sortable li#zenario_slide_image_" + id).css("border","solid 2px red").toggleClass("slideshow_selected_image");
		zenario_slideshow_2.selectedImage = id.toString();
		// refresh slide details microtemplate when new slide is selected
		zenario_slideshow_2.showForm(id);
	}
};
// Select last image
zenario_slideshow_2.selectLastImage = function() {
	if (!_.isEmpty(zenario_slideshow_2.slides)) {
		var length = zenario_slideshow_2.slideOrderedIds.length - 1;
		zenario_slideshow_2.selectImage(zenario_slideshow_2.slideOrderedIds[length]);
	}
};
// Select first image
zenario_slideshow_2.selectFirstImage = function() {
	if (!_.isEmpty(zenario_slideshow_2.slides)) {
		zenario_slideshow_2.selectImage(zenario_slideshow_2.slideOrderedIds[0]);
	}
};
// Move the selected image one place up
zenario_slideshow_2.nudgeImageUp = function() {
	if (!_.isEmpty(zenario_slideshow_2.slides)) {
	
		var current = $(".slideshow_selected_image");
		current.prev().before(current);
		zenario_slideshow_2.tempSaveOrder();
	}
};
// Move the selected image one place down
zenario_slideshow_2.nudgeImageDown = function() {
	if (!_.isEmpty(zenario_slideshow_2.slides)) {
	
		var current = $(".slideshow_selected_image");
		current.next().after(current);
		zenario_slideshow_2.tempSaveOrder();
	}
};

zenario_slideshow_2.resize = function() {
	maxHeight = 800;
	if (zenario_slideshow_2.sizing) {
		clearTimeout(zenario_slideshow_2.sizing);
	}
	if (get('zenario_fbAdminInner')) {
		var height = Math.floor($(window).height() * 0.96 - (zenario.browserIsIE()? 210 : zenario.browserIsSafari()? 202 : 205));
		if (height > maxHeight) {
			height = maxHeight;
		}
		
		if ((height = (1*height)) > 0) {
			get('zenario_fbAdminInner').style.height = height + 'px';
			get('zenario_slides').style.height = get('zenario_slide_attributes').style.height = (height - 60) + 'px';
			
		}
	}
	zenario_slideshow_2.sizing = setTimeout(zenario_slideshow_2.resize, 250);
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