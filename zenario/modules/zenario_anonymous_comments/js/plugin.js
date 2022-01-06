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

zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioT, zenarioAB, zenarioAT, zenarioO,
	encodeURIComponent, defined, engToBoolean, get, htmlspecialchars, jsEscape, phrase,
	extensionOf, methodsOf, has,
	zenario_anonymous_comments
) {
	"use strict";


zenario_anonymous_comments.load = function(editorId, enableImages, enableLinks) {
	
	var isMobile = zenario.isTouchScreen(),
		toolbarGap = ' | ',
		toolbar,
		toolbarLeft = 'bold italic underline strikethrough style-code | removeformat',
		toolbarRight = 'style-p style-pre | numlist bullist | blockquote outdent indent',
		plugins = 'lists paste autoresize stylebuttons',
		fixed_toolbar_container = '#toolbar_container_for_' + editorId,
		options;
	
	if (isMobile) {
		toolbar = [toolbarLeft, toolbarRight];
		$(fixed_toolbar_container).height(72);
		fixed_toolbar_container = false;
	
	} else {
		toolbar = toolbarLeft;
		
		if (enableImages || enableLinks) {
			toolbar += toolbarGap;
		
			if (enableImages) {
				toolbar += 'image';
				plugins += ' image';
			}
			
			if (enableLinks) {
				if (enableImages) {
					toolbar += ' ';
				}
				
				toolbar += 'link unlink';
				plugins += ' autolink link';
			}
		}
		
		toolbar += toolbarGap + toolbarRight;
	}
	
	options = {
		script_url: URLBasePath + zenario.tinyMCEPath,
		browser_spellcheck: true,
		height: 250,
		menubar: false,
		
		plugins: [plugins],
		toolbar: toolbar,
		
		link_title: false,
		link_class: false,
		target_list: false,
		image_description: false,
		image_dimensions: false,
		image_class: false,
		image_alignment: false,

		inline: true,
		fixed_toolbar_container: fixed_toolbar_container,
		setup: function(editor) {
			tinyMCE.i18n.add('en', {
				'Bold': window.anonymousCommentsPhrase.editorBold,
				'Italic': window.anonymousCommentsPhrase.editorItalic,
				'Underline': window.anonymousCommentsPhrase.editorUnderline,
				'Strikethrough': window.anonymousCommentsPhrase.editorStrikethrough,
				'Toggle code': window.anonymousCommentsPhrase.editorCode,
				'Clear formatting': window.anonymousCommentsPhrase.editorRemoveFormatting,
				'Insert/edit image': window.anonymousCommentsPhrase.editorInsertImage,
				'Source': window.anonymousCommentsPhrase.editorImageSrc,
				'Insert/edit link': window.anonymousCommentsPhrase.editorLink,
				'Url': window.anonymousCommentsPhrase.editorLinkHref,
				'Text to display': window.anonymousCommentsPhrase.editorLinkText,
				'Remove link': window.anonymousCommentsPhrase.editorRemoveLink,
				'Toggle p': window.anonymousCommentsPhrase.editorParagraph,
				'Toggle pre': window.anonymousCommentsPhrase.editorParagraphPreformatted,
				'Blockquote': window.anonymousCommentsPhrase.editorParagraphQuote,
				'Numbered list': window.anonymousCommentsPhrase.editorOrderedList,
				'Bullet list': window.anonymousCommentsPhrase.editorUnorderedList,
				'Decrease indent': window.anonymousCommentsPhrase.editorIndent,
				'Increase indent': window.anonymousCommentsPhrase.editorDecreaseIndent
			
			});
		},
		init_instance_callback: function(editor) {
			window.ed = editor;
			editor.focus();
		}
	};
	
	
	$('#' + editorId).tinymce(options);
};



}, zenario_anonymous_comments);