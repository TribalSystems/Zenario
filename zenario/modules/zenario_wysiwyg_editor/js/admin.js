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

zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioAB, zenarioAT, zenarioO,
	get, engToBoolean, htmlspecialchars, ifNull, jsEscape, phrase,
	extensionOf, methodsOf, has,
	zenario_wysiwyg_editor
) {
	"use strict";




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
		script_url: URLBasePath + zenario.tinyMCEPath,

		plugins: ["advlist autolink lists link image charmap hr anchor",
        "searchreplace code",
        "nonbreaking zenario_save table contextmenu directionality",
        "paste autoresize"],
        
        image_advtab: true,
        visual_table_class: ' ',
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
		document_base_url: URLBasePath,
		convert_urls: true,
		relative_urls: true,
		
		
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
		
		file_browser_callback: zenarioA.fileBrowser,
		
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
	
	zenario_wysiwyg_editor.startPoking();
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
				phrase.saveSyncSummaryPrompt,
				'<input type="button" class="submit_selected" value="' + phrase.saveSyncSummary + '" onclick="zenario_wysiwyg_editor.saveViaAJAX(\'' + editorId + '\', ' + engToBoolean(close) + ', true, true);" />' +
				'<input type="button" class="submit_selected" value="' + phrase.saveDontSyncSummary + '" onclick="zenario_wysiwyg_editor.saveViaAJAX(\'' + editorId + '\', ' + engToBoolean(close) + ', true, false);" />' +
				'<input type="button" class="submit" value="' + phrase.cancel + '"/>',
				true);
			return;
		
		} else if (zenario_wysiwyg_editor.summaries[containerId].matches) {
			zenario_wysiwyg_editor.floatingMessage(
				phrase.saveUpdateSummaryPrompt,
				'<input type="button" class="submit_selected" value="' + phrase.saveUpdateSummary + '" onclick="zenario_wysiwyg_editor.saveViaAJAX(\'' + editorId + '\', ' + engToBoolean(close) + ', true, true);" />' +
				'<input type="button" class="submit_selected" value="' + phrase.saveDontUpdateSummary + '" onclick="zenario_wysiwyg_editor.saveViaAJAX(\'' + editorId + '\', ' + engToBoolean(close) + ', true, false);" />' +
				'<input type="button" class="submit" value="' + phrase.cancel + '"/>',
				true);
			return;
		}
		
	} else if (confirm && !confirmChoice) {
		zenario_wysiwyg_editor.summaries[containerId] = false;
	}
	
	
	var saveLink = get(containerId + '_save_link').value;
	var content = zenario.tinyMCEGetContent($('div#' + editorId).tinymce());
	
	var error = zenario_wysiwyg_editor.AJAX(
		saveLink,
			'_zenario_save_content_=1' +
			'&_sync_summary=' + engToBoolean(confirm && confirmChoice) +
			'&content__content=' + encodeURIComponent(content),
		true);
	
	if (error) {
		zenario_wysiwyg_editor.floatingMessage(error, true, 'error');
	} else if (close) {
		zenario_wysiwyg_editor.doClose(slotName);
	} else {
		zenarioA.notification(phrase.contentSaved);
	}
}



zenario_wysiwyg_editor.close = function(el) {

	var containerId = zenario.getContainerIdFromEl(el);
	var slotName = zenario.getSlotnameFromEl(get(containerId));
	
	zenario_wysiwyg_editor.floatingMessage(
		phrase.closeEditorWarning,
		'<input type="button" class="submit_selected" value="' + phrase.abandonChanges + '" onclick="zenarioA.closeFloatingBox(); zenario_wysiwyg_editor.doClose(\'' + slotName + '\');" />' +
		'<input type="button" class="submit" value="' + phrase.cancel + '"/>',
		true);
}

zenario_wysiwyg_editor.doClose = function(slotName) {
	zenario_wysiwyg_editor.stopPoking();
	
	$('#zenario_editor_toolbar').html('').hide();
	zenario_wysiwyg_editor.refreshPluginSlot(slotName, undefined, false);
	
	//Reload the Admin Toolbar
	zenarioAT.init();
}


zenario_wysiwyg_editor.stopPoking = function() {
	if (zenario_wysiwyg_editor.poking) {
		clearInterval(zenario_wysiwyg_editor.poking);
	}
	zenario_wysiwyg_editor.poking = false;
};

zenario_wysiwyg_editor.startPoking = function() {
	if (!zenario_wysiwyg_editor.poking) {
		zenario_wysiwyg_editor.poking = setInterval(zenario_wysiwyg_editor.poke, 2 * 60 * 1000);
	}
};

zenario_wysiwyg_editor.poke = function() {
	zenario.ajax(URLBasePath + 'zenario/admin/quick_ajax.php?keep_session_alive=1')
};

	


zenario_wysiwyg_editor.listenForDoubleClick = function(slotName, containerId, buttonSelector) {
	$('div#'+containerId).off('dblclick').on('dblclick', function() {
		if (zenarioA.toolbar == 'edit') {
			$(buttonSelector).click();
		}
	});
};





}, zenario_wysiwyg_editor);