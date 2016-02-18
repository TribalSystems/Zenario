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
(function(
	zenario, zenario_anonymous_comments,
	get,
	undefined) {


zenario_anonymous_comments.loadWithDelay = function(containerId, enableColours, enableImages, enableEmoticons, enableLinks) {
	if (zenario.browserIsiPad() || zenario.browserIsiPhone()) {
		return;
	}
	
	if (zenario.get('editor__' + containerId)) {
		zenario.get('editor__' + containerId).style.visibility = 'hidden';
	}
	
	setTimeout(
		function() {
			zenario_anonymous_comments.load(containerId, enableColours, enableImages, enableLinks);
		}, 350);
}

zenario_anonymous_comments.load = function(containerId, enableColours, enableImages, enableEmoticons, enableLinks, selfCalling) {
	if (zenario.browserIsiPad() || zenario.browserIsiPhone()) {
		return;
	}
	
	if (zenario.get('editor__' + containerId)) {
		zenario.get('editor__' + containerId).style.visibility = 'hidden';
	}

	if (typeof punymce != 'object') {
		if (!selfCalling) {
			jQuery.getScript(
				URLBasePath + 'zenario/js/punymce.wrapper.js.php?v=' + window.zenarioCSSJSVersionNumber + '&gz=' + zenario.useGZ,
				function() {
					punymce.Event._pageInit();
					zenario_anonymous_comments.load(containerId, enableColours, enableImages, enableEmoticons, enableLinks, true)
				}
			);
		}
		return;
	}
	
	if (!zenario_anonymous_comments.editorPhrases) {
		zenario_anonymous_comments.editorPhrases = zenario_anonymous_comments.loadPhrases([
			'_EDITOR_BOLD',
			'_EDITOR_ITALIC',
			'_EDITOR_UNDERLINE',
			'_EDITOR_STRIKE',
			'_EDITOR_UNORDERED_LIST',
			'_EDITOR_ORDERED_LIST',
			'_EDITOR_STYLE',
			'_EDITOR_CODE',
			'_EDITOR_REMOVE_CODE',
			'_EDITOR_REMOVE_FORMAT',
			'_EDITOR_EDIT_SOURCE',
			'_EDITOR_EMOTICONS',
			'_EDITOR_INSERT_IMAGE',
			'_EDITOR_IMAGE_SRC',
			'_EDITOR_LINK',
			'_EDITOR_UNLINK',
			'_EDITOR_LINK_HREF',
			'_EDITOR_TEXT_COLOR'
		]);
		
		punymce.extend(punymce.I18n, {
			// Core
			bold: zenario_anonymous_comments.editorPhrases._EDITOR_BOLD,
			italic: zenario_anonymous_comments.editorPhrases._EDITOR_ITALIC,
			underline: zenario_anonymous_comments.editorPhrases._EDITOR_UNDERLINE,
			ul: zenario_anonymous_comments.editorPhrases._EDITOR_UNORDERED_LIST,
			ol: zenario_anonymous_comments.editorPhrases._EDITOR_ORDERED_LIST,
			style: zenario_anonymous_comments.editorPhrases._EDITOR_STYLE,
			removeformat: zenario_anonymous_comments.editorPhrases._EDITOR_REMOVE_FORMAT,

			//modules
			editsource: zenario_anonymous_comments.editorPhrases._EDITOR_EDIT_SOURCE,
			emoticons: zenario_anonymous_comments.editorPhrases._EDITOR_EMOTICONS,
		
			// Image plugin
			insertimage: zenario_anonymous_comments.editorPhrases._EDITOR_INSERT_IMAGE,
			entersrc: zenario_anonymous_comments.editorPhrases._EDITOR_IMAGE_SRC,
		
			// Link plugin
			link: zenario_anonymous_comments.editorPhrases._EDITOR_LINK,
			unlink: zenario_anonymous_comments.editorPhrases._EDITOR_UNLINK,
			enterhref: zenario_anonymous_comments.editorPhrases._EDITOR_LINK_HREF,
		
			// Textcolor plugin
			textcolor: zenario_anonymous_comments.editorPhrases._EDITOR_TEXT_COLOR
		});
	}
	
	//Attempt to fix a bug with escaping in punymce
	zenario.get('editor__' + containerId).value = zenario.htmlspecialchars(zenario.get('editor__' + containerId).value);
	
	zenario_anonymous_comments['editor__' + containerId] = new punymce.Editor({
		id: 'editor__' + containerId,
		toolbar:
			'bold,italic,underline,' +
			(enableColours? 'textcolor,' : '') +
			'removeformat,style,ul,ol,' +
			(enableLinks? 'link,unlink,' : '') +
			(enableImages? 'image,' : '') +
			(enableEmoticons? 'emoticons,' : '') +
			'editsource',
		plugins:
			(enableColours? 'TextColor,' : '') +
			(enableLinks? 'Link,' : '') +
			(enableImages? 'Image,' : '') +
			(enableEmoticons? 'Emoticons,' : '') +
			'BBCode,Paste,EditSource,ForceNL,Protect,TabFocus',
		min_height : 200,
		min_width : 400,
		styles : [
			{ title : zenario_anonymous_comments.editorPhrases._EDITOR_CODE, cls : 'pre', cmd : 'FormatBlock', val : '<pre>' },
			{ title : zenario_anonymous_comments.editorPhrases._EDITOR_REMOVE_CODE, cls : 'p', cmd : 'FormatBlock', val : '<p>' }
		],
		emoticons : {
			auto_convert : 1,
			emoticons : {
				happy : [':)', '=)'],
				unhappy : [':|', '=|'],
				sad : [':(','=('],
				grin : [':D', '=D'],
				surprised : [':o',':O','=o', '=O'],
				wink : [';)'],
				halfhappy : ['=/', '=\\', ':\\'],
				tounge : [':P', ':p', '=P', '=p'],
				lol : [':lol:'],
				mad : [':x', ':X', ':@'],
				rolleyes : [':roll:'],
				cool : ['8)', '8-)']
			}
		},
		content_css: 'zenario/styles/skin.cache_wrapper.css.php?v=' + encodeURIComponent(window.zenarioCSSJSVersionNumber) + '&id=' + zenario.skinId + '&editor=1' + (zenario.useGZ? '' : '&gz=1')
	});
	
	if (zenario.get('editor__' + containerId + '_f')) {
		zenario.get('editor__' + containerId + '_f').style.width = '100%';
	}
	
	//Set the value of the text field when saving
	$('#' + containerId + ' :submit').click(function() {
		var containerId = zenario.getContainerIdFromEl(this);
		var message = {format: 'bbcode', content: zenario_anonymous_comments['editor__' + containerId].getContent()};
		
		zenario_anonymous_comments.toBBCode(undefined, message);
		
		zenario.get('editor__' + containerId).value = message.content;
	});
}



})(
	zenario, zenario_anonymous_comments,
	zenario.get);