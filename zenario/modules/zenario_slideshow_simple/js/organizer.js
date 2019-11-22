zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioT, zenarioAB, zenarioAT, zenarioO,
	encodeURIComponent, defined, engToBoolean, get, htmlspecialchars, jsEscape, phrase,
	extensionOf, methodsOf, has,
	methods
) {
	"use strict";


methods.openSlideManager = function(el, slotName, ajaxURL, instanceId) {
	if (el) {
		el.blur();
	}
	
	thus.ajaxURL = ajaxURL;
	thus.slotName = slotName;
	thus.instanceId = instanceId;
	thus.nextNewId = 0;
	thus.slides = {};
	thus.groups = {};
	thus.smartGroups = {};
	thus.showTabs = false;
	thus.deletedSlideIds = [];
	thus.selectedSlideId = false;
	thus.changeMade = false;
	thus.desktop_canvas_setting = false;
	thus.desktop_canvas_setting_nice_name = false;
	thus.mobile_canvas_setting = false;
	thus.mobile_canvas_setting_nice_name = false;
	thus.mobile_canvas_width = false;
	thus.mobile_canvas_height = false;
	thus.dimensions = false;
	thus.mobileDimensions = false;
	thus.errors = false;
	
	//Set editor HTML
	thus.setEditorHTML();
	
	//Load slideshow data
	zenario.ajax(ajaxURL + "&mode=get_data").after(function(data) {
		data = JSON.parse(data);
		if (!Array.isArray(data.slides)) {
			thus.slides = data.slides;
		}
		if (!Array.isArray(data.groups)) {
			thus.groups = data.groups;
		}
		if (!Array.isArray(data.smartGroups)) {
			thus.smartGroups = data.smartGroups;
		}
		thus.showTabs = data.showTabs;
		
		if (data.desktopCanvasSetting) {
			thus.desktop_canvas_setting = data.desktopCanvasSetting;
			thus.desktop_canvas_setting_nice_name = data.desktopCanvasSettingNiceName;
		}
		
		if (data.mobileCanvasSetting) {
			thus.mobile_canvas_setting = data.mobileCanvasSetting;
			thus.mobile_canvas_setting_nice_name = data.mobileCanvasSettingNiceName;
		}
		
		if (data.width && data.height) {
			thus.dimensions = data.width + ' x ' + data.height + 'px';
		}
		
		if (data.mobileWidth && data.mobileHeight) {
			thus.mobileDimensions = data.mobileWidth + ' x ' + data.mobileHeight + 'px';
		}
		
		thus.mobile_canvas_width = data.mobileWidth;
		thus.mobile_canvas_height = data.mobileHeight;
		
		var orderedSlides = thus.getOrderedSlides();
		thus.selectedSlideId = orderedSlides.length > 0 ? orderedSlides[0].id : false;
		
		//Set slides HTML
		thus.setSlidesHTML();
		
		//Select first slide
		thus.selectSlide(thus.selectedSlideId);
	});
};

methods.setEditorHTML = function() {
	//Set HTML
	var width = 1000;
	var html = zenarioT.microTemplate('zenario_image_manager', {});
	zenarioA.openBox(html, 'zenario_fbAdminImageManager', 'AdminImageManager', undefined, width, undefined, 2, true, true, '.zenario_dragMe', false, false);
	
	//Resize editor to fit window
	var resize = function() {
		if (thus.resizing) {
			clearTimeout(thus.resizing);
		}
		var maxHeight = 800;
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
		thus.resizing = setTimeout(resize, 250);
	};
	resize();
		
	//Add events
	$('#zenario_slides .slide_navigation .last').on('click', function() {
		thus.selectLastImage();
	});
	$('#zenario_slides .slide_navigation .first').on('click', function() {
		thus.selectFirstImage();
	});
	$('#zenario_slides .slide_navigation .up').on('click', function() {
		if (thus.selectedSlideId) {
			thus.nudgeImageUp(thus.selectedSlideId);
		}
	});
	$('#zenario_slides .slide_navigation .down').on('click', function() {
		if (thus.selectedSlideId) {
			thus.nudgeImageDown(thus.selectedSlideId);
		}
	});
	
	$('#zenario_slides .slide_navigation .delete').on('click', function() {
		if (thus.selectedSlideId) {
			thus.deleteSlide(thus.selectedSlideId);
		}
	});
	
	$('#zenario_image_manager .button_upload_images').on('click', function() {
		thus.uploadImages();
	});
	$('#zenario_image_manager .button_add_images_organizer').on('click', function() {
		thus.pickImagesFromOrganizer();
	});
	
	$('#zenario_image_manager .zenario_close_image_manager').on('click', function() {
		thus.closeImageManager();
	});
	
	$("#zenario_save_continue").on("click", function() {
		thus.save();
	});
	$("#zenario_save_close").on("click", function() {
		thus.save(true);
	});
};

methods.setSlidesHTML = function() {
	//Set HTML
	var mergeFields = thus.getOrderedSlides();
	for (var i = 0; i < mergeFields.length; i++) {
		if (mergeFields[i].id == thus.selectedSlideId) {
			mergeFields[i].selected = true;
		}
	}
	
	var html = zenarioT.microTemplate('zenario_slide_image', mergeFields);
	$('#zenario_sortable').html(html);
	
	//Add events
	$("#zenario_sortable").sortable({
		update: function(event, ui) {
			thus.saveSlideOrder();
			thus.changeMade = true;
		}
	});
	
	$('#zenario_sortable li').on('click', function() {
		var slideId = $(this).data('id');
		thus.selectSlide(slideId);
	});
};

methods.setSlideDetailsHTML = function(slideId, imageType) {
	//Set HTML
	var mergeFields = {};
	var cb = new zenario.callback;
	
	imageType = imageType === undefined ? 'main' : imageType;
	
	if (slideId) {
		mergeFields = thus.getSlideDetailsMergeFields(slideId, imageType, cb);
	}
	
	var html = zenarioT.microTemplate('zenario_slide_details', mergeFields);
	$('#zenario_slide_attributes_inner').html(html);
	
	cb.done();
	
	//Add events
	$('#zenario_external_link').on('blur', function() {
		if (this.value == 'http://') { this.value = '' };
	});
	$('#zenario_external_link').on('focus', function() {
		if (!this.value) { this.value = 'http://' };
	});
	
	$('#zenario_select_content_item').on('click', function() {
		thus.pickContentItemLinkFromOrganizer(slideId);
	});
	
	$('#zenario_image_type .slide_tab').on('click', function() {
		thus.selectSlide(slideId, $(this).data('type'));
	});
	
	
	$('#zenario_upload_option').on('click', function() {
		thus.uploadImages(slideId, 'main');
	});
	$('#zenario_organizer_option').on('click', function() {
		thus.pickImagesFromOrganizer(slideId, 'main');
	});
	
	$('#zenario_rollover_upload_option').on('click', function() {
		thus.uploadImages(slideId, 'rollover');
	});
	$('#zenario_rollover_organizer_option').on('click', function() {
		thus.pickImagesFromOrganizer(slideId, 'rollover');
	});
	$('#zenario_remove_rollover_image').on('click', function(e) {
		thus.slides[slideId].rollover_image_id = false;
		thus.selectSlide(slideId, 'rollover');
		thus.changeMade = true;
	});
	
	$('#zenario_mobile_upload_option').on('click', function() {
		thus.uploadImages(slideId, 'mobile');
	});
	$('#zenario_mobile_organizer_option').on('click', function() {
		thus.pickImagesFromOrganizer(slideId, 'mobile');
	});
	$('#zenario_remove_mobile_image').on('click', function(e) {
		thus.slides[slideId].mobile_image_id = false;
		thus.selectSlide(slideId, 'mobile');
		thus.changeMade = true;
	});
};

methods.getSlideDetailsMergeFields = function(slideId, imageType, cb) {
	var mergeFields = JSON.parse(JSON.stringify(thus.slides[slideId]));
	mergeFields.imageType = imageType;
	mergeFields.showTabs = thus.showTabs;
	
	if (thus.errors && thus.errors[slideId] && thus.errors[slideId][imageType]) {
		mergeFields['error_' + imageType] = thus.errors[slideId][imageType][0];
	}
	
	mergeFields.link_type_values = [
		{value: '_NO_LINK', label: "No link"},
		{value: '_CONTENT_ITEM', label: 'Link to a content item'},
		{value: '_EXTERNAL_URL', label: 'Link to an external URL'}
	];
	for (var i = 0; i < mergeFields.link_type_values.length; i++) {
		if (mergeFields.link_type == mergeFields.link_type_values[i].value) {
			mergeFields.link_type_values[i].selected = true;
		}
	}
	
	mergeFields.privacy_values = [
		{value: 'public', label: 'Everyone'},
		{value: 'logged_out', label: 'Only show to visitors who are NOT logged in'},
		{value: 'logged_in', label: 'Private, only show to extranet users who are logged in'},
		{value: 'group_members', label: 'Private, only show to extranet users in the group(s):'},
		{value: 'in_smart_group', label: 'Private, only show to extranet users in the smart group:'},
		{value: 'logged_in_not_in_smart_group', label: 'Private, only show to extranet users NOT in the smart group:'},
		{value: 'call_static_method', label: "Call a module's static method (advanced):"},
		{value: 'hidden', label: "Hidden"}
	];
	for (var i = 0; i < mergeFields.privacy_values.length; i++) {
		if (mergeFields.privacy == mergeFields.privacy_values[i].value) {
			mergeFields.privacy_values[i].selected = true;
		}
	}
	
	var orderedGroups = [];
	for (var id in thus.groups) {
		orderedGroups.push({
			value: id, 
			label: thus.groups[id].label, 
			checked: mergeFields.group_ids && mergeFields.group_ids[id],
			ord: thus.groups[id].ord
		});
	}
	orderedGroups.sort(function(a, b) { return a.ord - b.ord });
	mergeFields.groups = orderedGroups;
	
	var orderedSmartGroups = [];
	for (var id in thus.smartGroups) {
		orderedSmartGroups.push({
			value: id, 
			label: thus.smartGroups[id].label, 
			selected: mergeFields.smart_group_id == id,
			ord: thus.smartGroups[id].ord
		});
	}
	orderedSmartGroups.sort(function(a, b) { return a.ord - b.ord });
	mergeFields.smart_groups_values = orderedSmartGroups;
	
	
	if (!mergeFields.mobile_behaviour) {
		mergeFields.mobile_behaviour = 'same_image';
	}
	mergeFields.mobile_behaviour_values = [
		{value: 'same_image', label: 'Use the same image as desktop'},
		{value: 'same_image_different_size', label: 'Use the desktop image at a different size'},
		{value: 'different_image', label: 'Use a different image'}
	];
	for (var i = 0; i < mergeFields.mobile_behaviour_values.length; i++) {
		mergeFields.mobile_behaviour_values[i].id = slideId;
		mergeFields.mobile_behaviour_values[i].imageType = 'mobile';
		mergeFields.mobile_behaviour_values[i].name = 'mobile_behaviour';
		mergeFields.mobile_behaviour_values[i].format_onchange = true;
		if (mergeFields.mobile_behaviour == mergeFields.mobile_behaviour_values[i].value) {
			mergeFields.mobile_behaviour_values[i].selected = true;
		}
	}
	
	//Required to display the mobile canvas width.
	if (!mergeFields.m_width) {
		mergeFields.m_width = thus.mobile_canvas_width;
	}
	
	//Required to display the mobile canvas height.
	if (!mergeFields.m_height) {
		mergeFields.m_height = thus.mobile_canvas_height;
	}
	
	if (mergeFields.mobile_behaviour == 'same_image' || mergeFields.mobile_behaviour == 'same_image_different_size') {
		mergeFields.mobile_image_id = mergeFields.image_id;
		mergeFields.mobile_image_details_thumbnail_url = mergeFields.image_details_thumbnail_url;
		mergeFields.m_width = mergeFields.width;
		mergeFields.m_height = mergeFields.height;
	}
	
	mergeFields.desktop_canvas_setting = thus.desktop_canvas_setting;
	mergeFields.desktop_canvas_setting_nice_name = thus.desktop_canvas_setting_nice_name;
	mergeFields.mobile_canvas_setting = thus.mobile_canvas_setting;
	mergeFields.mobile_canvas_setting_nice_name = thus.mobile_canvas_setting_nice_name;
	
	if (thus.dimensions) {
		mergeFields.dimensions = thus.dimensions;
	}
	if (thus.mobileDimensions) {
		mergeFields.mobileDimensions = thus.mobileDimensions;
	}
	
	//Code for using the standard FAB image picker.
	//Some things still need to be sorted out to use it (e.g. updating preview images, saving).
	/*
	var zenarioS2 = window.zenarioS2 = new zenarioAF();
	zenarioS2.init('zenarioS2', 'zenario_admin_box');
	
	//This is needed to allow files to be uploaded
	zenarioS2.getKey = function() {
		return {};
	};
	
	//This enables image previews of picked items
	zenarioS2.drawPickedItem = methodsOf(zenarioABToolkit).drawPickedItem;
	
	var fields = {
		image_id: {
			ord: 1,
			label: 'Desktop Image:', 
			pick_items: {
				path: 'zenario__content/panels/image_library',
				select_phrase: 'Select image...'
			},
			upload: {
				multi: false,
				accept: 'image/*',
				extensions: ['.gif', '.jpg', '.jpeg', '.png'],
				drag_and_drop: true,
				reorder_items: false
			},
			value: mergeFields.image_id,
			onchange: function(field) {
				//...
			}
		}
	};
	mergeFields.test = zenarioS2.drawTUIX(fields, 'zenario_admin_simple_form', cb);
	*/
	
	return mergeFields;
};

methods.getOrderedSlides = function() {
	var orderedSlides = [];
	for (var slideId in thus.slides) {
		orderedSlides.push(_.clone(thus.slides[slideId]));
	}
	orderedSlides.sort(function(a, b) { return a.ord - b.ord });
	return orderedSlides;
};

methods.saveSlideOrder = function() {
	var orderedSlideIds = $('#zenario_sortable').sortable('toArray', {attribute: 'data-id'});
	for (var i = 0; i < orderedSlideIds.length; i++) {
		thus.slides[orderedSlideIds[i]].ord = i + 1;
	}
};

methods.selectSlide = function(slideId, imageType) {
	//Save previous slides data
	thus.saveOpenDetails();
	
	//Select new slide
	thus.selectedSlideId = slideId;
	thus.setSlidesHTML();
	thus.setSlideDetailsHTML(slideId, imageType);
};

methods.saveOpenDetails = function() {
	var slide = thus.slides[thus.selectedSlideId];
	if (!slide) {
		return;
	}
	
	var data = $('#zenario_slide_settings_form').serializeArray();
	for (var i = 0; i < data.length; i++) {
		slide[data[i].name] = data[i].value;
	}
	
	var multiFields = {};
	$('#zenario_slide_settings_form input:checkbox').each(function() {
		var index = this.name.indexOf('[]');
		if (index !== -1) {
			var name = this.name.substr(0, index);
			if (!multiFields[name]) {
				multiFields[name] = {};
			}
			if (this.checked) {
				multiFields[name][this.value] = true;
			}
		} else {
			slide[this.name] = this.checked;
		}
	});
	for (var name in multiFields) {
		slide[name] = multiFields[name];
	}
};

methods.deleteSlide = function(slideId) {
	var orderedSlides = thus.getOrderedSlides();
	var nextSlideId = false;
	for (var i = 0; i < orderedSlides.length; i++) {
		if (orderedSlides[i].id == slideId) {
			if (orderedSlides[i - 1]) {
				nextSlideId = orderedSlides[i - 1].id;
			} else if (orderedSlides[i + 1]) {
				nextSlideId = orderedSlides[i + 1].id;
			}
			break;
		}
	}
	
	thus.deletedSlideIds.push(slideId);
	delete(thus.slides[slideId]);
	
	thus.selectedSlideId = nextSlideId;
	thus.selectSlide(nextSlideId);
	thus.saveSlideOrder();
	thus.changeMade = true;
};


methods.selectLastImage = function() {
	var orderedSlides = thus.getOrderedSlides();
	if (orderedSlides.length) {
		thus.selectSlide(orderedSlides[orderedSlides.length - 1].id);
	}
};
methods.selectFirstImage = function() {
	var orderedSlides = thus.getOrderedSlides();
	if (orderedSlides.length) {
		thus.selectSlide(orderedSlides[0].id);
	}
};
methods.nudgeImageUp = function(slideId) {
	thus.slides[slideId].ord -= 1.1;
	thus.setSlidesHTML();
	thus.saveSlideOrder();
	thus.changeMade = true;
};
methods.nudgeImageDown = function(slideId) {
	thus.slides[slideId].ord += 1.1;
	thus.setSlidesHTML();
	thus.saveSlideOrder();
	thus.changeMade = true;
};

methods.uploadImages = function(slideId, imageType) {
	var requests = {mode: "file_upload"};
	var fallback = !zenarioT.canDoHTML5Upload();
	var html = '<input type="file" name="Filedata" accept="image/*"';
	
	if (fallback) {
		html += ' id="zenario_fallback_fileupload"/>';
		requests._html5_backwards_compatibility_hack = 1;
		
		//For backwards compatability for browsers without html5, attempt to convert file upload tags into ajax->confirm->form tags
		//Start generating the box.
		//If there is a form, the message should be surrounded by <form></form> tags.
		;
		html = 
			'<form id="zenario_bc_form" action="' + htmlspecialchars(thus.ajaxURL) + '"' +
				' onsubmit=""' +
				' target="zenario_iframe" method="post" enctype="multipart/form-data">'
				+ html;
		
		for (var r in requests) {
			html += '<input type="hidden" value="' + htmlspecialchars(requests[r]) + '" name="' + htmlspecialchars(r) + '"/>';
		}
		html += '</form>';
		
		var buttonsHTML =
			'<input type="button" class="submit_selected" value="' + zenarioA.phrase.upload + '" onclick="get(\'zenario_bc_form\').submit();"/>';
		buttonsHTML +=
			'<input type="button" class="submit" value="' + zenarioA.phrase.cancel + '"/>';
		
		zenarioA.showMessage(html, buttonsHTML);
		
		//Try to click the fileupload prompt straight away...
		//...except on IE, where thus causes an error :'(
		if (!zenario.browserIsIE()) {
			$('#zenario_fallback_fileupload').click();
		}
	} else {
		html += ' multiple/>';
			var $input = $(html);
		$input.change(function() {
			zenarioT.doHTML5Upload(this.files, thus.ajaxURL, requests, function(responses) {
				thus.uploadImagesCallback(slideId, imageType, responses);
			});
		});
		$input.click();
		return;
	}
};

methods.uploadImagesCallback = function(slideId, imageType, responses) {
	//Update an existing slide
	if (responses.length) {
		if (slideId) {
			if (imageType == 'rollover') {
				thus.slides[slideId].rollover_image_id = responses[0].id;
				thus.slides[slideId].r_filename = responses[0].filename;
				thus.slides[slideId].r_alt_tag = responses[0].alt_tag;
				thus.slides[slideId].r_width = responses[0].width;
				thus.slides[slideId].r_height = responses[0].height;
				thus.slides[slideId].rollover_image_details_thumbnail_url = responses[0].link + "&height=150&width=300";
			} else if (imageType == 'mobile') {
				thus.slides[slideId].mobile_image_id = responses[0].id;
				thus.slides[slideId].m_filename = responses[0].filename;
				thus.slides[slideId].m_alt_tag = responses[0].alt_tag;
				thus.slides[slideId].m_width = responses[0].width;
				thus.slides[slideId].m_height = responses[0].height;
				thus.slides[slideId].mobile_image_details_thumbnail_url = responses[0].link + "&height=150&width=300";
			} else {
				thus.slides[slideId].image_id = responses[0].id;
				thus.slides[slideId].filename = responses[0].filename;
				thus.slides[slideId].alt_tag = responses[0].alt_tag;
				thus.slides[slideId].width = responses[0].width;
				thus.slides[slideId].height = responses[0].height;
				thus.slides[slideId].image_details_thumbnail_url = responses[0].link + "&height=150&width=300";
				thus.slides[slideId].image_list_thumbnail_url = responses[0].link + "&height=150&width=150";
			}
		
		//Create new slides
		} else {
			for (var i = 0; i < responses.length; i++) {
				var slide = {
					image_id: responses[i].id,
					filename: responses[i].filename,
					alt_tag: responses[i].alt_tag,
					width: responses[i].width,
					height: responses[i].height,
					image_details_thumbnail_url: responses[i].link + "&height=150&width=300",
					image_list_thumbnail_url: responses[i].link + "&height=150&width=150"
				};
			
				slideId = thus.addNewSlide(slide);
			}
		}
		
		thus.selectSlide(slideId, imageType);
		thus.changeMade = true;
	}
};

methods.pickImagesFromOrganizer = function(slideId, imageType) {
	thus.remember = {slideId: slideId, imageType: imageType};
	var path = 'zenario__content/panels/image_library';
	zenarioA.organizerSelect(
		'zenario_slideshow_simple', 'pickImagesFromOrganizerCallback', true,
		path, path, path, path, true);
};

methods.pickImagesFromOrganizerCallback = function(path, key, row) {
	var slideId = thus.remember.slideId;
	var imageType = thus.remember.imageType;
	thus.remember = false;
	
	zenario.ajax(thus.ajaxURL + '&mode=add_slides_from_organizer&ids=' + key.id).after(function(responses) {
		responses = JSON.parse(responses);
		if (responses.length) {
			//Update an existing slide
			if (slideId) {
				if (imageType == 'rollover') {
					thus.slides[slideId].rollover_image_id = responses[0].image_id;
					thus.slides[slideId].r_filename = responses[0].filename;
					thus.slides[slideId].r_alt_tag = responses[0].alt_tag;
					thus.slides[slideId].r_width = responses[0].width;
					thus.slides[slideId].r_height = responses[0].height;
					thus.slides[slideId].rollover_image_details_thumbnail_url = responses[0].image_details_thumbnail_url;
				} else if (imageType == 'mobile') {
					thus.slides[slideId].mobile_image_id = responses[0].image_id;
					thus.slides[slideId].m_filename = responses[0].filename;
					thus.slides[slideId].m_alt_tag = responses[0].alt_tag;
					thus.slides[slideId].m_width = responses[0].width;
					thus.slides[slideId].m_height = responses[0].height;
					thus.slides[slideId].mobile_image_details_thumbnail_url = responses[0].image_details_thumbnail_url;
				} else {
					thus.slides[slideId].image_id = responses[0].image_id;
					thus.slides[slideId].filename = responses[0].filename;
					thus.slides[slideId].alt_tag = responses[0].alt_tag;
					thus.slides[slideId].width = responses[0].width;
					thus.slides[slideId].height = responses[0].height;
					thus.slides[slideId].image_details_thumbnail_url = responses[0].image_details_thumbnail_url;
					thus.slides[slideId].image_list_thumbnail_url = responses[0].image_list_thumbnail_url;
				}
				
			//Create new slides
			} else {
				for (var i = 0; i < responses.length; i++) {
					slideId = thus.addNewSlide(responses[i]);
				}
			}
			
			thus.selectSlide(slideId, imageType);
			thus.changeMade = true;
		}
	});
};

methods.addNewSlide = function(slide) {
	slide.id = 't' + (++thus.nextNewId);
	
	var orderedSlides = thus.getOrderedSlides();
	slide.ord = orderedSlides.length ? orderedSlides[orderedSlides.length - 1].ord + 1 : 1;
	
	thus.slides[slide.id] = slide;
	thus.changeMade = true;
	return slide.id;
};

methods.pickContentItemLinkFromOrganizer = function(slideId) {
	var path = 'zenario__content/panels/content';
	if ($("#zenario_content_item_id").val()) {
		path = 'zenario__content/panels/content//'+$("#zenario_content_item_id").val();
	}
	thus.remember = {slideId: slideId};
	zenarioA.organizerSelect(
		'zenario_slideshow_simple', 'organizerPickContentItemLinkCallback', false,
		path, 
		'zenario__content/panels/content',
		'zenario__content/panels/content', 
		false, 
		true);
};

methods.organizerPickContentItemLinkCallback = function(path, key, row) {
	var slideId = thus.remember.slideId;
	thus.remember = false;
	
	thus.slides[slideId].hyperlink_target_display = row.tag;
	thus.slides[slideId].hyperlink_target = key.id;
	
	thus.selectSlide(slideId);
	thus.changeMade = true;
};

methods.closeImageManager = function() {
	thus.saveOpenDetails();
	var message = 'You are currently editing thus floating admin box. If you leave now you will loose any unsaved changes.';
	if (!thus.changeMade || confirm(message)) {
		zenarioA.closeBox('AdminImageManager');
	}
};

methods.save = function(closeAfter) {
	thus.saveOpenDetails();
	var requests = {
		slides: JSON.stringify(thus.slides),
		deletedSlideIds: JSON.stringify(thus.deletedSlideIds)
	};
	
	zenario.ajax(thus.ajaxURL + '&mode=save_slides', requests).after(function(errors) {
		errors = JSON.parse(errors);
		if (_.isEmpty(errors)) {
			if (thus.slotName) {
				zenario.refreshPluginSlot(thus.slotName, 'lookup');
			}
			if (closeAfter) {
				zenarioA.closeBox('AdminImageManager');
			}
		} else {
			thus.errors = errors;
			for (var slideId in errors) {
				for (var imageType in errors[slideId]) {
					thus.selectSlide(slideId, imageType);
					break;
				}
				break;
			}
		}
	});
};


}, zenario_slideshow_simple);