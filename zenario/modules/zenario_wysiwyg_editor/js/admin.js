/*
 * Copyright (c) 2024, Tribal Limited
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
	zenario, zenarioA, zenarioT, zenarioAB, zenarioAT, zenarioO,
	encodeURIComponent, defined, engToBoolean, get, htmlspecialchars, jsEscape, phrase,
	extensionOf, methodsOf, has,
	zenario_wysiwyg_editor
) {
	"use strict";




zenario_wysiwyg_editor.summaries = {};

zenario_wysiwyg_editor.animationsHiddenInEditors = false;
zenario_wysiwyg_editor.hideAnimationsInEditors = function() {
	zenario_wysiwyg_editor.animationsHiddenInEditors = true;
};

zenario_wysiwyg_editor.imagesHiddenInEditors = false;
zenario_wysiwyg_editor.hideImagesInEditors = function() {
	zenario_wysiwyg_editor.imagesHiddenInEditors = true;
};

zenario_wysiwyg_editor.open = function(containerId, editorId, html, summaryLocked, summaryEmpty, summaryMatches, delayed) {
	
	//If the Admin Toolbar has not loaded yet, save this code until it has loaded
	if (!zenarioAT.loaded && !delayed) {
		zenarioAT.runOnInit.push(function() {
			zenario_wysiwyg_editor.open(containerId, editorId, html, summaryLocked, summaryEmpty, summaryMatches, true);
		});
		return;
	}
	
	//Remember the current scroll position
	var currentScrollPosition = zenario.scrollTop(),
		images = '';
	
	if (!zenario_wysiwyg_editor.animationsHiddenInEditors && !zenario_wysiwyg_editor.imagesHiddenInEditors) {
		images = 'media,image,|,';
	} else if (!zenario_wysiwyg_editor.animationsHiddenInEditors) {
		images = 'media,|,';
	} else if (!zenario_wysiwyg_editor.imagesHiddenInEditors) {
		images = 'image,|,';
	}
	
	var $editor = $('div#' + editorId),
		skinEditorOptions = zenarioA.skinEditorOptions,
		options = {
			promotion: false,

			plugins: [
				"advlist", "autolink", "lists", "link", "image", "charmap", "anchor", "emoticons",
				"searchreplace", "code",
				"nonbreaking", "table", "directionality",
				"autoresize",
				"visualblocks",
				"zenario_save"
			],
		
			image_advtab: true,
			visual_table_class: ' ',
			browser_spellcheck: true,
		
			menu: {
				edit: {title: 'Edit', items: 'undo redo | cut copy paste | selectall | searchreplace'},
				format: {title: 'Format', items: 'bold italic underline strikethrough superscript subscript codeformat removeformat' + (skinEditorOptions && skinEditorOptions.style_formats? ' | forecolor backcolor | styles' : '') + ' ' + (skinEditorOptions && skinEditorOptions.font_family_formats? 'fontfamily ' : '') + 'align'},
				insert: {title: 'Insert', items: 'image link | anchor hr charmap'},
				table: {title: 'Table', items: 'inserttable tableprops deletetable | cell row column'},
				view: {title: 'View', items: 'code | visualblocks'}
			},
			removed_menuitems: 'file newdocument restoredraft print',
		
			toolbar: 'undo redo | image link unlink | blocks' + (skinEditorOptions && skinEditorOptions.style_formats? ' | styles' : '') + ' | ' + (skinEditorOptions && skinEditorOptions.font_family_formats? 'fontfamily ' : '') + 'fontsize | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist | blockquote | charmap emoticons | code | zenario_save_and_continue zenario_save_and_close zenario_abandon',
			statusbar: false,
		
			//This would change how the toolbar overflows if space is tight
			//toolbar_mode: 'wrap',
		
		
			//autoresize_max_height: Math.max(Math.floor(($(window).height() - 130 - 100) * 0.9), 400),
			autoresize_min_height: 100,
			paste_preprocess: zenarioA.tinyMCEPasteRreprocess,
			paste_data_images: false,
		
			inline: true,
			//zenario_inline_ui: true,
			fixed_toolbar_container: '#zenario_at_wrap',
			toolbar_persist: true,
		
			inline_styles: false,
			allow_events: true,
			allow_script_urls: true,
			document_base_url: URLBasePath,
			convert_urls: true,
			relative_urls: !zenario.slashesInURL,
		
		
			font_size_formats: '8pt 9pt 10pt 11pt 12pt 13pt 14pt 15pt 16pt 17pt 18pt 24pt 30pt 36pt',
			/*: [
				{title: 'Bold text', inline: 'b'},
				{title: 'Red text', inline: 'span', styles: {color: '#ff0000'}},
				{title: 'Red header', block: 'h1', styles: {color: '#ff0000'}},
				{title: 'Example 1', inline: 'span', classes: 'example1'},
				{title: 'Example 2', inline: 'span', classes: 'example2'},
				{title: 'Table styles'},
				{title: 'Table row 1', selector: 'tr', classes: 'tablerow1'}
			],*/
		
			file_picker_callback: zenarioA.fileBrowser,
		
			init_instance_callback: function(instance) {
				//zenario.removeLinkStatus($editor);
				
				instance.setContent(html);
				
				zenarioA.enableDragDropUploadInTinyMCE(true, '', containerId);
			
				//Attempt to restore the scroll position, if something in this process overwrote it.
				if (defined(currentScrollPosition)) {
					zenario.scrollTop(currentScrollPosition);
				
					window.setTimeout(function() {
						zenario.scrollTop(currentScrollPosition);
					}, 0);
				
				}
			
				//Attempt to put the cursor immediately in the field when it loads with the editor
				instance.focus();
				
				
				//TinyMCE will automatically remove any <iframe> or <script> tags.
				//This is desirable, but we want some way to warn an admin about this,
				//to help them understand why it happened.
				//There's no way to do this nicely but we'll use a work-around...
				var warnAboutTagRemoval = false;
				
				//Listen out for the admin opening the source-code editor.
				instance.on('ExecCommand', (e) => {
					if (e.command == 'mceCodeEditor') {
						
						warnAboutTagRemoval = false;
						
						//Add a change event to the textarea that lets them enter the source code.
						$('.tox-dialog .tox-textarea').on('change', function() {
							
							//Check to see if they've added an <iframe> or <script> tag,
							//and remember that it was there.
							var val = $(this).val();
							warnAboutTagRemoval = val.match(/\<(iframe|script)\b/);
						});
					}
				});
				
				//There's no "on save" event for the source code editor, but we can use the
				//NodeChange event as a work-around for that.
				instance.on('NodeChange', function(e) {
					
					//If the admin had an <iframe> or <script> tag in their source code,
					//and then they've closed the source code editor and are now back in the
					//regular WYSIWYG editor, assume it was removed and warn them about it.
					if (warnAboutTagRemoval) {
						warnAboutTagRemoval = false;
						
						zenarioA.notification(phrase.editorStripsTagsWarning, 'warning', {timeOut: 15000, extendedTimeOut: 60000});
					}
				});
			}
		};
	
	
	if (skinEditorOptions) {
		options = _.extend(options, skinEditorOptions);
	}
	
	$editor.tinymce(options);
	
	
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
	
	zenario.startPoking(zenario_wysiwyg_editor);
};

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
				'<input type="button" class="zenario_submit_button" value="' + phrase.saveSyncSummary + '" onclick="zenario_wysiwyg_editor.saveViaAJAX(\'' + editorId + '\', ' + engToBoolean(close) + ', true, true);" />' +
				'<input type="button" class="zenario_submit_button" value="' + phrase.saveDontSyncSummary + '" onclick="zenario_wysiwyg_editor.saveViaAJAX(\'' + editorId + '\', ' + engToBoolean(close) + ', true, false);" />' +
				'<input type="button" class="zenario_gp_button" value="' + phrase.cancel + '"/>',
				true);
			return;
		
		} else if (zenario_wysiwyg_editor.summaries[containerId].matches) {
			zenario_wysiwyg_editor.floatingMessage(
				phrase.saveUpdateSummaryPrompt,
				'<input type="button" class="zenario_submit_button" value="' + phrase.saveUpdateSummary + '" onclick="zenario_wysiwyg_editor.saveViaAJAX(\'' + editorId + '\', ' + engToBoolean(close) + ', true, true);" />' +
				'<input type="button" class="zenario_submit_button" value="' + phrase.saveDontUpdateSummary + '" onclick="zenario_wysiwyg_editor.saveViaAJAX(\'' + editorId + '\', ' + engToBoolean(close) + ', true, false);" />' +
				'<input type="button" class="zenario_gp_button" value="' + phrase.cancel + '"/>',
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
		{
			_zenario_save_content_: 1,
			_sync_summary: engToBoolean(confirm && confirmChoice),
			content__content: zenario.encodeItemIdForOrganizer(content)
		},
		true);
		//N.b. Cloudflare sometimes blocks HTML from being sent via the POST, e.g. if it sees it has links in it.
		//We're attempting to work around this by calling encodeItemIdForOrganizer() to mask the HTML.
	
	if (error) {
		zenario_wysiwyg_editor.floatingMessage(error, true, 'error');
	} else if (close) {
		zenario_wysiwyg_editor.doClose(slotName);
	} else {
		zenarioA.notification(phrase.contentSaved);
	}
};

//https://www.tinymce.com/docs/advanced/creating-custom-notifications/
//New notifications in 7.3
zenario_wysiwyg_editor.notification = function(editorId, options) {
	
	var editor = $('div#' + editorId).tinymce();
	
	if (editor && editor.notificationManager) {
		editor.notificationManager.open(options);
	}
};

zenario_wysiwyg_editor.close = function(el) {

	var containerId = zenario.getContainerIdFromEl(el);
	var slotName = zenario.getSlotnameFromEl(get(containerId));
	
	zenario_wysiwyg_editor.floatingMessage(
		phrase.closeEditorWarning,
		'<input type="button" class="zenario_submit_button" value="' + phrase.abandonChanges + '" onclick="zenario_wysiwyg_editor.doClose(\'' + slotName + '\');" />' +
		'<input type="button" class="zenario_gp_button" value="' + phrase.cancel + '"/>',
		true);
};

zenario_wysiwyg_editor.doClose = function(slotName) {
	zenario.stopPoking(zenario_wysiwyg_editor);
	
	$('#zenario_editor_toolbar').html('').hide();
	zenario_wysiwyg_editor.refreshPluginSlot(slotName, undefined, false);
	
	//Reload the Admin Toolbar
	zenarioAT.init();
};




zenario_wysiwyg_editor.listenForDoubleClick = function(slotName, containerId, buttonSelector) {
	
	zenario.slots[slotName].hasDoubleClick = true;
	
	$('div#'+containerId).off('dblclick').on('dblclick', function() {
		if (zenarioA.toolbar == 'edit') {
			$(buttonSelector).click();
		}
	});
	$('#'+containerId + '-wrap').addClass('zenario_showDoubleClickInEditMode');
};





}, zenario_wysiwyg_editor);
