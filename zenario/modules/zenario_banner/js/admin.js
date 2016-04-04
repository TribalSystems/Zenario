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
	zenario_banner
) {
	"use strict";




zenario_banner.open = function(containerId, editorId, delayed) {
	
	if (!zenarioAT.loaded && !delayed) {
		zenarioAT.runOnInit.push(function() {
			zenario_banner.open(containerId, editorId, true);
		});
		return;
	}
	
	var $input = $('<input type="text" id="banner_title_input_box" placeholder="'+zenario_banner.phrase('Title')+'"/>'), 
		$title = $('div#'+containerId+' div.banner_title h2'),
		$editor = $('div#' + editorId),
		editorContentIsEmpty = $.trim($editor.text()) == '',
		p, 
		props = ['color', 'background', 'font-family', 'text-size', 'font-weight', 'font-size', 'font-style']; 
		
	for (p in props) {
		$input.css(props[p], $title.css(props[p])); 
	}
	$input.val($title.text().replace(/^\s+/, '').replace(/\s+$/, '')); 
	$input.css('border', 'none'); $title.replaceWith($input);
	
	zenarioA.getSkinDesc();
	
	if (editorContentIsEmpty) {
		$editor.addClass('empty_contenteditable_with_placeholder');
	}
	
	$editor.tinymce({
		script_url: URLBasePath + zenario.tinyMCEPath,

		plugins: ["advlist autolink lists link charmap hr anchor",
		"searchreplace code",
		"nonbreaking zenario_save contextmenu directionality",
		"paste autoresize"],
		
		browser_spellcheck: true,
		
		menu: {
			file: {title: 'File', items: ''},
			edit: {title: 'Edit', items: 'undo redo | cut copy paste | selectall'},
			insert: {title: 'Insert', items: 'link | anchor hr charmap'},
			view: {title: 'View', items: ''},
			format: {title: 'Format', items: 'bold italic underline strikethrough superscript subscript' + (zenarioA.skinDesc.style_formats? ' | formats' : '') + ' | removeformat'},
			tools: {title: 'Tools', items: 'searchreplace | code'}
		},
		
		toolbar: 'undo redo | link unlink | bold italic underline | removeformat' + (zenarioA.skinDesc.style_formats? ' | styleselect' : '') + ' | fontsizeselect | formatselect | numlist bullist | outdent indent | alignleft aligncenter alignright alignjustify | save save_and_close cancel',
		statusbar: false,
		
		autoresize_min_height: 100,
		paste_preprocess: zenarioA.tinyMCEPasteRreprocess,
		
		inline: true,
		zenario_inline_ui: true,
		
		inline_styles: false,
		allow_events: true,
		allow_script_urls: false,
		document_base_url: URLBasePath,
		convert_urls: true,
		relative_urls: true,
		
		style_formats: zenarioA.skinDesc.style_formats,
		file_browser_callback: zenarioA.fileBrowser,
		
		
		setup: function(editor) {
			editor.on('keyup', function(e) {
				var editorContentIsNowEmpty = editor.getContent() == '';
				
				if (editorContentIsEmpty && !editorContentIsNowEmpty) {
					$editor.removeClass('empty_contenteditable_with_placeholder');
				
				} else if (!editorContentIsEmpty && editorContentIsNowEmpty) {
					$editor.addClass('empty_contenteditable_with_placeholder');
				}
				editorContentIsEmpty = editorContentIsNowEmpty;
			});
		}
	});
	
	window.zenarioEditorSave = function(editor) {
		zenario_banner.saveViaAJAX(get(editor.id), true);
	};
	window.zenarioEditorSaveAndContinue = function(editor) {
		zenario_banner.saveViaAJAX(get(editor.id));
	};
	window.zenarioEditorCancel = function(editor) {
		zenario_banner.close(get(editor.id));
	};
	
	zenario_banner.startPoking();
}



zenario_banner.saveViaAJAX = function(el, close, confirm, confirmChoice) {
	var editorId;
	
	if (typeof el == 'string') {
		editorId = el;
		el = get(el);
	} else {
		editorId = el.id;
	}

	var containerId = zenario.getContainerIdFromEl(el);
	var slotName = zenario.getSlotnameFromEl(el);
	
	var saveLink = get(containerId + '_save_link').value;
	var content = zenario.tinyMCEGetContent($('div#' + editorId).tinymce());
	
	var error = zenario_banner.AJAX(
		saveLink,
		{
			_zenario_save_content_: 1,
			_sync_summary: engToBoolean(confirm && confirmChoice),
			content__content: content,
			content__title: $('div#'+containerId+' #banner_title_input_box').val()
		}, true);
	
	if (error) {
		zenario_banner.floatingMessage(error, true, 'error');
	} else if (close) {
		zenario_banner.doClose(slotName);
	} else {
		zenarioA.notification(phrase.contentSaved);
	}
}



zenario_banner.stopPoking = function() {
	if (zenario_banner.poking) {
		clearInterval(zenario_banner.poking);
	}
	zenario_banner.poking = false;
};

zenario_banner.startPoking = function() {
	if (!zenario_banner.poking) {
		zenario_banner.poking = setInterval(zenario_banner.poke, 2 * 60 * 1000);
	}
};

zenario_banner.poke = function() {
	zenario.ajax(URLBasePath + 'zenario/admin/quick_ajax.php?keep_session_alive=1')
};



zenario_banner.close = function(el) {

	var containerId = zenario.getContainerIdFromEl(el);
	var slotName = zenario.getSlotnameFromEl(get(containerId));
	
	zenario_banner.floatingMessage(
		phrase.closeEditorWarning,
		'<input type="button" class="submit_selected" value="' + phrase.abandonChanges + '" onclick="zenarioA.closeFloatingBox(); zenario_banner.doClose(\'' + slotName + '\');" />' +
		'<input type="button" class="submit" value="' + phrase.cancel + '"/>',
		true);
}

zenario_banner.doClose = function(slotName) {
	zenario_banner.stopPoking();
	
	$('#zenario_editor_toolbar').html('').hide();
	zenario_banner.refreshPluginSlot(slotName, undefined, false);
	
	//Reload the Admin Toolbar
	zenarioAT.init();
}




}, zenario_banner);