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


//These functions are included on every page, just after the body tag, so they're written to be
//as small as possible when minified
(function(document_body, window, not_, retina) {
	
	//This function sets/modifies a CSS class on the document.body.
	var setBodyClass = window.zenarioSBC = function(condition, metClassName, notMetClassName) {
			
			document_body.className = document_body.className
				.replace(
					new RegExp('\\b(' + metClassName + '|' + notMetClassName + ')\\b', 'g'),
					''
				)
				+ ' '
				+ (condition? metClassName : notMetClassName);
		},
	
		//This function sets the grid properties.
		//It also checks the minWidth, and will set a CSS class on the body depending on
		//whether the grid is currently responsive or not
		setGridSettings = window.zenarioSGS = function(zenarioGrid) {
			window.zenarioGrid = zenarioGrid;
			setBodyClass(
				zenarioGrid.responsive
			 && window.innerWidth < zenarioGrid.minWidth, 'mobile', 'desktop');
		};
	
	//Start off with empty settings for the grid
	setGridSettings({});
	
	//Set a CSS class on the body, depending on whether JavaScript is enabled
	setBodyClass(1, 'js', 'no_js');
	
	//Add a CSS class for whether this is retina or not.
	//(Note that this won't work for IE 10 or earlier).
	setBodyClass(window.devicePixelRatio > 1, 'retina', 'not_retina');


})(document.body, window);