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
(function(
	zenario, zenarioA, zenario_wysiwyg_editor,
	undefined) {


zenario_wysiwyg_editor.summaries = {};

zenario_wysiwyg_editor.animationsHiddenInEditors = false;
zenario_wysiwyg_editor.hideAnimationsInEditors = function() {
	zenario_wysiwyg_editor.animationsHiddenInEditors = true;
}

zenario_wysiwyg_editor.imagesHiddenInEditors = false;
zenario_wysiwyg_editor.hideImagesInEditors = function() {
	zenario_wysiwyg_editor.imagesHiddenInEditors = true;
}

zenario_wysiwyg_editor.open = function(containerId, editorId, summaryLocked, summaryEmpty, summaryMatches, delayed) {
	
	//If the Admin Toolbar has not loaded yet, save this code until it has loaded
	if (!zenarioAT.loaded && !delayed) {
		zenarioAT.runOnInit.push(function() {
			zenario_wysiwyg_editor.open(containerId, editorId, summaryLocked, summaryEmpty, summaryMatches, true);
		});
		return;
	}

	
	var images = '';
	
	if (!zenario_wysiwyg_editor.animationsHiddenInEditors && !zenario_wysiwyg_editor.imagesHiddenInEditors) {
		images = 'media,image,|,';
	} else if (!zenario_wysiwyg_editor.animationsHiddenInEditors) {
		images = 'media,|,';
	} else if (!zenario_wysiwyg_editor.imagesHiddenInEditors) {
		images = 'image,|,';
	}
	
	zenarioA.getSkinDesc();
	$('div#' + editorId).tinymce({
		script_url: URLBasePath + zenarioA.tinyMCEPath,

		plugins: ["advlist autolink lists link image charmap hr anchor",
        "searchreplace code",
        "nonbreaking zenario_save table contextmenu directionality",
        "paste autoresize"],
        
        image_advtab: true,
        browser_spellcheck: true,
        
        //contextmenu:
        
        
        /*
Toolbar controls
Plugin	Controls
core	bold italic underline strikethrough subscript superscript outdent indent cut copy paste
		selectall removeformat visualaid newdocument blockquote numlist bullist
		alignleft aligncenter alignright alignjustify undo redo
hr	hr
link	link unlink
image	image
charmap	charmap
anchor	anchor
searchreplace	searchreplace
code	code
nonbreaking	nonbreaking
directionality	ltr rtl
*/
		menu: {
			file: {title: 'File', items: ''},
			edit: {title: 'Edit', items: 'undo redo | cut copy paste | selectall'},
			insert: {title: 'Insert', items: 'link image | anchor hr charmap'},
			view: {title: 'View', items: ''},
			format: {title: 'Format', items: 'bold italic underline strikethrough superscript subscript' + (zenarioA.skinDesc.style_formats? ' | formats' : '') + ' | removeformat'},
			table: {title: 'Table', items: 'inserttable tableprops deletetable | cell row column'},
			tools: {title: 'Tools', items: 'searchreplace | code'}
		},
		
		toolbar: 'undo redo | image link unlink | bold italic underline | removeformat' + (zenarioA.skinDesc.style_formats? ' | styleselect' : '') + ' | fontsizeselect | formatselect | numlist bullist | outdent indent | alignleft aligncenter alignright alignjustify | save save_and_close cancel',
		statusbar: false,
		
		
		//autoresize_max_height: Math.max(Math.floor(($(window).height() - 130 - 100) * 0.9), 400),
		autoresize_min_height: 100,
		paste_preprocess: zenarioA.tinyMCEPasteRreprocess,
		
		inline: true,
		zenario_inline_ui: true,
		
		inline_styles: false,
		allow_events: true,
		allow_script_urls: true,
		convert_urls: false,
		
		
		style_formats: zenarioA.skinDesc.style_formats,
		/*: [
			{title: 'Bold text', inline: 'b'},
			{title: 'Red text', inline: 'span', styles: {color: '#ff0000'}},
			{title: 'Red header', block: 'h1', styles: {color: '#ff0000'}},
			{title: 'Example 1', inline: 'span', classes: 'example1'},
			{title: 'Example 2', inline: 'span', classes: 'example2'},
			{title: 'Table styles'},
			{title: 'Table row 1', selector: 'tr', classes: 'tablerow1'}
		],*/
		
		file_browser_callback: zenario_wysiwyg_editor.SK,
		
		init_instance_callback: function(instance) {
			zenarioA.enableDragDropUploadInTinyMCE(true, '', containerId);
		}
	});
	
	zenario_wysiwyg_editor.summaries[containerId] = {locked: summaryLocked, empty: summaryEmpty, matches: summaryMatches};
	
	
	window.zenarioEditorSave = function(editor) {
		zenario_wysiwyg_editor.saveViaAJAX(get(editor.id), true);
	};

	window.zenarioEditorSaveAndContinue = function(editor) {
		zenario_wysiwyg_editor.saveViaAJAX(get(editor.id));
	};

	window.zenarioEditorCancel = function(editor) {
		zenario_wysiwyg_editor.close(get(editor.id));
	};
}

zenario_wysiwyg_editor.saveViaAJAX = function(el, close, confirm, confirmChoice) {
	
	var editorId;
	
	if (typeof el == 'string') {
		editorId = el;
		el = get(el);
	} else {
		editorId = el.id;
	}

	var containerId = zenario.getContainerIdFromEl(el);
	var slotName = zenario.getSlotnameFromEl(el);
	
	
	if (!confirm && zenario_wysiwyg_editor.summaries[containerId] && !zenario_wysiwyg_editor.summaries[containerId].locked) {
		
		if (zenario_wysiwyg_editor.summaries[containerId].empty) {
			zenario_wysiwyg_editor.floatingMessage(
				zenarioA.phrase.saveSyncSummaryPrompt,
				'<input type="button" class="submit_selected" value="' + zenarioA.phrase.saveSyncSummary + '" onclick="zenario_wysiwyg_editor.saveViaAJAX(\'' + editorId + '\', ' + zenario.engToBoolean(close) + ', true, true);" />' +
				'<input type="button" class="submit_selected" value="' + zenarioA.phrase.saveDontSyncSummary + '" onclick="zenario_wysiwyg_editor.saveViaAJAX(\'' + editorId + '\', ' + zenario.engToBoolean(close) + ', true, false);" />' +
				'<input type="button" class="submit" value="' + zenarioA.phrase.cancel + '"/>',
				true);
			return;
		
		} else if (zenario_wysiwyg_editor.summaries[containerId].matches) {
			zenario_wysiwyg_editor.floatingMessage(
				zenarioA.phrase.saveUpdateSummaryPrompt,
				'<input type="button" class="submit_selected" value="' + zenarioA.phrase.saveUpdateSummary + '" onclick="zenario_wysiwyg_editor.saveViaAJAX(\'' + editorId + '\', ' + zenario.engToBoolean(close) + ', true, true);" />' +
				'<input type="button" class="submit_selected" value="' + zenarioA.phrase.saveDontUpdateSummary + '" onclick="zenario_wysiwyg_editor.saveViaAJAX(\'' + editorId + '\', ' + zenario.engToBoolean(close) + ', true, false);" />' +
				'<input type="button" class="submit" value="' + zenarioA.phrase.cancel + '"/>',
				true);
			return;
		}
		
	} else if (confirm && !confirmChoice) {
		zenario_wysiwyg_editor.summaries[containerId] = false;
	}
	
	
	var saveLink = zenario.get(containerId + '_save_link').value;
	var content = $('div#' + editorId).tinymce().getContent();
	
	var error = zenario_wysiwyg_editor.AJAX(
		saveLink,
			'_zenario_save_content_=1' +
			'&_sync_summary=' + zenario.engToBoolean(confirm && confirmChoice) +
			'&content__content=' + encodeURIComponent(content),
		true);
	
	if (error) {
		zenario_wysiwyg_editor.floatingMessage(error, true, 'error');
	} else if (close) {
		zenario_wysiwyg_editor.doClose(slotName);
	} else {
		zenarioA.notification(zenarioA.phrase.contentSaved);
	}
}

/*
	fileBrowserCallback(
						self.getEl('inp').id,
						self.getEl('inp').value,
						settings.filetype,
						window
					);
*/
zenario_wysiwyg_editor.SK = function(field_name, url, type, win) {
	
	zenario_wysiwyg_editor.win = win;
	zenario_wysiwyg_editor.field_name = field_name;
	
	if (type == 'file') {
		zenarioA.SK('zenario_wysiwyg_editor', 'setLinkURL', false,
						'zenario__content/nav/content/panel',
						'zenario__content/nav/content/panel',
						'zenario__content/nav/content/panel',
						'zenario__content/nav/content/panel',
						false, undefined, undefined, undefined, true);
	
	} else if (type == 'image') {
		zenarioA.SK('zenario_wysiwyg_editor', 'setImageURL', false,
						'zenario__content/nav/content/panel/item_buttons/images//' + zenario_wysiwyg_editor.cType + '_' + zenario_wysiwyg_editor.cID + '//',
						'zenario__content/hidden_nav/media/panel/hidden_nav/inline_images_for_content/panel',
						'zenario__content/hidden_nav/media/panel/hidden_nav/inline_images_for_content/panel',
						'zenario__content/hidden_nav/media/panel/hidden_nav/inline_images_for_content/panel',
						true, undefined, undefined, undefined, true);
	}
}

zenario_wysiwyg_editor.lastFieldValue = '';
zenario_wysiwyg_editor.setEditorField = function(value, el, onlyIfEmpty) {
	if (el === undefined) {
		el = zenario_wysiwyg_editor.win.document.getElementById(zenario_wysiwyg_editor.field_name);
	}
	
	if (onlyIfEmpty) {
		if (el.value !== '' && el.value != zenario_wysiwyg_editor.lastFieldValue) {
			return;
		}
		zenario_wysiwyg_editor.lastFieldValue = value;
	}
	
	el.value = value;
	zenario.fireChangeEvent(el);
}

zenario_wysiwyg_editor.setLinkURL = function(path, key, row) {
	//Get the URL via an AJAX program
	key.getItemURL = 1;
	var URL = zenario.pluginClassAJAX('zenario_common_features', key, true);
	
	zenario_wysiwyg_editor.setEditorField(row.title, $('.mce-panel input.mce-link_text_to_display')[0], true);
	zenario_wysiwyg_editor.setEditorField(URL);
}

zenario_wysiwyg_editor.setImageURL = function(path, key, row) {
	var imageURL = 'zenario/file.php?c=' + row.checksum + '&filename=' + encodeURIComponent(row.filename);
	
	zenario_wysiwyg_editor.setEditorField(row.alt_tag, $('.mce-panel input.mce-image_alt')[0]);
	zenario_wysiwyg_editor.setEditorField(imageURL);
}

zenario_wysiwyg_editor.setFlashURL = function(path, key, row) {
	var flashURL = 'zenario/file.php?c=' + row.checksum + '&filename=' + encodeURIComponent(row.filename);
	
	zenario_wysiwyg_editor.setEditorField(flashURL);
}

zenario_wysiwyg_editor.close = function(el) {

	var containerId = zenario.getContainerIdFromEl(el);
	var slotName = zenario.getSlotnameFromEl(zenario.get(containerId));
	
	zenario_wysiwyg_editor.floatingMessage(
		zenarioA.phrase.closeEditorWarning,
		'<input type="button" class="submit_selected" value="' + zenarioA.phrase.abandonChanges + '" onclick="zenarioA.closeFloatingBox(); zenario_wysiwyg_editor.doClose(\'' + slotName + '\');" />' +
		'<input type="button" class="submit" value="' + zenarioA.phrase.cancel + '"/>',
		true);
}

zenario_wysiwyg_editor.doClose = function(slotName) {
	$('#zenario_editor_toolbar').html('').hide();
	zenario_wysiwyg_editor.refreshPluginSlot(slotName, undefined, false);
	
	//Reload the Admin Toolbar
	zenarioAT.init();
}



})(
	zenario, zenarioA, zenario_wysiwyg_editor);