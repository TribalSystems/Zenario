/*
 * Copyright (c) 2026, Tribal Limited
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




zenario_wysiwyg_editor.animationsHiddenInEditors = false;
zenario_wysiwyg_editor.hideAnimationsInEditors = function() {
	zenario_wysiwyg_editor.animationsHiddenInEditors = true;
};

zenario_wysiwyg_editor.imagesHiddenInEditors = false;
zenario_wysiwyg_editor.hideImagesInEditors = function() {
	zenario_wysiwyg_editor.imagesHiddenInEditors = true;
};

zenario_wysiwyg_editor.open = function(slotName, containerId, editorId, html, delayed) {
	
	//If the Admin Toolbar has not loaded yet, save this code until it has loaded
	if (!zenarioAT.loaded && !delayed) {
		zenarioAT.runOnInit.push(function() {
			zenario_wysiwyg_editor.open(slotName, containerId, editorId, html, true);
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
		
		//We don't want to show the "title" attributes in the drop-down menus from TinyMCE's menu bar,
		//however TinyMCE doesn't give us an option to control this.
		//Try to attach events to the menu bar, and aggressively try to kill off the title attributes.
		clearTitleAttributes = function() {
			clearTitleAttributesOff();
			$('.tox-selected-menu div[title]').attr('title', '');
			
			setTimeout(function() {
				clearTitleAttributesOn();
			}, 0);
		},
		clearTitleAttributesOn = function() {
			$('.tox-menubar > button, .tox-menu').on('mouseover click', clearTitleAttributes);
		},
		clearTitleAttributesOff = function() {
			$('.tox-menubar > button, .tox-menu').off('mouseover click', clearTitleAttributes);
		},
		
		options = {
			text_patterns: false,
			promotion: false,

			plugins: [
				"advlist", "autolink", "lists", "link", "image", "charmap", "anchor", "emoticons",
				"searchreplace", "code",
				"wordcount",
				"nonbreaking", "table", "directionality",
				"autoresize",
				"visualblocks",
				"zenario_save"
			],
		
			image_advtab: true,
			visual_table_class: ' ',
			browser_spellcheck: true,
		
			//The "menubar" property sets the order of elements and/or allows them to be hidden.
			//The "menu" property sets the contents of the menus (they can be in any order).
			//Please note: if a plugin isn't loaded for the intended menu item, it will not be shown.
			//For more info visit https://www.tiny.cloud/docs/tinymce/6/menus-configuration-options/
			menubar: "edit insert format adjust custom table tools",
			menu: {
				edit: {title: 'Edit', items: 'undo redo | cut copy paste pastetext | selectall'},
				insert: {title: 'Insert', items: 'image link | anchor hr charmap emoticons'},
				format: {title: 'Format', items: 'blocks align lineheight'},
				adjust: {title: 'Adjust', items: 'bold italic underline strikethrough superscript subscript codeformat | removeformat'},
				custom: {title: 'Custom', items: (skinEditorOptions && skinEditorOptions.style_formats? ' | styles' : '') + ' ' + (skinEditorOptions && skinEditorOptions.font_family_formats? 'fontfamily' : '') + ' fontsize | forecolor backcolor'},
				table: {title: 'Table', items: 'inserttable tableprops deletetable | cell row column'},
				tools: {title: 'Tools', items: 'searchreplace wordcount | visualblocks | code'}
			},
			removed_menuitems: 'file newdocument restoredraft print',
		
			toolbar: 'image link unlink blocks fontsize bold italic underline strikethrough forecolor removeformat | alignleft aligncenter alignright alignjustify | bullist numlist | blockquote | charmap emoticons | code| zenario_save_and_continue zenario_save_and_close zenario_abandon',
			statusbar: false,
		
			//This would change how the toolbar overflows if space is tight
			//toolbar_mode: 'wrap',
		
		
			//autoresize_max_height: Math.max(Math.floor(($(window).height() - 130 - 100) * 0.9), 400),
			autoresize_min_height: 100,
			paste_preprocess: zenarioA.editorPastePreprocess,
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
		
			file_picker_callback: function(tinyCallback, value, meta) {
				
				//When picking an image to place in a slot, try to work out how wide the slot is.
				//If we can do that, we'll set a limit to not display the image at a width that's larger than this.
				var gsDetails = zenarioA.getGridSlotDetails(slotName),
					maxImageWidth;
				
				if (gsDetails && gsDetails.pxWidth) {
					maxImageWidth = Math.floor(gsDetails.pxWidth);
				}
				
				zenarioA.fileBrowser(tinyCallback, value, meta, maxImageWidth);
			},
		
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
						
						zenarioT.notification(phrase.editorStripsTagsWarning, 'warning', {timeOut: 15000, extendedTimeOut: 60000});
					}
				});
				
				//N.b. the following patch has been taken out, as the slight errors seem to be harmless.
				//	//When used inside a fluid layout, TinyMCE has a bug when creating tables, where it will come up with % widths for the column
				//	//layouts but there will be weird rounding errors in the actual numbers.
				//	//For example, it might produce numbers such as 49.9584% or 100.017%, when it should really have been 50% or 100%.
				//	//I have a little script here to watch out for TinyMCE's NewCell event, and when it fires, go through checking the widths of
				//	//the columns and the table itself. If they match a regular expression, I'll consider them bugged and round them to one
				//	//decimal place to try and fix the issue.
				//	instance.on('NewCell', function(a, b, c) {
				//		zenario.actAfterDelayIfNotSuperseded('editorFixColumnWidths', function() {
				//			$('#' + containerId + ' .mce-content-body table, #' + containerId + ' .mce-content-body table colgroup col').each(function (i, el) {
				//			
				//				var width = el.style.width,
				//					match;
				//			
				//				if (width && (match = width.match(/((\d+)\.(\d\d\d+))\%/))) {
				//				
				//					width = zenario.round(1 * match[1], 1);
				//					el.style.width = width + '%';
				//				}
				//			});
				//		}, 10);
				//	});
				
				clearTitleAttributesOn();
			}
		};
	
	
	if (skinEditorOptions) {
		options = _.extend(options, skinEditorOptions);
	}
	
	$editor.tinymce(options);
	
	
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
	
	
	var saveLink = get(containerId + '_save_link').value;
	var content = zenario.tinyMCEGetContent($('div#' + editorId).tinymce());
	
	var error = zenario_wysiwyg_editor.AJAX(
		saveLink,
		{
			_zenario_save_content_: 1,
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
		zenarioT.notification(phrase.contentSaved);
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
};





}, zenario_wysiwyg_editor);
