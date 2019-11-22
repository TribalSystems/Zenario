/*
 * Copyright (c) 2019, Tribal Limited
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
		editorPhrases, options;
	
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
	
	
	if (!(editorPhrases = zenario_anonymous_comments.editorPhrases)) {
		editorPhrases = zenario_anonymous_comments.editorPhrases = zenario_anonymous_comments.loadPhrases([
			'_EDITOR_BOLD',
			'_EDITOR_CODE',
			'_EDITOR_DECREASE_INDENT',
			'_EDITOR_IMAGE_SRC',
			'_EDITOR_INDENT',
			'_EDITOR_INSERT_IMAGE',
			'_EDITOR_ITALIC',
			'_EDITOR_LINK',
			'_EDITOR_LINK_HREF',
			'_EDITOR_LINK_TEXT',
			'_EDITOR_ORDERED_LIST',
			'_EDITOR_PARAGRAPH',
			'_EDITOR_PREFORMATTED',
			'_EDITOR_QUOTE',
			'_EDITOR_REMOVE_FORMAT',
			'_EDITOR_STRIKE',
			'_EDITOR_UNDERLINE',
			'_EDITOR_UNLINK',
			'_EDITOR_UNORDERED_LIST'
		]);
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
				'Bold': editorPhrases._EDITOR_BOLD,
				'Italic': editorPhrases._EDITOR_ITALIC,
				'Underline': editorPhrases._EDITOR_UNDERLINE,
				'Strikethrough': editorPhrases._EDITOR_STRIKE,
				'Toggle code': editorPhrases._EDITOR_CODE,
				'Clear formatting': editorPhrases._EDITOR_REMOVE_FORMAT,
				'Insert/edit image': editorPhrases._EDITOR_INSERT_IMAGE,
				'Source': editorPhrases._EDITOR_IMAGE_SRC,
				'Insert/edit link': editorPhrases._EDITOR_LINK,
				'Url': editorPhrases._EDITOR_LINK_HREF,
				'Text to display': editorPhrases._EDITOR_LINK_TEXT,
				'Remove link': editorPhrases._EDITOR_UNLINK,
				'Toggle p': editorPhrases._EDITOR_PARAGRAPH,
				'Toggle pre': editorPhrases._EDITOR_PREFORMATTED,
				'Blockquote': editorPhrases._EDITOR_QUOTE,
				'Numbered list': editorPhrases._EDITOR_ORDERED_LIST,
				'Bullet list': editorPhrases._EDITOR_UNORDERED_LIST,
				'Decrease indent': editorPhrases._EDITOR_INDENT,
				'Increase indent': editorPhrases._EDITOR_DECREASE_INDENT
			
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