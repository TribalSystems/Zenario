/*
 * Copyright (c) 2018, Tribal Limited
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

/*
	This file contains JavaScript source code.
	The code here is not the code you see in your browser. Before this file is downloaded:
	
		1. Compilation macros are applied (e.g. "foreach" is a macro for "for .. in ... hasOwnProperty").
		2. It is minified (e.g. using Google Closure Compiler).
		3. It may be wrapped togther with other files (this is to reduce the number of http requests on a page).
	
	For more information, see js_minify.shell.php for steps (1) and (2), and visitor.wrapper.js.php for step (3).
*/



zenario.mtSettings = {
	variable: "m",
	escape: false,
	interpolate: /\{\{(.+?)\}\}/g,
	evaluate: /[<\{]%([\s\S]+?)%[>\}]/g,
	twigStyleSyntax: true
};

$(document)
	//Disable/enable scrolling the page when a colorbox opens/closes
	.bind('cbox_open', => { zenario.disableScrolling('colorbox'); })
	.bind('cbox_closed', => { zenario.enableScrolling('colorbox'); })
	
	.ready(function() {
		//Add tooltips and other jQuery elements to the page after it has loaded
		zenario.addJQueryElements();
		
		var	baseURL = URLBasePath + 'zenario/',
			baseURLlen = baseURL.length;
		
		$('script').each(function(i, el) {
			
			var src = el.src;
			
			if (src && src.substr(0, baseURLlen) == baseURL) {
				zenario.jsLibs[
					src.substr(baseURLlen)
						.replace(/(\?|\&)(v=[^\&]+|no_cache=1)\&/g, '$1')
						.replace(/(\?|\&)(v=[^\&]+|no_cache=1)\&/g, '$1')
						.replace(/(\?|\&)(v=[^\&]+|no_cache=1)$/g, '')
				] = true;
			}
		});
	});